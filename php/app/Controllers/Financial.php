<?php

namespace App\Controllers;

use App\Models\CanteenRecordModel;
use App\Models\SchoolFundModel;

class Financial extends BaseController
{
    public function index()
    {
        $canteenModel = new CanteenRecordModel();
        $fundModel    = new SchoolFundModel();

        if ($this->request->getMethod() === 'POST') {
            $type = $this->request->getPost('type');

            if ($type === 'canteen' && hasRole('admin', 'canteen')) {
                $canteenModel->insert([
                    'date'              => $this->request->getPost('date'),
                    'description'       => $this->request->getPost('description'),
                    'revenue'           => (float) $this->request->getPost('revenue'),
                    'expenses'          => (float) $this->request->getPost('expenses'),
                    'transaction_count' => (int) $this->request->getPost('transaction_count'),
                ]);
                session()->setFlashdata('flash', ['type' => 'success', 'msg' => 'Canteen record added successfully!']);
            } elseif ($type === 'fund' && hasRole('admin', 'disbursing')) {
                $last    = $fundModel->currentBalance();
                $amount  = -abs((float) $this->request->getPost('amount'));
                $balance = $last + $amount;

                $fundModel->insert([
                    'date'        => $this->request->getPost('date'),
                    'category'    => $this->request->getPost('category'),
                    'description' => $this->request->getPost('description'),
                    'particulars' => $this->request->getPost('particulars') ?? '',
                    'amount'      => $amount,
                    'balance'     => $balance,
                    'prepared_by' => currentUser()['name'],
                ]);
                session()->setFlashdata('flash', ['type' => 'success', 'msg' => 'Fund disbursement record added successfully!']);
            }

            return redirect()->to('/financial-reports');
        }

        $canteenRecords = $canteenModel->orderBy('date', 'ASC')->findAll();
        $fundRecords    = $fundModel->orderBy('date', 'ASC')->findAll();

        $canteenTotals = ['revenue' => 0, 'expenses' => 0, 'netIncome' => 0, 'transactions' => 0];
        foreach ($canteenRecords as $r) {
            $canteenTotals['revenue']      += $r['revenue'];
            $canteenTotals['expenses']     += $r['expenses'];
            $canteenTotals['netIncome']    += $r['net_income'];
            $canteenTotals['transactions'] += $r['transaction_count'];
        }

        $currentBalance = ! empty($fundRecords) ? end($fundRecords)['balance'] : 0;

        return view('pages/financial', [
            'pageTitle'      => 'Financial Reports',
            'canteenRecords' => $canteenRecords,
            'fundRecords'    => $fundRecords,
            'canteenTotals'  => $canteenTotals,
            'currentBalance' => $currentBalance,
            'flash'          => session()->getFlashdata('flash'),
        ]);
    }
}
