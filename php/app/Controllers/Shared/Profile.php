<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Profile extends BaseController
{
    private const ALLOWED_PHOTO_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function index()
    {
        $sessionUser = currentUser();
        $model       = new UserModel();

        if ($this->request->getMethod() === 'POST') {
            $isAjax  = $this->request->isAJAX();
            $action  = $this->request->getPost('action');
            $message = null;
            $error   = null;

            try {
                if ($action === 'update_info') {
                    $name  = trim($this->request->getPost('name') ?? '');
                    $email = trim($this->request->getPost('email') ?? '');

                    if ($name === '' || $email === '') {
                        $error = 'Name and email are required.';
                    } elseif ($model->where('email', $email)->where('id !=', $sessionUser['id'])->first()) {
                        $error = 'That email is already in use by another account.';
                    } else {
                        $model->update($sessionUser['id'], ['name' => $name, 'email' => $email]);
                        $this->refreshSession($model, $sessionUser['id']);
                        $message = 'Profile updated successfully.';
                    }
                } elseif ($action === 'change_password') {
                    $current = $this->request->getPost('current_password') ?? '';
                    $new     = $this->request->getPost('new_password') ?? '';
                    $confirm = $this->request->getPost('confirm_password') ?? '';
                    $dbUser  = $model->find($sessionUser['id']);

                    if (! password_verify($current, $dbUser['password'])) {
                        $error = 'Current password is incorrect.';
                    } elseif (strlen($new) < 6) {
                        $error = 'New password must be at least 6 characters.';
                    } elseif ($new !== $confirm) {
                        $error = 'New password and confirmation do not match.';
                    } else {
                        $model->update($sessionUser['id'], ['password' => password_hash($new, PASSWORD_BCRYPT)]);
                        $message = 'Password changed successfully.';
                    }
                } elseif ($action === 'upload_photo') {
                    $rules = [
                        'photo' => [
                            'label'  => 'Photo',
                            'rules'  => 'uploaded[photo]|max_size[photo,2048]|ext_in[photo,' . implode(',', self::ALLOWED_PHOTO_EXT) . ']|is_image[photo]',
                            'errors' => [
                                'uploaded' => 'Please choose an image to upload.',
                                'max_size' => 'Image is too large (max 2MB).',
                                'ext_in'   => 'Unsupported image type.',
                                'is_image' => 'The file must be a valid image.',
                            ],
                        ],
                    ];

                    if (! $this->validate($rules)) {
                        $error = implode(' ', $this->validator->getErrors());
                    } else {
                        $targetDir = FCPATH . 'uploads/avatars';

                        if (! is_dir($targetDir)) {
                            mkdir($targetDir, 0755, true);
                        }

                        $dbUser = $model->find($sessionUser['id']);
                        if (! empty($dbUser['photo']) && is_file($targetDir . DIRECTORY_SEPARATOR . $dbUser['photo'])) {
                            unlink($targetDir . DIRECTORY_SEPARATOR . $dbUser['photo']);
                        }

                        $file    = $this->request->getFile('photo');
                        $newName = 'user_' . $sessionUser['id'] . '_' . $file->getRandomName();
                        $file->move($targetDir, $newName);

                        $model->update($sessionUser['id'], ['photo' => $newName]);
                        $this->refreshSession($model, $sessionUser['id']);
                        $message = 'Profile photo updated successfully.';
                    }
                } elseif ($action === 'remove_photo') {
                    $dbUser    = $model->find($sessionUser['id']);
                    $targetDir = FCPATH . 'uploads/avatars';

                    if (! empty($dbUser['photo']) && is_file($targetDir . DIRECTORY_SEPARATOR . $dbUser['photo'])) {
                        unlink($targetDir . DIRECTORY_SEPARATOR . $dbUser['photo']);
                    }

                    $model->update($sessionUser['id'], ['photo' => null]);
                    $this->refreshSession($model, $sessionUser['id']);
                    $message = 'Profile photo removed.';
                }
            } catch (\Throwable $e) {
                return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/profile');
            }

            if ($error) {
                return $isAjax ? $this->ajaxError($error) : redirect()->to('/profile')->with('flash', ['type' => 'danger', 'msg' => $error]);
            }

            if ($isAjax) {
                return $message ? $this->ajaxSuccess($message) : $this->ajaxError('Unknown action.');
            }

            session()->setFlashdata('flash', ['type' => 'success', 'msg' => $message ?? '']);

            return redirect()->to('/profile');
        }

        return view('pages/shared/profile', [
            'pageTitle' => 'My Profile',
            'profile'   => $model->find($sessionUser['id']),
            'flash'     => session()->getFlashdata('flash'),
        ]);
    }

    private function refreshSession(UserModel $model, int $userId): void
    {
        $fresh = $model->find($userId);
        unset($fresh['password']);
        session()->set('user', $fresh);
    }
}
