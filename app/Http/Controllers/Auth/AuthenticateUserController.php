<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Http\Controllers\Controller;
use App\Models\User;

class AuthenticateUserController extends Controller
{
    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(LoginRequest $request)
    {
        if (!auth()->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = User::where('email', $request->email)->with('accounts')->first();

        return response()->json([
            'message' => 'User logged in',
            'data' => new UserResource($user),
            'token' => $user->createToken('API TOKEN')->plainTextToken,
        ], 200);
    }

    /* Get the authenticated user's information.
     *
     * @return \Illuminate\Http\JsonResponse
    */
    public function me()
    {
        return response()->json([
            'data' => new UserResource(auth()->user()->load('accounts')),
        ], 200);
    }

    /**
     * Handle an incoming logout request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy()
    {
        auth()->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Session closed',
        ], 200);
    }
}
