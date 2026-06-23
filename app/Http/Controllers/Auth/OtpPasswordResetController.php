<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OtpPasswordResetController extends Controller
{
    private const OTP_EXPIRATION_MINUTES = 10;
    private const MAX_SEND_ATTEMPTS = 3;
    private const SEND_DECAY_SECONDS = 300;
    private const MAX_VERIFY_ATTEMPTS = 5;
    private const VERIFY_DECAY_SECONDS = 600;

    public function create(): View
    {
        return view('auth.forgot-password', [
            'email' => session('password_reset_otp_email'),
            'isVerified' => session()->has('password_reset_otp_verified_email'),
        ]);
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $email = Str::lower($validated['email']);
        $sendKey = $this->sendThrottleKey($email, $request->ip());

        if (RateLimiter::tooManyAttempts($sendKey, self::MAX_SEND_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($sendKey);

            throw ValidationException::withMessages([
                'email' => 'Trop de demandes de code. Réessayez dans '.ceil($seconds / 60).' minute(s).',
            ]);
        }

        RateLimiter::hit($sendKey, self::SEND_DECAY_SECONDS);

        session()->put('password_reset_otp_email', $email);
        session()->forget('password_reset_otp_verified_email');

        $user = User::where('email', $email)->first();

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

        return redirect()
            ->route('password.request')
            ->with('status', 'Si un compte existe avec cette adresse, un code OTP a été envoyé par email.');
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $email = session('password_reset_otp_email');

        if (! $email) {
            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'Veuillez d’abord demander un code OTP.']);
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $verifyKey = $this->verifyThrottleKey($email, $request->ip());

        if (RateLimiter::tooManyAttempts($verifyKey, self::MAX_VERIFY_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($verifyKey);

            throw ValidationException::withMessages([
                'code' => 'Trop de codes incorrects. Réessayez dans '.ceil($seconds / 60).' minute(s).',
            ]);
        }

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record) {
            RateLimiter::hit($verifyKey, self::VERIFY_DECAY_SECONDS);

            return back()->withErrors([
                'code' => 'Code invalide ou expiré. Demandez un nouveau code.',
            ]);
        }

        $createdAt = Carbon::parse($record->created_at);

        if ($createdAt->copy()->addMinutes(self::OTP_EXPIRATION_MINUTES)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            RateLimiter::hit($verifyKey, self::VERIFY_DECAY_SECONDS);

            return back()->withErrors([
                'code' => 'Ce code a expiré. Demandez un nouveau code.',
            ]);
        }

        if (! Hash::check($validated['code'], $record->token)) {
            RateLimiter::hit($verifyKey, self::VERIFY_DECAY_SECONDS);

            return back()->withErrors([
                'code' => 'Code OTP incorrect.',
            ]);
        }

        RateLimiter::clear($verifyKey);
        session()->put('password_reset_otp_verified_email', $email);

        return redirect()
            ->route('password.request')
            ->with('status', 'Code vérifié. Vous pouvez maintenant définir un nouveau mot de passe.');
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $email = session('password_reset_otp_verified_email');

        if (! $email) {
            return redirect()
                ->route('password.request')
                ->withErrors(['code' => 'Veuillez vérifier le code OTP avant de changer le mot de passe.']);
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::where('email', $email)->first();

        if (! $user) {
            session()->forget(['password_reset_otp_email', 'password_reset_otp_verified_email']);

            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'Impossible de réinitialiser ce compte.']);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));

        DB::table('password_reset_tokens')->where('email', $email)->delete();
        session()->forget(['password_reset_otp_email', 'password_reset_otp_verified_email']);

        return redirect()
            ->route('login')
            ->with('status', 'Votre mot de passe a été réinitialisé. Vous pouvez vous connecter.');
    }

    private function sendThrottleKey(string $email, string $ip): string
    {
        return 'password-otp-send|'.$email.'|'.$ip;
    }

    private function verifyThrottleKey(string $email, string $ip): string
    {
        return 'password-otp-verify|'.$email.'|'.$ip;
    }
}
