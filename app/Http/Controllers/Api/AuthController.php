<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a user and issue a Bearer token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        $token = $user->createToken(
            $request->validated('device_name', 'react-vite'),
            ['*'],
        )->plainTextToken;

        return response()->json([
            'message' => 'Usuario registrado correctamente.',
            'token_type' => 'Bearer',
            'token' => $token,
            'user' => $this->userData($user),
        ], Response::HTTP_CREATED);
    }

    /**
     * Validate credentials and issue a Bearer token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('email', $request->validated('email'))
            ->first();

        if (! $user || ! Hash::check(
            $request->validated('password'),
            $user->password,
        )) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $token = $user->createToken(
            $request->validated('device_name', 'react-vite'),
            ['*'],
        )->plainTextToken;

        return response()->json([
            'message' => 'Sesión iniciada correctamente.',
            'token_type' => 'Bearer',
            'token' => $token,
            'user' => $this->userData($user),
        ]);
    }

    /**
     * Revoke only the Bearer token used by the current request.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    /**
     * Return only the public user fields needed by the frontend.
     *
     * @return array{id: int, name: string, email: string}
     */
    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
