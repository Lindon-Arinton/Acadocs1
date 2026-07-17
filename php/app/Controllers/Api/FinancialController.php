<?php

namespace App\Controllers\Api;

use App\Models\CanteenRecordModel;
use App\Models\SchoolFundModel;

class FinancialController extends BaseApiController
{
    public function index()
    {
        $type = $this->request->getGet('type') ?? 'canteen';

        if ($type === 'canteen') {
            $model = new CanteenRecordModel();

            return $this->jsonResponse([
                'records' => $model->orderBy('date', 'DESC')->findAll(),
                'summary' => $model->totals(),
            ]);
        }

        if ($type === 'funds') {
            $model   = new SchoolFundModel();
            $rows    = $model->orderBy('date', 'DESC')->findAll();
            $current = end($rows);

            return $this->jsonResponse([
                'records'         => $rows,
                'current_balance' => $current['balance'] ?? 0,
            ]);
        }

        return $this->jsonError('Method not allowed.', 405);
    }

    public function create()
    {
        $type = $this->request->getGet('type') ?? 'canteen';
        $b    = $this->body();

        if ($type === 'canteen') {
            $id = (new CanteenRecordModel())->insert([
                'date'              => $b['date'],
                'description'       => $b['description'],
                'revenue'           => $b['revenue'],
                'expenses'          => $b['expenses'],
                'transaction_count' => $b['transaction_count'] ?? 0,
            ]);

            return $this->jsonResponse(['id' => $id, 'message' => 'Created.'], 201);
        }

        if ($type === 'funds') {
            $fundModel = new SchoolFundModel();
            $balance   = $fundModel->currentBalance() + (float) $b['amount'];

            $id = $fundModel->insert([
                'date'        => $b['date'],
                'category'    => $b['category'],
                'description' => $b['description'],
                'particulars' => $b['particulars'] ?? '',
                'amount'      => $b['amount'],
                'balance'     => $balance,
                'prepared_by' => $b['prepared_by'] ?? '',
            ]);

            return $this->jsonResponse(['id' => $id, 'balance' => $balance, 'message' => 'Created.'], 201);
        }

        return $this->jsonError('Method not allowed.', 405);
    }

    public function delete()
    {
        $type = $this->request->getGet('type') ?? 'canteen';
        $id   = (int) $this->request->getGet('id');

        if (! $id) {
            return $this->jsonError('Method not allowed.', 405);
        }

        if ($type === 'canteen') {
            (new CanteenRecordModel())->delete($id);

            return $this->jsonResponse(['message' => 'Deleted.']);
        }

        return $this->jsonError('Method not allowed.', 405);
    }
}
