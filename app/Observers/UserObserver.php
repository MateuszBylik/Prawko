<?php

namespace App\Observers;

use App\Enums\TransactionType;
use App\Models\User;
use App\Services\WalletService;

class UserObserver
{
    public function __construct(private readonly WalletService $wallet) {}

    /**
     * Każde nowe konto automatycznie dostaje portfel oraz bonus powitalny
     * w kredytach — dzięki temu portfel działa spójnie w webie i w API.
     */
    public function created(User $user): void
    {
        $wallet = $user->wallet()->create(['balance' => 0]);

        $bonus = (int) config('prawko.signup_bonus');
        if ($bonus > 0) {
            $this->wallet->credit(
                $wallet,
                amount: $bonus,
                type: TransactionType::Bonus,
                description: 'Bonus powitalny',
            );
        }
    }
}
