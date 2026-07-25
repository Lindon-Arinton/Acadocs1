<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Users extends BaseController
{
    public function index()
    {
        if (! hasRole('admin', 'adas')) {
            return redirect()->to('/dashboard');
        }

        $model = new UserModel();

        if ($this->request->getMethod() === 'POST') {
            $action  = $this->request->getPost('action');
            $isAjax  = $this->request->isAJAX();
            $message = null;

            try {
                if ($action === 'add') {
                    $model->insert([
                        'name'     => $this->request->getPost('name'),
                        'email'    => $this->request->getPost('email'),
                        'password' => password_hash($this->request->getPost('password'), PASSWORD_BCRYPT),
                        'role'     => $this->request->getPost('role'),
                    ]);
                    $message = 'User created successfully.';
                } elseif ($action === 'delete') {
                    if ((int) $this->request->getPost('id') === (int) currentUser()['id']) {
                        return $isAjax ? $this->ajaxError('You cannot delete your own account.') : redirect()->to('/users');
                    }
                    $model->delete((int) $this->request->getPost('id'));
                    $message = 'User deleted.';
                } elseif ($action === 'reset_pw') {
                    $model->update((int) $this->request->getPost('id'), [
                        'password' => password_hash($this->request->getPost('new_password'), PASSWORD_BCRYPT),
                    ]);
                    $message = 'Password reset successfully.';
                }
            } catch (\Throwable $e) {
                return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/users');
            }

            if ($isAjax) {
                return $message ? $this->ajaxSuccess($message) : $this->ajaxError('Unknown action.');
            }

            session()->setFlashdata('flash', ['type' => 'success', 'msg' => $message ?? '']);

            return redirect()->to('/users');
        }

        $search = trim($this->request->getGet('q') ?? '');
        $sort   = $this->request->getGet('sort') ?? 'role';

        $builder = $model->select('id,name,email,role,created_at')
            ->groupStart()
                ->like('name', $search)
                ->orLike('email', $search)
            ->groupEnd();

        match ($sort) {
            'name_az' => $builder->orderBy('name', 'ASC'),
            'newest'  => $builder->orderBy('created_at', 'DESC'),
            'oldest'  => $builder->orderBy('created_at', 'ASC'),
            default   => $builder->orderBy('role', 'ASC')->orderBy('name', 'ASC'),
        };

        $users = $builder->findAll();

        return view('pages/admin/users', [
            'pageTitle' => 'User Management',
            'users'     => $users,
            'search'    => $search,
            'sort'      => $sort,
            'flash'     => session()->getFlashdata('flash'),
        ]);
    }
}
