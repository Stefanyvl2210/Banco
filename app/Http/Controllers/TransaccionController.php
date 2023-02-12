<?php

namespace App\Http\Controllers;

use App\Models\Cuenta;
use App\Models\Transaccion;
use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

class TransaccionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'num_cuenta_receptora' => 'required|numeric',
            'num_cuenta_emisora' => 'required|numeric',
            'cantidad' => 'required|numeric',
            'tipo' => 'required|string',
            'tipo_cuenta' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => $validator->errors()->all(),
                'error_code' => 422
            ], 422);
        }

        if (!strcmp($data['tipo'], 'transferencia')) {

            return $this->storeUserTransaction($data);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Transaccion  $transaccion
     * @return \Illuminate\Http\Response
     */
    public function show(Transaccion $transaccion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Transaccion  $transaccion
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Transaccion $transaccion)
    {
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Transaccion  $transaccion
     * @return \Illuminate\Http\Response
     */
    public function destroy(Transaccion $transaccion)
    {
        //
    }

    /**
     * Stores a transaction to another user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeUserTransaction($data)
    {
        $account_to_transfer = Cuenta::where('num_cuenta', $data['num_cuenta_receptora'])->first();

        $user_account = Cuenta::where('num_cuenta', $data['num_cuenta_emisora'])->first();

        if (!$user_account) {
            return response()->json([
                'messages' => "Cuenta no encontrada",
                'error_code' => 422
            ], 422);
        }

        if (!$account_to_transfer) {
            return response()->json([
                'messages' => "Cuenta invalida",
                'error_code' => 422
            ], 422);
        }

        if ($data['tipo_cuenta'] !== $account_to_transfer->tipo) {
            return response()->json([
                'messages' => "Tipo de cuenta invalida",
                'error_code' => 422
            ], 422);
        }

        if ($data['cantidad'] < 1) {
            return response()->json([
                'messages' => "El monto debe ser mayor a 0",
                'error_code' => 422
            ], 422);
        }

        if ($user_account->saldo < $data['cantidad']) {
            return response()->json([
                'messages' => "Saldo insuficiente. " . "Saldo actual: " . $user_account->saldo,
                'error_code' => 422
            ], 422);
        }

        $new_transaction = [
            'cuenta_id' => $account_to_transfer->id,
            'num_transaccion' => mt_rand(1000000, 9999999),
            'tipo' => 'transferencia',
            'cantidad' => $data['cantidad'],
        ];

        $create_transaction = Transaccion::create($new_transaction);

        $transfer_amount = $account_to_transfer->saldo;
        $user_amount = $user_account->saldo;

        $account_to_transfer->update(['saldo' => ($transfer_amount + $data['cantidad'])]);
        $user_account->update(['saldo' => ($user_amount - $data['cantidad'])]);

        return response()->json([
            'message' => 'Transferencia exitosa',
            'data' => [
                'Transferencia' => $new_transaction,
                'Saldo actual' => $user_account->saldo
            ]
        ], 200);
    }
}
