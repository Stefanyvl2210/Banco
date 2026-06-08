<?php

namespace App\Http\Controllers;

use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Services\Banking\BankingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
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

    /* Display a listing of the user's accounts.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $accounts = auth()->user()->accounts()->latest()->get();

        return response()->json([
            'data' => AccountResource::collection($accounts),
        ], 200);
    }

    /* Create a new account for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_type' => 'required|string|in:checking,savings',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => $validator->errors()->all(),
                'error_code' => 422,
            ], 422);
        }

        $account = $this->bankingService->createAccount(auth()->user(), strtolower($request->account_type));

        return response()->json([
            'message' => 'Account created successfully',
            'data' => new AccountResource($account),
        ], 201);
    }

    /* Display the specified account if it belongs to the authenticated user.
     *
     * @param  \App\Models\Account  $account
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Account $account)
    {
        if ($account->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        return response()->json([
            'data' => new AccountResource($account),
        ], 200);
    }

    /* Remove the specified account from storage.
     *
     * @param  \App\Models\Account  $account
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Account $account)
    {
        if ($account->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ((float) $account->balance > 0) {
            return response()->json([
                'message' => 'An account with available balance cannot be closed.',
            ], 422);
        }

        $account->delete();

        return response()->json([
            'message' => 'Account closed successfully.',
        ], 200);
    }
}
