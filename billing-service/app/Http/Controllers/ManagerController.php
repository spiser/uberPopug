<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManagerController extends Controller
{
    public function view(Request $request): View
    {
        if ($request->role === UserRole::worker) {
            throw new \Exception('Вы - Рабочий! Уходите!');
        }

        $balanceToday = Transaction::query()
            ->where('type', TransactionType::task)
            ->where('created_at', '>', Carbon::now()->startOfDay())
            ->selectRaw('(sum(credit) - sum(debit)) AS balance')
            ->get()
            ->first();

        return view('manager.view', [
            'balance' => $balanceToday->balance ?: 0,
        ]);
    }
}
