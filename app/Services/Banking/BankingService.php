<?php

namespace App\Services\Banking;

use App\Models\Account;
use App\Models\BankTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class BankingService
{
    /* Register a new user and create an initial account for them.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    public function registerUser(array $data)
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $this->createAccount($user, $data['account_type']);

            return $user;
        });
    }

    /* Create a new account for a user.
     *
     * @param  \App\Models\User  $user
     * @param  string  $accountType
     * @return \App\Models\Account
     */
    public function createAccount(User $user, string $accountType)
    {
        return Account::create([
            'user_id' => $user->id,
            'account_number' => $this->generateAccountNumber(),
            'account_type' => $accountType,
            'balance' => '0.00',
        ]);
    }

    /* Handle a deposit transaction for a user's account.
     *
     * @param  \App\Models\User  $user
     * @param  array  $data
     * @return array
     */
    public function deposit(User $user, array $data)
    {
        return DB::transaction(function () use ($user, $data) {
            $account = $this->findOwnedAccountForUpdate($user, $data['account_number'], $data['account_type'] ?? null);
            $amount = $this->toCents($data['amount']);
            $this->ensurePositiveAmount($amount);

            $newBalance = $this->toCents($account->balance) + $amount;
            $account->update(['balance' => $this->fromCents($newBalance)]);

            $transaction = BankTransaction::create([
                'account_id' => $account->id,
                'destination_account_id' => $account->id,
                'transaction_number' => $this->generateTransactionNumber(),
                'transaction_type' => 'deposit',
                'amount' => $this->fromCents($amount),
            ]);

            return [
                'transaction' => $transaction,
                'current_balance' => $this->fromCents($newBalance),
            ];
        });
    }

    /* Handle a withdrawal transaction for a user's account.
     *
     * @param  \App\Models\User  $user
     * @param  array  $data
     * @return array
     */
    public function withdrawal(User $user, array $data)
    {
        return DB::transaction(function () use ($user, $data) {
            $account = $this->findOwnedAccountForUpdate($user, $data['account_number'], $data['account_type'] ?? null);
            $amount = $this->toCents($data['amount']);
            $this->ensurePositiveAmount($amount);

            $currentBalance = $this->toCents($account->balance);

            if ($currentBalance < $amount) {
                throw ValidationException::withMessages([
                    'amount' => ['Insufficient balance. Current balance: '.$this->fromCents($currentBalance)],
                ]);
            }

            $newBalance = $currentBalance - $amount;
            $account->update(['balance' => $this->fromCents($newBalance)]);

            $transaction = BankTransaction::create([
                'account_id' => $account->id,
                'source_account_id' => $account->id,
                'transaction_number' => $this->generateTransactionNumber(),
                'transaction_type' => 'withdrawal',
                'amount' => $this->fromCents($amount),
            ]);

            return [
                'transaction' => $transaction,
                'current_balance' => $this->fromCents($newBalance),
            ];
        });
    }

    /* Handle a transfer transaction between two accounts.
     *
     * @param  \App\Models\User  $user
     * @param  array  $data
     * @return array
     */
    public function transfer(User $user, array $data)
    {
        return DB::transaction(function () use ($user, $data) {
            $sourceNumber = (string) $data['source_account_number'];
            $targetNumber = (string) $data['destination_account_number'];
            $amount = $this->toCents($data['amount']);
            $this->ensurePositiveAmount($amount);

            $accounts = Account::whereIn('account_number', [$sourceNumber, $targetNumber])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy(function ($account) {
                    return (string) $account->account_number;
                });

            $source = $accounts->get($sourceNumber);
            $target = $accounts->get($targetNumber);

            if (!$source || (int) $source->user_id !== (int) $user->id) {
                throw ValidationException::withMessages([
                    'source_account_number' => ['Invalid source account.'],
                ]);
            }

            if (!$target) {
                throw ValidationException::withMessages([
                    'destination_account_number' => ['Invalid destination account.'],
                ]);
            }

            $sourceBalance = $this->toCents($source->balance);

            if ($sourceBalance < $amount) {
                throw ValidationException::withMessages([
                    'amount' => ['Insufficient balance. Current balance: '.$this->fromCents($sourceBalance)],
                ]);
            }

            $targetBalance = $this->toCents($target->balance);
            $newSourceBalance = $sourceBalance - $amount;

            $source->update(['balance' => $this->fromCents($newSourceBalance)]);
            $target->update(['balance' => $this->fromCents($targetBalance + $amount)]);

            $transaction = BankTransaction::create([
                'account_id' => $source->id,
                'source_account_id' => $source->id,
                'destination_account_id' => $target->id,
                'transaction_number' => $this->generateTransactionNumber(),
                'transaction_type' => 'transfer',
                'amount' => $this->fromCents($amount),
            ]);

            return [
                'transaction' => $transaction,
                'current_balance' => $this->fromCents($newSourceBalance),
            ];
        });
    }

    /* Find an account owned by the user and lock it for update.
     *
     * @param  \App\Models\User  $user
     * @param  string  $accountNumber
     * @param  string|null  $accountType
     * @return \App\Models\Account
     */
    private function findOwnedAccountForUpdate(User $user, $accountNumber, ?string $accountType)
    {
        $account = Account::where('account_number', $accountNumber)->lockForUpdate()->first();

        if (!$account || (int) $account->user_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'account_number' => ['Invalid account.'],
            ]);
        }

        if ($accountType && $account->account_type !== $accountType) {
            throw ValidationException::withMessages([
                'account_type' => ['Invalid account type.'],
            ]);
        }

        return $account;
    }

    /* Generate a unique account number.
     *
     * @return string
     */
    private function generateAccountNumber()
    {
        do {
            $number = mt_rand(501878200000000, 501878200099999);
        } while (Account::where('account_number', $number)->exists());

        return $number;
    }

    /* Generate a unique transaction number.
     *
     * @return string
     */
    private function generateTransactionNumber()
    {
        do {
            $number = mt_rand(1000000, 9999999);
        } while (BankTransaction::where('transaction_number', $number)->exists());

        return $number;
    }

    /* Ensure the amount is a positive integer (in cents).
     *
     * @param  int  $amount
     * @return void
     */
    private function ensurePositiveAmount(int $amount)
    {
        if ($amount < 1) {
            throw ValidationException::withMessages([
                'amount' => ['The amount must be greater than 0.'],
            ]);
        }
    }

    /* Convert a decimal amount to cents (integer).
     *
     * @param  string|float  $amount
     * @return int
     */
    private function toCents($amount)
    {
        $value = trim((string) $amount);

        if (!preg_match('/^\d+(\.\d{1,2})?$/', $value)) {
            throw ValidationException::withMessages([
                'amount' => ['The amount must have no more than 2 decimals.'],
            ]);
        }

        [$whole, $decimal] = array_pad(explode('.', $value, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad($decimal, 2, '0');
    }

    /* Convert an amount in cents (integer) back to a decimal string.
     *
     * @param  int  $amount
     * @return string
     */
    private function fromCents(int $amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

}
