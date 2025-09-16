<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RequestResetPasswordRequest;
use App\Jobs\SendWelcomeEmail;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Transformers\Me\MeTransformer;
use App\Transformers\User\UserTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Jobs\SendOtpEmail;
use App\Http\Requests\VerifyPhoneEmailRequest;
use Illuminate\Support\Facades\Crypt;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if ($user && !$user->password) {
            return response()->json([
                'errors' => ['password' => ['Password not set, use Google to login.']],
            ], 422);
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid Credentials'], 401);
        }

        $rand = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $hash = bcrypt($rand);

        if (!optional($user)->email_verified_at && $user) {
            Cache::put('user_hash_' . $user->id, [
                'secret' => $hash,
                'email' => $user['email'],
            ], now()->addMinutes(3));

            SendOtpEmail::dispatch(
                'Email Verification',
                $user['email'],
                $rand
            );

            $token = Crypt::encryptString(json_encode([
                'id' => $user->id,
                'expires_at' => now()->addMinutes(3)
            ]));
            return response()->json([
                'verification' => true,
                'token' => $token
            ], 200);
        }

        $abilities = [];

        return response()->json([
            'message' => 'Login successful.',
            'accessToken' => $user->createToken($user->fname . '-' . $user->sname . '-AuthToken', $abilities)->plainTextToken,
            'user' => fractal($user, new MeTransformer())->toArray()['data'],
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if ($user) {
            return response()->json([
                'errors' => ['email' => ['User with this email already exists.']],
            ], 422);
        }

        DB::beginTransaction();

        try {
            $newUser = User::create([
                'email' => $validated['email'],
                'fname' => $validated['fname'],
                'sname' => $validated['sname'],
                'password' => Hash::make($validated['password']),
                'marketing_consent' =>  isset($validated['marketing_consent']) ? $validated['marketing_consent'] : false,
                'timezone' => $validated['timezone']
            ]);

            $newUser->defaultAccount()->create([
                'name' => 'Personal',
                'currency' => 'USD',
                'default_account' => true
            ]);

            if (isset($validated['marketing_consent']) && $validated['marketing_consent']) {
                EmailSubscriber::create([
                    'email' => $validated['email'],
                    'fname' => $validated['fname'],
                    'sname' => $validated['sname'],
                    'user_id' => $newUser->id,
                    'discount' => 50 // 50% discount for early access users
                ]);
            }

            $rand = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $hash = bcrypt($rand);

            Cache::put('user_hash_' . $newUser->id, [
                'secret' => $hash,
                'email' => $newUser['email'],
            ], now()->addMinutes(3));

            SendOtpEmail::dispatch(
                'Email Verification',
                $newUser['email'],
                $rand
            );

            $token = Crypt::encryptString(json_encode([
                'id' => $newUser->id,
                'expires_at' => now()->addMinutes(3)
            ]));

            DB::commit();

            SendWelcomeEmail::dispatch(
                'Welcome to Vault!',
                $validated['email'],
                $newUser->fname
            );

            return response()->json([
                'message' => 'Account created.',
                'verification' => true,
                'token' => $token
            ]);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function logout(): JsonResponse
    {
        if (auth()->user()->currentAccessToken()->delete()) {
            return response()->json(['message' => 'Logged out successfully.']);
        }

        return response()->json(['message' => 'Failed to logout.'], 500);
    }

    public function requestResetPassword(RequestResetPasswordRequest $request): JsonResponse
    {
        $email = $request->validated()['email'];
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'No user found with this email address.'
            ], 404);
        }

        $token = Str::random(60);
        Cache::put('password.reset:' . $token, $user->id, 3600);

        $resetUrl = url(config('APP_BASE_URL') . '/password-reset/' . $token . '?email=' . $email);

        $user->notify(new ResetPasswordNotification($token, $resetUrl));

        return response()->json([
            'message' => 'Reset link sent successfully. Please check your email.'
        ], 200);
    }


    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $token = $validated['token'];
        $userId = Cache::get('password.reset:' . $token);

        if (!$userId) {
            return response()->json(['message' => 'Invalid or expired token.'], 422);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Reset the password
        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        // Invalidate the token
        Cache::forget('password.reset:' . $token);

        event(new \Illuminate\Auth\Events\PasswordReset($user));

        // Optionally log the user in and return a token
        return response()->json([
            'message' => 'Password reset successfully. You are now logged in.',
            'accessToken' => $user->createToken($user->getFullNameAttribute() . '-AuthToken', [])->plainTextToken,
            'user' => fractal($user, new UserTransformer())->toArray()['data']
        ]);
    }

    public function verify_user(VerifyPhoneEmailRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $data = json_decode(Crypt::decryptString($request->token), true);
        $user = User::find($data['id']);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $data = Cache::get('user_hash_' . $user->id);
        if (!$data) {
            return response()->json(['message' => 'OTP session expired'], 401);
        }

        if (Hash::check($validated['otp'], $data['secret'])) {
            $user->email = $data['email'] ?? $user->email;
            $user->phone = $data['phone'] ?? $user->phone;
            $user->email_verified_at = now();
            $user->save();

            // Clean up
            Cache::forget('user_hash_' . $user->id);

            AdminUpdated::dispatch();

            return response()->json([
                'verified' => true,
                'me' => fractal($user, new MeTransformer())->toArray()['data']
            ]);
        }

        return response()->json([
            'verified' => false,
            'message' => 'Invalid OTP'
        ], 422);
    }
}
