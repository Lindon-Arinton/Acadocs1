<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

abstract class BaseApiController extends BaseController
{
    protected function jsonResponse($data, int $status = 200): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON($data);
    }

    protected function jsonError(string $message, int $status = 400): ResponseInterface
    {
        return $this->jsonResponse(['error' => $message], $status);
    }

    protected function body(): array
    {
        return $this->request->getJSON(true) ?? [];
    }
}
