<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    private const OTP_EXPIRATION_MINUTES = 10;
    private const MAX_LOGIN_ATTEMPTS = 3;
    private const LOGIN_DECAY_SECONDS = 300;
    private const MAX_SEND_ATTEMPTS = 3;
    private const SEND_DECAY_SECONDS = 300;
    private const MAX_VERIFY_ATTEMPTS = 5;
    private const VERIFY_DECAY_SECONDS = 600;

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $email = Str::lower($validated['email']);
        $loginKey = $this->loginThrottleKey($email, $request->ip());

        if (RateLimiter::tooManyAttempts($loginKey, self::MAX_LOGIN_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($loginKey);

            return $this->error(
                'Trop de tentatives de connexion. Réessayez dans '.ceil($seconds / 60).' minute(s).',
                429
            );
        }

        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($loginKey, self::LOGIN_DECAY_SECONDS);

            return $this->error('Identifiants invalides.', 422, [
                'email' => ['Identifiants invalides.'],
            ]);
        }

        if (! $user->estParent()) {
            RateLimiter::hit($loginKey, self::LOGIN_DECAY_SECONDS);

            return $this->error('Ce compte n’est pas un compte parent.', 403);
        }

        if ((bool) $user->is_deleted) {
            RateLimiter::hit($loginKey, self::LOGIN_DECAY_SECONDS);

            return $this->error('Ce compte est désactivé.', 403);
        }

        RateLimiter::clear($loginKey);

        $deviceName = $validated['device_name'] ?? 'mobile-parent';
        $token = $user->createToken($deviceName, ['parent'])->plainTextToken;

        return $this->success('Connexion réussie.', [
            'token_type' => 'Bearer',
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->success('Déconnexion réussie.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success('Profil récupéré avec succès.', [
            'user' => $this->formatUser($request->user()),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return $this->error('Le mot de passe actuel est incorrect.', 422, [
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        $currentToken = $user->currentAccessToken();

        if ($currentToken) {
            $user->tokens()
                ->where('id', '!=', $currentToken->id)
                ->delete();
        }

        return $this->success('Mot de passe modifié avec succès.');
    }

    public function sendPasswordOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $email = Str::lower($validated['email']);
        $sendKey = $this->sendThrottleKey($email, $request->ip());

        if (RateLimiter::tooManyAttempts($sendKey, self::MAX_SEND_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($sendKey);

            return $this->error(
                'Trop de demandes de code. Réessayez dans '.ceil($seconds / 60).' minute(s).',
                429
            );
        }

        RateLimiter::hit($sendKey, self::SEND_DECAY_SECONDS);

        $user = User::where('email', $email)
            ->where('role', 'parent')
            ->where(function ($query) {
                $query->whereNull('is_deleted')->orWhere('is_deleted', false);
            })
            ->first();

        if ($user) {
            $code = (string) random_int(100000, 999999);

            DB::table('password_reset_tokens')->where('email', $email)->delete();
            DB::table('password_reset_tokens')->insert([
                'email' => $email,
                'token' => Hash::make($code),
                'created_at' => now(),
            ]);

            Mail::send('emails.auth.password-otp', [
                'user' => $user,
                'code' => $code,
                'expirationMinutes' => self::OTP_EXPIRATION_MINUTES,
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Code de réinitialisation du mot de passe');
            });
        }

        return $this->success('Si un compte parent existe avec cette adresse, un code OTP a été envoyé par email.');
    }

    public function verifyPasswordOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $email = Str::lower($validated['email']);
        $check = $this->checkOtp($email, $validated['code'], $request->ip());

        if (! $check['valid']) {
            return $this->error($check['message'], $check['status'] ?? 422, [
                'code' => [$check['message']],
            ]);
        }

        return $this->success('Code OTP valide.');
    }

    public function resetPasswordWithOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $email = Str::lower($validated['email']);
        $check = $this->checkOtp($email, $validated['code'], $request->ip());

        if (! $check['valid']) {
            return $this->error($check['message'], $check['status'] ?? 422, [
                'code' => [$check['message']],
            ]);
        }

        $user = User::where('email', $email)
            ->where('role', 'parent')
            ->first();

        if (! $user) {
            return $this->error('Impossible de réinitialiser ce compte.', 422);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));

        DB::table('password_reset_tokens')->where('email', $email)->delete();
        $user->tokens()->delete();
        RateLimiter::clear($this->verifyThrottleKey($email, $request->ip()));

        return $this->success('Mot de passe réinitialisé avec succès. Connectez-vous avec le nouveau mot de passe.');
    }

    private function checkOtp(string $email, string $code, string $ip): array
    {
        $verifyKey = $this->verifyThrottleKey($email, $ip);

        if (RateLimiter::tooManyAttempts($verifyKey, self::MAX_VERIFY_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($verifyKey);

            return [
                'valid' => false,
                'status' => 429,
                'message' => 'Trop de codes incorrects. Réessayez dans '.ceil($seconds / 60).' minute(s).',
            ];
        }

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record) {
            RateLimiter::hit($verifyKey, self::VERIFY_DECAY_SECONDS);

            return [
                'valid' => false,
                'message' => 'Code invalide ou expiré. Demandez un nouveau code.',
            ];
        }

        $createdAt = Carbon::parse($record->created_at);

        if ($createdAt->copy()->addMinutes(self::OTP_EXPIRATION_MINUTES)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            RateLimiter::hit($verifyKey, self::VERIFY_DECAY_SECONDS);

            return [
                'valid' => false,
                'message' => 'Ce code a expiré. Demandez un nouveau code.',
            ];
        }

        if (! Hash::check($code, $record->token)) {
            RateLimiter::hit($verifyKey, self::VERIFY_DECAY_SECONDS);

            return [
                'valid' => false,
                'message' => 'Code OTP incorrect.',
            ];
        }

        RateLimiter::clear($verifyKey);

        return ['valid' => true];
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'nom' => $user->nom,
            'prenom' => $user->prenom,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'sexe' => $user->sexe,
            'adresse' => $user->adresse,
            'role' => $user->role,
        ];
    }

    private function success(string $message, array $data = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => (object) $data,
        ]);
    }

    private function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => (object) $errors,
        ], $status);
    }

    private function loginThrottleKey(string $email, string $ip): string
    {
        return 'api-parent-login|'.$email.'|'.$ip;
    }

    private function sendThrottleKey(string $email, string $ip): string
    {
        return 'api-parent-password-otp-send|'.$email.'|'.$ip;
    }

    private function verifyThrottleKey(string $email, string $ip): string
    {
        return 'api-parent-password-otp-verify|'.$email.'|'.$ip;
    }
}
