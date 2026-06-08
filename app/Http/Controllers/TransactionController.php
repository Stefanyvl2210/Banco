<?php

namespace App\Http\Controllers;

use App\Http\Requests\Banking\DepositRequest;
use App\Http\Requests\Banking\TransferRequest;
use App\Http\Requests\Banking\WithdrawalRequest;
use App\Http\Resources\BankTransactionResource;
use App\Models\BankTransaction;
use App\Services\Banking\BankingService;

class TransactionController extends Controller
{
    private $bankingService = null;

    /* Inject the BankingService into the controller.
     *
     * @param  \App\Services\Banking\BankingService  $bankingService
     * @return void
     */
    public function __construct(BankingService $bankingService)
    {
        $this->bankingService = $bankingService;
    }

    /* Display a listing of the authenticated user's transactions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $accountIds = auth()->user()->accounts()->pluck('id');

        $transactions = BankTransaction::whereIn('account_id', $accountIds)
            ->orWhereIn('source_account_id', $accountIds)
            ->orWhereIn('destination_account_id', $accountIds)
            ->latest()
            ->paginate(15);

        return BankTransactionResource::collection($transactions);
    }

    /* Create a new transaction for the authenticated user.
     *
     * @param  \App\Http\Requests\Banking\TransferRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(TransferRequest $request)
    {
        $result = $this->bankingService->transfer(auth()->user(), $request->validated());

        return response()->json([
            'message' => 'Transfer completed successfully',
            'data' => [
                'transfer' => new BankTransactionResource($result['transaction']),
                'current_balance' => $result['current_balance'],
            ],
        ], 200);
    }

    /* Handle a deposit transaction for the authenticated user.
     *
     * @param  \App\Http\Requests\Banking\DepositRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deposit(DepositRequest $request)
    {
        $result = $this->bankingService->deposit(auth()->user(), $request->validated());

        return response()->json([
            'message' => 'Deposit completed successfully',
            'data' => [
                'deposit' => new BankTransactionResource($result['transaction']),
                'current_balance' => $result['current_balance'],
            ],
        ], 200);
    }

    /* Handle a withdrawal transaction for the authenticated user.
     *
     * @param  \App\Http\Requests\Banking\WithdrawalRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function withdrawal(WithdrawalRequest $request)
    {
        $result = $this->bankingService->withdrawal(auth()->user(), $request->validated());

        return response()->json([
            'message' => 'Withdrawal completed successfully',
            'data' => [
                'withdrawal' => new BankTransactionResource($result['transaction']),
                'current_balance' => $result['current_balance'],
            ],
        ], 200);
    }
}
