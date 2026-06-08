<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\RegisterUserRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Banking\BankingService;

class UserController extends Controller
{
    private $bankingService = null;

    public function __construct(BankingService $bankingService)
    {
        $this->bankingService = $bankingService;
    }

    /**
     * Display a listing of the authenticated user's profile and accounts.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json( [
            'data' => UserResource::collection(User::with('accounts')->latest()->get()),
        ], 200);
    }

    /**
     * Create a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(RegisterUserRequest $request)
    {
        $newUser = $this->bankingService->registerUser($request->validated());
        $newUser->load('accounts');

        return response()->json([
            'message' => 'User created successfully',
            'data' => new UserResource($newUser),
            'token' => $newUser->createToken('API TOKEN')->plainTextToken,
        ], 201);
    }

    /**
    * Update the authenticated user's profile information.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\JsonResponse
    */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = auth()->user();
        $user->update($request->validated());

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => new UserResource($user->load('accounts')),
        ], 200);
    }

    /**
     * Remove the specified user from storage. Only non-admin users can be deleted, and admins cannot delete their own user through this endpoint.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        if ((int) $user->id === (int) auth()->id()) {
            return response()->json([
                'message' => 'Admins cannot delete their own user through this endpoint.',
            ], 422);
        }

        if ($user->isAdmin()) {
            return response()->json([
                'message' => 'Admin users cannot be deleted through this endpoint.',
            ], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'data' => [
                'message' => 'User with id '.$id.' was deleted successfully.',
            ],
        ], 200);
    }
}
