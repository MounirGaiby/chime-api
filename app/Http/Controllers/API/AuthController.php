<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = Auth::login($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'authorization' => [
                        'token' => $token,
                        'type' => 'bearer',
                        'expires_in' => auth()->factory()->getTTL() * 60
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $credentials = $request->only('email', 'password');

            if (!$token = Auth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => Auth::user(),
                    'authorization' => [
                        'token' => $token,
                        'type' => 'bearer',
                        'expires_in' => Auth::factory()->getTTL() * 60
                    ]
                ]
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create token',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function logout()
    {
        try {
            Auth::logout();
            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function refresh()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'user' => Auth::user(),
                    'authorization' => [
                        'token' => Auth::refresh(),
                        'type' => 'bearer',
                        'expires_in' => Auth::factory()->getTTL() * 60
                    ]
                ]
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not refresh token',
            ], 401);
        }
    }
} 