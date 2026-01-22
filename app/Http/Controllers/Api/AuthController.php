<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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

    public function forgot_password(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email'
            ]);

            $email = $request->email;

            // generate token
            $rawToken = Str::random(64);
            $hashedToken = hash('sha256', $rawToken);

            // simpan / overwrite token
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => $hashedToken,
                    'created_at' => Carbon::now(),
                ]
            );

            // reset link
            $resetUrl = config('app.frontend_url')
                . "/reset-password?token={$rawToken}&email={$email}";

            // kirim email (simple)
            Mail::raw(
                "Klik link berikut untuk reset password:\n\n{$resetUrl}\n\nLink berlaku 15 menit.",
                fn($msg) => $msg
                    ->to($email)
                    ->subject('Reset Password')
            );

            return response()->json([
                'code' => 200,
                'message' => 'Jika email terdaftar, link reset telah dikirim'
            ]);
        } catch (\Exception $e) {
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

    public function reset(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'token' => 'required',
                'password' => 'required|min:8|confirmed',
            ]);

            $record = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (!$record) {
                return response()->json([
                    'code' => 400,
                    'message' => 'Token invalid'
                ], 400);
            }

            // check expiry (15 menit)
            if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
                return response()->json([
                    'code' => 400,
                    'message' => 'Token expired'
                ], 400);
            }

            // verify token
            if (!hash_equals(
                $record->token,
                hash('sha256', $request->token)
            )) {
                return response()->json([
                    'code' => 400,
                    'message' => 'Token invalid'
                ], 400);
            }

            // update password
            $user = User::where('email', $request->email)->firstOrFail();
            $user->password = Hash::make($request->password);
            $user->save();

            // 🔥 revoke ALL access tokens (Billing + Game)
            $user->tokens()->delete();

            // delete reset token
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            return response()->json([
                'code' => 200,
                'message' => 'Password berhasil direset'
            ]);
        } catch (\Exception $e) {
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
}
