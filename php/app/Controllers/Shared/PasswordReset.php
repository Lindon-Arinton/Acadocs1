<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Models\PasswordResetModel;
use App\Models\UserModel;

/**
 * "Forgot password": the user enters their email, gets a 6-digit code by
 * email, then enters that code with a new password.
 */
class PasswordReset extends BaseController
{
    private const CODE_TTL_MINUTES = 15;
    private const MAX_ATTEMPTS     = 5;
    private const RESEND_COOLDOWN  = 60; // seconds between codes for one account
    private const SESSION_EMAIL    = 'password_reset_email';

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

                    return redirect()->to('/reset-password')->with('info',
                        'If an account exists for ' . $email . ', a 6-digit reset code has been sent to it. '
                        . 'It expires in ' . self::CODE_TTL_MINUTES . ' minutes.');
                }
            }
        }

        return view('auth/forgot_password', ['error' => $error, 'email' => $email]);
    }

    /** Step 2: check the code and set the new password. */
    public function reset()
    {
        if (currentUser()) {
            return redirect()->to('/dashboard');
        }

        $email = (string) session()->get(self::SESSION_EMAIL);
        if ($email === '') {
            return redirect()->to('/forgot-password');
        }

        $error = '';

        if ($this->request->getMethod() === 'POST') {
            $code     = preg_replace('/\D/', '', (string) $this->request->getPost('code'));
            $password = (string) $this->request->getPost('password');
            $confirm  = (string) $this->request->getPost('confirm_password');

            if (strlen($code) !== 6) {
                $error = 'Enter the 6-digit code from the email.';
            } elseif (strlen($password) < 6) {
                $error = 'New password must be at least 6 characters.';
            } elseif ($password !== $confirm) {
                $error = 'New password and confirmation do not match.';
            } else {
                $error = $this->consumeCode($email, $code, $password);

                if ($error === '') {
                    session()->remove(self::SESSION_EMAIL);

                    return redirect()->to('/login')->with('success', 'Your password has been reset. You can now sign in.');
                }
            }
        }

        return view('auth/reset_password', [
            'error' => $error,
            'email' => $email,
            'info'  => session()->getFlashdata('info'),
        ]);
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
        if (! $user) {
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

    /** @return string error message, or '' on success */
    private function consumeCode(string $email, string $code, string $password): string
    {
        $invalid = 'That code is invalid or has expired. Request a new one.';

        $users = new UserModel();
        $user  = $users->findByEmail($email);
        if (! $user) {
            return $invalid;
        }

        $resets = new PasswordResetModel();
        $reset  = $resets->latestOpenForUser((int) $user['id']);

        if (! $reset || strtotime($reset['expires_at']) < time() || (int) $reset['attempts'] >= self::MAX_ATTEMPTS) {
            return $invalid;
        }

        if (! password_verify($code, $reset['code_hash'])) {
            $attempts = (int) $reset['attempts'] + 1;
            $resets->update($reset['id'], ['attempts' => $attempts]);

            $left = self::MAX_ATTEMPTS - $attempts;

            return $left > 0
                ? 'Incorrect code. ' . $left . ' attempt' . ($left === 1 ? '' : 's') . ' left.'
                : 'Too many incorrect attempts. Request a new code.';
        }

        $users->update($user['id'], ['password' => password_hash($password, PASSWORD_BCRYPT)]);
        $resets->closeAllForUser((int) $user['id']);

        return '';
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
