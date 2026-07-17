<?php

namespace App\Controllers;

use App\Models\UserModel;

class Users extends BaseController
{
    public function index()
    {
        if (! hasRole('admin')) {
            return redirect()->to('/dashboard');
        }

        $model = new UserModel();

        if ($this->request->getMethod() === 'POST') {
            $action = $this->request->getPost('action');

            if ($action === 'add') {
                $model->insert([
                    'name'     => $this->request->getPost('name'),
                    'email'    => $this->request->getPost('email'),
                    'password' => password_hash($this->request->getPost('password'), PASSWORD_BCRYPT),
                    'role'     => $this->request->getPost('role'),
                ]);
                session()->setFlashdata('flash', ['type' => 'success', 'msg' => 'User created successfully.']);
            } elseif ($action === 'delete' && (int) $this->request->getPost('id') !== (int) currentUser()['id']) {
                $model->delete((int) $this->request->getPost('id'));
                session()->setFlashdata('flash', ['type' => 'success', 'msg' => 'User deleted.']);
            } elseif ($action === 'reset_pw') {
                $model->update((int) $this->request->getPost('id'), [
                    'password' => password_hash($this->request->getPost('new_password'), PASSWORD_BCRYPT),
                ]);
                session()->setFlashdata('flash', ['type' => 'success', 'msg' => 'Password reset successfully.']);
            }

            return redirect()->to('/users');
        }

        $search = trim($this->request->getGet('q') ?? '');
        $users = $model->select('id,name,email,role,created_at')
            ->groupStart()
                ->like('name', $search)
                ->orLike('email', $search)
            ->groupEnd()
            ->orderBy('role')->orderBy('name')
            ->findAll();

        return view('pages/users', [
            'pageTitle' => 'User Management',
            'users'     => $users,
            'search'    => $search,
            'flash'     => session()->getFlashdata('flash'),
        ]);
    }
}
