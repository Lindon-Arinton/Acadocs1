<?php

namespace App\Controllers\Api;

use App\Models\DocumentLinkModel;

class DocumentLinksController extends BaseApiController
{
    public function index()
    {
        $model    = new DocumentLinkModel();
        $category = $this->request->getGet('category');

        $builder = $model->orderBy('date_added', 'DESC');
        if ($category) {
            $builder->where('category', $category);
        }

        return $this->jsonResponse($builder->findAll());
    }

    public function create()
    {
        $b = $this->body();

        $id = (new DocumentLinkModel())->insert([
            'category'     => $b['category'] ?? 'Forms',
            'title'        => $b['title'] ?? '',
            'description'  => $b['description'] ?? '',
            'url'          => $b['url'] ?? '',
            'added_by'     => $b['added_by'] ?? '',
            'date_added'   => $b['date_added'] ?? date('Y-m-d'),
            'access_level' => $b['access_level'] ?? 'All Users',
        ]);

        return $this->jsonResponse(['id' => $id, 'message' => 'Created.'], 201);
    }

    public function delete()
    {
        $id = (int) $this->request->getGet('id');
        if (! $id) {
            return $this->jsonError('Method not allowed.', 405);
        }

        (new DocumentLinkModel())->delete($id);

        return $this->jsonResponse(['message' => 'Deleted.']);
    }
}
