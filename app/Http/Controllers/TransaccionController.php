<?php

namespace App\Http\Controllers;

use App\Models\Cuenta;
use App\Models\Transaccion;
use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

class TransaccionController extends Controller
{
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
            'num_cuenta' => 'required|numeric',
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

        return response()->json([
            'messages' => auth(),

        ], 422);

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
        $account_to_transfer = Cuenta::where('num_cuenta', $data['num_cuenta'])->first();

        if (!$account_to_transfer) {
            return response()->json([
                'messages' => "Cuenta invalida",
                'error_code' => 422
            ], 422);
        }

        if ($data['tipo_cuenta'] !== $account_to_transfer->tipo) {
            return response()->json([
                'messages' => "Tipo de uenta invalida",
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

        $user_amount = $account_to_transfer->saldo;
        $account_to_transfer->update(['saldo' => ($user_amount + $data['cantidad'])]);

        return response()->json([
            'message' => 'Transferencia exitosa',
            'data' => $new_transaction
        ], 200);
    }
}
