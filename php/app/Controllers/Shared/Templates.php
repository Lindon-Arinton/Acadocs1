<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Models\TemplateCategoryModel;
use App\Models\TemplateModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Templates extends BaseController
{
    private const ALLOWED_EXT = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png',
    ];

    public function index()
    {
        $categoryModel = new TemplateCategoryModel();
        $templateModel = new TemplateModel();

        if ($this->request->getMethod() === 'POST') {
            $isAjax = $this->request->isAJAX();

            if (! hasRole('admin', 'adas')) {
                return $isAjax ? $this->ajaxError('You are not authorized to do this.', 403) : redirect()->to('/templates');
            }

            $action  = $this->request->getPost('action');
            $message = null;
            $error   = null;

            try {
                if ($action === 'add_category') {
                    $name = trim($this->request->getPost('name') ?? '');

                    if ($name === '') {
                        $error = 'Please enter a category name.';
                    } elseif ($categoryModel->where('name', $name)->first()) {
                        $error = 'This category already exists.';
                    } else {
                        $categoryModel->insert([
                            'name'       => $name,
                            'created_by' => currentUser()['name'],
                        ]);
                        $message = 'Category added.';
                    }
                } elseif ($action === 'delete_category') {
                    $categoryId = (int) $this->request->getPost('id');

                    if ($templateModel->where('category_id', $categoryId)->countAllResults() > 0) {
                        $error = 'This category still has templates. Remove or reassign them first.';
                    } else {
                        $categoryModel->delete($categoryId);
                        $message = 'Category deleted.';
                    }
                } elseif ($action === 'upload') {
                    $categoryId = (int) $this->request->getPost('category_id');
                    $category   = $categoryModel->find($categoryId);

                    if (! $category) {
                        $error = 'Please choose a valid category.';
                    } else {
                        $rules = [
                            'file' => [
                                'label'  => 'File',
                                'rules'  => 'uploaded[file]|max_size[file,10240]|ext_in[file,' . implode(',', self::ALLOWED_EXT) . ']',
                                'errors' => [
                                    'uploaded' => 'Please choose a file to upload.',
                                    'max_size' => 'File is too large (max 10MB).',
                                    'ext_in'   => 'Unsupported file type.',
                                ],
                            ],
                        ];

                        if (! $this->validate($rules)) {
                            $error = implode(' ', $this->validator->getErrors());
                        } else {
                            $file      = $this->request->getFile('file');
                            $targetDir = WRITEPATH . 'uploads/templates/' . $categoryId;

                            if (! is_dir($targetDir)) {
                                mkdir($targetDir, 0755, true);
                            }

                            $newName = $file->getRandomName();
                            $file->move($targetDir, $newName);

                            $title = trim($this->request->getPost('title') ?? '');

                            $templateModel->insert([
                                'category_id' => $categoryId,
                                'title'       => $title !== '' ? $title : pathinfo($file->getClientName(), PATHINFO_FILENAME),
                                'file_path'   => $targetDir . DIRECTORY_SEPARATOR . $newName,
                                'file_name'   => $file->getClientName(),
                                'file_ext'    => strtolower(pathinfo($file->getClientName(), PATHINFO_EXTENSION)),
                                'file_size'   => $file->getSize(),
                                'uploaded_by' => currentUser()['name'],
                                'date_added'  => date('Y-m-d'),
                            ]);
                            $message = 'Template uploaded successfully.';
                        }
                    }
                } elseif ($action === 'delete_template') {
                    $template = $templateModel->find((int) $this->request->getPost('id'));

                    if ($template) {
                        if (is_file($template['file_path'])) {
                            unlink($template['file_path']);
                        }
                        $templateModel->delete($template['id']);
                    }
                    $message = 'Template deleted.';
                }
            } catch (\Throwable $e) {
                return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/templates');
            }

            if ($error) {
                return $isAjax ? $this->ajaxError($error) : redirect()->to('/templates')->with('flash', ['type' => 'danger', 'msg' => $error]);
            }

            if ($isAjax) {
                return $message ? $this->ajaxSuccess($message) : $this->ajaxError('Unknown action.');
            }

            session()->setFlashdata('flash', ['type' => 'success', 'msg' => $message ?? '']);

            return redirect()->to('/templates');
        }

        $search   = trim($this->request->getGet('q') ?? '');
        $catId    = (int) ($this->request->getGet('category') ?? 0);
        $fileType = $this->request->getGet('type') ?? 'all';
        $sort     = $this->request->getGet('sort') ?? 'date_desc';

        $builder = $templateModel
            ->select('templates.*, template_categories.name AS category_name')
            ->join('template_categories', 'template_categories.id = templates.category_id');

        if ($search !== '') {
            $builder->groupStart()
                ->like('templates.title', $search)
                ->orLike('templates.file_name', $search)
                ->groupEnd();
        }
        if ($catId > 0) {
            $builder->where('templates.category_id', $catId);
        }
        if ($fileType !== 'all') {
            $builder->where('templates.file_ext', $fileType);
        }

        match ($sort) {
            'date_asc' => $builder->orderBy('templates.date_added', 'ASC'),
            'section'  => $builder->orderBy('template_categories.name', 'ASC')->orderBy('templates.title', 'ASC'),
            'type'     => $builder->orderBy('templates.file_ext', 'ASC')->orderBy('templates.title', 'ASC'),
            default    => $builder->orderBy('templates.date_added', 'DESC'),
        };

        $templates = $builder->findAll();

        $categories = $categoryModel->orderBy('name', 'ASC')->findAll();
        foreach ($categories as &$c) {
            $c['count'] = $templateModel->where('category_id', $c['id'])->countAllResults();
        }
        unset($c);

        return view('pages/shared/templates', [
            'pageTitle'  => 'Templates',
            'templates'  => $templates,
            'categories' => $categories,
            'search'     => $search,
            'catId'      => $catId,
            'fileType'   => $fileType,
            'sort'       => $sort,
            'fileTypes'  => $templateModel->distinctExtensions(),
            'flash'      => session()->getFlashdata('flash'),
        ]);
    }

    public function download(int $id)
    {
        $template = (new TemplateModel())->find($id);

        if (! $template || ! is_file($template['file_path'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response->download($template['file_path'], null)->setFileName($template['file_name']);
    }
}
