<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Models\PasswordResetModel;
use App\Models\UserModel;

/**
 * "Forgot password": the user enters their email, gets a 6-digit code by
 * email, verifies that code, and only then picks a new password (in a modal).
 */
class PasswordReset extends BaseController
{
    private const CODE_TTL_MINUTES = 15;
    private const MAX_ATTEMPTS     = 5;
    private const RESEND_COOLDOWN  = 60; // seconds between codes for one account
    private const SESSION_EMAIL    = 'password_reset_email';
    private const SESSION_VERIFIED = 'password_reset_verified'; // ['email' => …, 'reset_id' => …] once the code checks out

    /** Step 1: ask for the account email and send a code. */
    public function forgot()
    {
        if (currentUser()) {
            return redirect()->to('/dashboard');
        }

        $error = '';
        $email = trim($this->request->getGet('email') ?? '');

        if ($this->request->getMethod() === 'POST') {
            $email = trim($this->request->getPost('email') ?? '');

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                $error = $this->issueCode($email);

                if ($error === '') {
                    session()->set(self::SESSION_EMAIL, $email);
                    session()->remove(self::SESSION_VERIFIED); // a new code must be verified afresh

                    return redirect()->to('/reset-password')->with('info',
                        'If an account exists for ' . $email . ', a 6-digit reset code has been sent to it. '
                        . 'It expires in ' . self::CODE_TTL_MINUTES . ' minutes.');
                }
            }
        }

