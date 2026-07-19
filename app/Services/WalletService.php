<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Jedyne wejście do operacji na saldzie portfela. Gwarantuje atomiczność
 * (blokada wiersza) i pełny audyt (każda zmiana salda = jeden wpis w księdze).
 */
class WalletService
{
    /** Uznanie portfela (doładowanie, bonus, zwrot). */
    public function credit(
        Wallet $wallet,
        int $amount,
        TransactionType $type = TransactionType::Topup,
        ?string $description = null,
        array $meta = [],
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Kwota uznania musi być dodatnia.');
        }

        return $this->record($wallet, $amount, $type, $description, $meta);
    }

    /** Obciążenie portfela (zakup / odblokowanie funkcji premium). */
    public function debit(
        Wallet $wallet,
        int $amount,
        TransactionType $type = TransactionType::Purchase,
        ?string $description = null,
        array $meta = [],
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Kwota obciążenia musi być dodatnia.');
        }

        return $this->record($wallet, -$amount, $type, $description, $meta);
    }

    private function record(Wallet $wallet, int $delta, TransactionType $type, ?string $description, array $meta): WalletTransaction
    {
        return DB::transaction(function () use ($wallet, $delta, $type, $description, $meta) {
            // Blokada wiersza portfela — spójne saldo przy równoległych operacjach.
            $locked = Wallet::whereKey($wallet->getKey())->lockForUpdate()->firstOrFail();

            $newBalance = $locked->balance + $delta;

            if ($newBalance < 0) {
                throw new RuntimeException('Niewystarczające saldo portfela.');
            }

            $locked->update(['balance' => $newBalance]);

            $transaction = $locked->transactions()->create([
                'type' => $type,
                'amount' => $delta,
                'balance_after' => $newBalance,
                'description' => $description,
                'meta' => $meta ?: null,
            ]);

            // Zsynchronizuj przekazany obiekt, aby wywołujący widział aktualne saldo.
            $wallet->setAttribute('balance', $newBalance)->syncOriginalAttribute('balance');

            return $transaction;
        });
    }
}
