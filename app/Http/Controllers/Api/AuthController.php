<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /**
     * Register a user and authenticate the first-party SPA by session.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        if ($request->hasSession()) {
            Auth::guard('web')->login($user);
            $request->session()->regenerate();

            return response()->json([
                'message' => 'Usuario registrado correctamente.',
                'user' => $this->userData($user),
            ], Response::HTTP_CREATED);
        }

        // Keep personal access tokens available to non-browser API clients.
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
     * Authenticate the first-party SPA with a session, or issue a token to API clients.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if ($request->hasSession()) {
            if (! Auth::guard('web')->attempt(
                $request->safe()->only(['email', 'password']),
            )) {
                throw ValidationException::withMessages([
                    'email' => ['Las credenciales proporcionadas son incorrectas.'],
                ]);
            }

            $request->session()->regenerate();

            return response()->json([
                'message' => 'Sesión iniciada correctamente.',
                'user' => $this->userData(Auth::guard('web')->user()),
            ]);
        }

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
     * Revoke the current API token or destroy the authenticated browser session.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    /**
     * Return the authenticated user for restoring a browser session or API token.
     */
    public function currentUser(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userData($request->user()),
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