        return view('auth/forgot_password', ['error' => $error, 'email' => $email]);
    }

    /**
     * Step 2 (step=verify): check the code. Step 3 (step=password): only once
     * the code is verified, set the new password — the page shows that step
     * in a modal.
     */
    public function reset()
    {
        if (currentUser()) {
            return redirect()->to('/dashboard');
        }

        $email = (string) session()->get(self::SESSION_EMAIL);
        if ($email === '') {
            return redirect()->to('/forgot-password');
        }

        $error         = '';
        $passwordError = '';

        if ($this->request->getMethod() === 'POST') {
            $step = (string) $this->request->getPost('step');

            if ($step === 'verify') {
                $code = preg_replace('/\D/', '', (string) $this->request->getPost('code'));

                if (strlen($code) !== 6) {
                    $error = 'Enter the 6-digit code from the email.';
                } else {
                    [$error, $resetId] = $this->verifyCode($email, $code);

                    if ($error === '') {
                        session()->set(self::SESSION_VERIFIED, ['email' => $email, 'reset_id' => $resetId]);

                        return redirect()->to('/reset-password');
                    }
                }
            } elseif ($step === 'password') {
                $user    = (new UserModel())->findByEmail($email);
                $resetId = $user && (int) $user['is_active'] ? $this->verifiedResetId($email, (int) $user['id']) : null;
                $password = (string) $this->request->getPost('password');
                $confirm  = (string) $this->request->getPost('confirm_password');

                if ($resetId === null) {
                    session()->remove(self::SESSION_VERIFIED);
                    $error = 'Your verified code has expired. Request a new one.';
                } elseif (strlen($password) < 6) {
                    $passwordError = 'New password must be at least 6 characters.';
                } elseif ($password !== $confirm) {
                    $passwordError = 'New password and confirmation do not match.';
                } else {
                    (new UserModel())->update($user['id'], ['password' => password_hash($password, PASSWORD_BCRYPT)]);
                    (new PasswordResetModel())->closeAllForUser((int) $user['id']);
                    session()->remove([self::SESSION_EMAIL, self::SESSION_VERIFIED]);

                    return redirect()->to('/login')->with('success', 'Your password has been reset. You can now sign in.');
                }
            }
        }

        $user     = (new UserModel())->findByEmail($email);
        $verified = $user && $this->verifiedResetId($email, (int) $user['id']) !== null;

        return view('auth/reset_password', [
            'error'         => $error,
            'passwordError' => $passwordError,
            'verified'      => $verified,
            'email'         => $email,
            'info'          => session()->getFlashdata('info'),
        ]);
    }

    /**
     * The reset row this session verified, if that code is still the user's
     * newest open one and hasn't expired — otherwise null.
     */
    private function verifiedResetId(string $email, int $userId): ?int
    {
        $verified = session()->get(self::SESSION_VERIFIED);
        if (! is_array($verified) || ($verified['email'] ?? '') !== $email) {
            return null;
        }

        $reset = (new PasswordResetModel())->latestOpenForUser($userId);
        if (! $reset || (int) $reset['id'] !== (int) ($verified['reset_id'] ?? 0) || strtotime($reset['expires_at']) < time()) {
            return null;
        }

        return (int) $reset['id'];
    }

    /**
     * Creates and emails a code. Unknown emails get the same outcome as
     * known ones so the form can't be used to discover accounts.
     *
     * @return string error message, or '' on success
     */
    private function issueCode(string $email): string
    {
        $user = (new UserModel())->findByEmail($email);
        if (! $user || ! (int) $user['is_active']) {
            return '';
        }

        $resets = new PasswordResetModel();
        $latest = $resets->latestOpenForUser((int) $user['id']);
        if ($latest && time() - strtotime($latest['created_at']) < self::RESEND_COOLDOWN) {
            return ''; // a code was just sent — don't flood their inbox
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $resets->closeAllForUser((int) $user['id']);
        $resets->insert([
            'user_id'    => $user['id'],
            'code_hash'  => password_hash($code, PASSWORD_DEFAULT),
            'attempts'   => 0,
            'expires_at' => date('Y-m-d H:i:s', time() + self::CODE_TTL_MINUTES * 60),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($this->sendCodeEmail($user, $code)) {
            return '';
        }

        // Email isn't configured on a dev machine — log the code so the flow
        // can still be tested; never do this in production.
        if (ENVIRONMENT === 'development') {
            log_message('notice', 'Password reset code for {email}: {code} (email sending failed)', ['email' => $email, 'code' => $code]);

            return '';
        }

        return 'We could not send the reset email right now. Please try again later or contact the administrator.';
    }

    /**
     * Checks the code without using it up (that happens when the password is set).
     *
     * @return array{0: string, 1: int|null} [error message or '', verified reset id]
     */
    private function verifyCode(string $email, string $code): array
    {
        $invalid = 'That code is invalid or has expired. Request a new one.';

        $user = (new UserModel())->findByEmail($email);
        if (! $user || ! (int) $user['is_active']) {
            return [$invalid, null];
        }

        $resets = new PasswordResetModel();
        $reset  = $resets->latestOpenForUser((int) $user['id']);

        if (! $reset || strtotime($reset['expires_at']) < time() || (int) $reset['attempts'] >= self::MAX_ATTEMPTS) {
            return [$invalid, null];
        }

        if (! password_verify($code, $reset['code_hash'])) {
            $attempts = (int) $reset['attempts'] + 1;
            $resets->update($reset['id'], ['attempts' => $attempts]);

            $left = self::MAX_ATTEMPTS - $attempts;

            return [$left > 0
                ? 'Incorrect code. ' . $left . ' attempt' . ($left === 1 ? '' : 's') . ' left.'
                : 'Too many incorrect attempts. Request a new code.', null];
        }

        return ['', (int) $reset['id']];
    }

    private function sendCodeEmail(array $user, string $code): bool
    {
        $emailer = service('email');
        $config  = config('Email');

        if ($config->fromEmail === '') {
            return false;
        }

        $emailer->setFrom($config->fromEmail, $config->fromName ?: 'ACADOCS');
        $emailer->setTo($user['email']);
        $emailer->setSubject('Your ACADOCS password reset code');
        $emailer->setMailType('html');
        $emailer->setMessage(view('emails/password_reset_code', [
            'name'    => $user['name'],
            'code'    => $code,
            'minutes' => self::CODE_TTL_MINUTES,
        ]));

        try {
            if ($emailer->send(false)) {
                return true;
            }
            log_message('error', 'Password reset email failed: ' . strip_tags($emailer->printDebugger(['headers'])));
        } catch (\Throwable $e) {
            log_message('error', 'Password reset email failed: ' . $e->getMessage());
        }

        return false;
    }
}
