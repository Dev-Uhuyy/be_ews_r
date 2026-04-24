<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware untuk validasi token dari STI-API
 *
 * Karena sti-api dan be_ews_r menggunakan database yang sama,
 * token yang dibuat oleh sti-api tersimpan di tabel personal_access_tokens
 * yang sama. Middleware ini akan memvalidasi token tersebut.
 *
 * Sanctum plainTextToken format: "token_id|token_identifier"
 * - token_id: primary key of personal_access_tokens table
 * - token_identifier: random string that is SHA256 hashed before storing in DB
 *
 * Validation process:
 * 1. Parse token to get token_id and token_identifier
 * 2. Hash token_identifier with SHA256
 * 3. Look up in DB by (id = token_id, token = sha256(token_identifier))
 */
class ValidateStiApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->getTokenFromRequest($request);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token not provided',
                'data' => null,
            ], 401);
        }

        $tokenData = null;

        // Sanctum plainTextToken format is "token_id|token_identifier"
        // The token_identifier is hashed with SHA256 before storing in DB
        if (str_contains($token, '|')) {
            $parts = explode('|', $token);
            $tokenId = $parts[0] ?? null;
            $tokenIdentifier = $parts[1] ?? null;

            if ($tokenId && $tokenIdentifier) {
                // Hash the token_identifier with SHA256 (same as Sanctum does)
                $hashedToken = hash('sha256', $tokenIdentifier);

                // Find by token_id AND hashed token
                $tokenData = DB::table('personal_access_tokens')
                    ->where('id', $tokenId)
                    ->where('token', $hashedToken)
                    ->first();
            }
        } else {
            // Raw token (already hashed) - find by token field
            $tokenData = DB::table('personal_access_tokens')
                ->where('token', $token)
                ->first();
        }

        if (!$tokenData) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token',
                'data' => null,
            ], 401);
        }

        // Get the user associated with the token
        $user = User::find($tokenData->tokenable_id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'data' => null,
            ], 401);
        }

        // Set the authenticated user for the request
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Manually set the user on the auth guard (for Sanctum compatibility)
        auth()->setUser($user);

        return $next($request);
    }

    /**
     * Extract the bearer token from the request.
     * Supports both Authorization header and query parameter 'token'
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function getTokenFromRequest(Request $request): ?string
    {
        // Check Authorization header first
        $header = $request->header('Authorization', '');
        if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return $matches[1];
        }

        // Fallback: check query parameter 'token'
        $tokenParam = $request->query('token', '');
        if (!empty($tokenParam)) {
            return $tokenParam;
        }

        return null;
    }
}