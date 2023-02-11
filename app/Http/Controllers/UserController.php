<?php

namespace App\Http\Controllers;

use App\Models\Cuenta;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $allUsers = User::all();
        foreach ($allUsers as $user) {
            $account = $user->cuentas;
        }
        return response()->json( [
            'data' => $allUsers
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $data['tipo_cuenta'] = isset($data['tipo_cuenta']) ? strtolower($data['tipo_cuenta']) : null;

        $validator = Validator::make($data, [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'telefono' => 'sometimes|string',
            'email' => 'required|string|unique:users',
            'password' => 'required|string',
            'tipo_cuenta' => 'required|string|in:corriente,ahorros',
        ]);
 
        if ($validator->fails()) {
            return response()->json([
                'messages' => $validator->errors()->all(),
                'error_code' => 422
            ], 422 );
        }

        try {
            $data['password'] = Hash::make($data['password']);
            do {
                $numAccount = mt_rand(501878200000000,501878200099999);
                $accountExists = Cuenta::where('num_cuenta', $numAccount)->count();

            } while($accountExists);

            $newUser = User::create($data);
            $dataAccount = [
                'user_id' => $newUser['id'],
                'num_cuenta' => $numAccount,
                'tipo' => $data['tipo_cuenta'],
                'saldo' => 0
            ];

            $newAccount = Cuenta::create($dataAccount);
            $response = array_merge($data, $dataAccount);

        } catch (\Throwable $e) {
            return response($e, 500);
        }

        return response()->json([
            'message' => 'Usuario creado con exito',
            'data' => $response
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = User::find($id);
        if(!$user)
            return response()->json(['Error' => "Persona con id ". $id ." no existe."], 404);

        try {
            $delete = $user->delete();
        } catch (\Throwable $e) {
            return response()->json($e, 500);
        }

        return response()->json([
            'data' => [
                'message' => 'Persona con id '.$id.' fue eliminada con exito.'
            ]
        ], 200);
    }
}
