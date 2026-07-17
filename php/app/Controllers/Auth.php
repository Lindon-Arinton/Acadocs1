<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        if (currentUser()) {
            return redirect()->to(currentUser()['role'] === 'teacher' ? '/teacher-dashboard' : '/dashboard');
        }

        $error = '';
        $email = '';

        if ($this->request->getMethod() === 'POST') {
            $email    = trim($this->request->getPost('email') ?? '');
            $password = trim($this->request->getPost('password') ?? '');

            if ($email && $password) {
                $user = (new UserModel())->findByEmail($email);

                if ($user && password_verify($password, $user['password'])) {
                    unset($user['password']);
                    session()->set('user', $user);

                    return redirect()->to($user['role'] === 'teacher' ? '/teacher-dashboard' : '/dashboard');
                }

                $error = 'Invalid email or password. Please try again.';
            } else {
                $error = 'Please enter your email and password.';
            }
        }

        return view('auth/login', ['error' => $error, 'email' => $email]);
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login');
    }
}
