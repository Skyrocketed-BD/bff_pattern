<?php

namespace App\Http\Controllers;

use App\Models\finance\GeneralLedger;

class FinanceController extends Controller
{
    protected string $connection = 'finance';

    /**
     * Check if transaction is closed
     */
    protected function isTransactionClosed(string $transactionNumber): bool
    {
        return GeneralLedger::on($this->connection)
            ->where('transaction_number', $transactionNumber)
            ->where('closed', '1')
            ->exists();
    }
}
