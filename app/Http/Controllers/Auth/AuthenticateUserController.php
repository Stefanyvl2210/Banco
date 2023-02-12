<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthenticateUserController extends Controller
{
    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {

        $data = $request->all();
        // $request->authenticate();

        // $request->session()->regenerate();

        // return redirect()->intended(RouteServiceProvider::HOME);

        $validator = Validator::make($data, [
            'email' => 'required|email',
            "password" => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => $validator->errors()->all(),
                'error_code' => 422
            ], 422);
        }

        if (!auth()->attempt($request->only('email', 'password'), $request->remember)) {
            return response()->json([
                'message' => 'Credenciales invalidas',
            ], 200);
        }

        return response()->json([
            'message' => 'Usuario logeado',
            'data' => auth()->user()
        ], 200);
    }
}
