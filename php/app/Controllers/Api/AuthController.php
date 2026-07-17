<?php

namespace App\Controllers\Api;

use App\Models\UserModel;

class AuthController extends BaseApiController
{
    public function login()
    {
        $b        = $this->body();
        $email    = trim($b['email'] ?? '');
        $password = trim($b['password'] ?? '');

        if (! $email || ! $password) {
            return $this->jsonError('Email and password required.');
        }

        $user = (new UserModel())->findByEmail($email);
        if (! $user || ! password_verify($password, $user['password'])) {
            return $this->jsonError('Invalid credentials.', 401);
        }

        unset($user['password']);
        session()->set('user', $user);

        return $this->jsonResponse(['message' => 'OK', 'user' => $user]);
    }

    public function logout()
    {
        session()->destroy();

        return $this->jsonResponse(['message' => 'Logged out.']);
    }

    public function me()
    {
        $user = currentUser();

        return $user ? $this->jsonResponse($user) : $this->jsonError('Unauthenticated.', 401);
    }
}
