<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class SsoController extends Controller
{
    public function generate(Request $request)
    {
        try {
            $user = $request->user();
            // GENERATE TOKEN DAN KITA SET EXPIRED NYA JADI 60 DETIK
            $payload = [
                'sub' => $user->id,
                'email' => $user->email,
                'iss' => 'billing-panel',
                'aud' => 'game-panel',
                'iat' => time(),
                'exp' => time() + 60, // 60 detik
                'jti' => uniqid('sso_', true),
            ];

            $token = JWT::encode(
                $payload,
                config('app.key'),
                'HS256'
            );

            // simpan jti → one-time token
            Cache::put(
                'sso:' . $payload['jti'],
                true,
                60
            );

            return response()->json([
                'code' => 200,
                'data' => [
                    'token' => $token,
                    'url' => config('global.game_panel_url')
                ],
            ]);

        } catch (Throwable $e) {
            $message = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];

            Log::critical($message);
            return response()->json([
                'code' => 500,
                'message' => 'SSO token error',
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $token = $request->token;

            if (!$token) {
                return response()->json([
                    'code' => 400,
                    'message' => 'Token required',
                ], 400);
            }

            $decoded = JWT::decode(
                $token,
                new Key(config('app.key'), 'HS256')
            );

            // one-time token check
            if (!Cache::pull('sso:' . $decoded->jti)) {
                return response()->json([
                    'code' => 401,
                    'message' => 'SSO token expired or invalid',
                ], 401);
            }

            $user = User::find($decoded->sub);

            if (!$user) {
                return response()->json([
                    'code' => 404,
                    'message' => 'User not found',
                ], 404);
            }

            // ISSUE ACCESS TOKEN UNTUK GAME PANEL
            $gameToken = $user->createToken(
                'game-panel'
            )->plainTextToken;

            return response()->json([
                'code' => 200,
                'message' => 'SSO login success',
                'data' => [
                    'access_token' => $gameToken,
                    'token_type' => 'Bearer',
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'code' => 401,
                'message' => 'SSO login failed',
            ], 401);
        }
    }
}
