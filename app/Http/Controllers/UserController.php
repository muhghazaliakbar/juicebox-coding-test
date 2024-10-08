<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Display the specified user.
     */
    public function show(int $id): UserResource|JsonResponse
    {
        try {
            // Find the user by ID or fail with ModelNotFoundException
            $user = User::query()->findOrFail($id);

            // Return the user data using UserResource for consistent formatting
            return new UserResource($user);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }
    }
}
