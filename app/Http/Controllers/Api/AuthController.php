<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validate([
                'name'     => 'required|string|max:100',
                'email'    => 'required|email|unique:users,email',
                'password' => 'required|min:8|confirmed',
            ]);

            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'code'    => 201,
                'message' => 'Register success',
                'data' => [
                    'user'  => $user,
                    'token' => $token,
                ],
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'code'    => 422,
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Register Error', [
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'code'    => 500,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    public function login(Request $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validate([
                'email'    => 'required|email',
                'password' => 'required',
            ]);

            $user = User::where('email', $data['email'])->first();

            if (! $user || ! Hash::check($data['password'], $user->password)) {
                DB::rollBack();

                return response()->json([
                    'code'    => 401,
                    'message' => 'Email atau password salah',
                ], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'code'    => 200,
                'message' => 'Login success',
                'data' => [
                    'user'  => $user,
                    'token' => $token,
                ],
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'code'    => 422,
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Login Error', [
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'code'    => 500,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    public function me(Request $request)
    {
        return response()->json([
            'code' => 200,
            'data' => $request->user(),
        ]);
    }
}
