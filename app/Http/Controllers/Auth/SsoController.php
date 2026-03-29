<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SsoAuthorizationCode;
use App\Models\SsoClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    public function authorize(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'string'],
            'redirect_uri' => ['required', 'url'],
            'state' => ['nullable', 'string', 'max:2048'],
        ]);

        $client = SsoClient::query()
            ->where('client_id', $validated['client_id'])
            ->where('is_active', true)
            ->first();

        if (! $client) {
            return response()->json(['message' => 'Invalid client.'], 422);
        }

        if (! $client->canRedirectTo($validated['redirect_uri'])) {
            return response()->json(['message' => 'Invalid redirect URI.'], 422);
        }

        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'next' => $request->fullUrl(),
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json(['message' => 'Your account is inactive.'], 403);
        }

        $plainCode = Str::random(96);

        SsoAuthorizationCode::query()->create([
            'user_id' => $user->id,
            'sso_client_id' => $client->id,
            'code_hash' => hash('sha256', $plainCode),
            'redirect_uri' => $validated['redirect_uri'],
            'expires_at' => Carbon::now()->addMinutes(2),
        ]);

        $query = [
            'code' => $plainCode,
        ];

        if (! empty($validated['state'])) {
            $query['state'] = $validated['state'];
        }

        $separator = str_contains($validated['redirect_uri'], '?') ? '&' : '?';

        return redirect()->away($validated['redirect_uri'].$separator.http_build_query($query));
    }

    public function token(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'grant_type' => ['required', 'in:authorization_code'],
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
            'code' => ['required', 'string'],
            'redirect_uri' => ['required', 'url'],
        ]);

        $client = SsoClient::query()
            ->where('client_id', $validated['client_id'])
            ->where('is_active', true)
            ->first();

        if (! $client || ! Hash::check($validated['client_secret'], $client->client_secret)) {
            return response()->json(['message' => 'Invalid client credentials.'], 401);
        }

        if (! $client->canRedirectTo($validated['redirect_uri'])) {
            return response()->json(['message' => 'Invalid redirect URI.'], 422);
        }

        $code = SsoAuthorizationCode::query()
            ->where('sso_client_id', $client->id)
            ->where('redirect_uri', $validated['redirect_uri'])
            ->where('code_hash', hash('sha256', $validated['code']))
            ->first();

        if (! $code || $code->used_at || $code->expires_at->isPast()) {
            return response()->json(['message' => 'Invalid or expired authorization code.'], 422);
        }

        $code->update([
            'used_at' => now(),
        ]);

        $expiration = config('sanctum.expiration');
        $expiresAt = $expiration ? now()->addMinutes((int) $expiration) : null;

        $token = $code->user->createToken(
            name: 'sso:'.$client->name,
            abilities: ['*'],
            expiresAt: $expiresAt,
        );

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token->plainTextToken,
            'expires_at' => $expiresAt?->toIso8601String(),
            'user' => [
                'id' => $code->user->id,
                'name' => $code->user->name,
                'email' => $code->user->email,
                'status' => $code->user->status,
            ],
        ]);
    }
}
