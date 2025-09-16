<?php

namespace App\Http\Controllers;

use App\Http\Requests\Media\UploadPhotoRequest;
use App\Http\Requests\UpdateMePasswordRequest;
use App\Http\Requests\UpdateMeRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use App\Transformers\Me\MeTransformer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use App\Http\Requests\EnrollPhoneEmailRequest;
use App\Http\Requests\VerifyPhoneEmailRequest;
use Illuminate\Support\Facades\Cache;
use App\Jobs\SendOtpEmail;

class MeController extends Controller
{
    public function me(): JsonResponse
    {
        return response()->json(
            fractal(auth()->user(), new MeTransformer())->toArray()['data']
        );
    }

    public function update(UpdateMeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // TODO: Add Transaction with try-catch

        $user = auth()->user();

        $user->update($validated);

        return response()->json(
            fractal($user, new MeTransformer())->toArray()['data']
        );
    }

    public function update_password(UpdateMePasswordRequest $request): JsonResponse
    {
        $user = auth()->user();

        if ($user->password) {
            if (!Hash::check($request->old_password, $user->password)) {
                return response()->json([
                    'errors' => ['old_password' => ['The old password is incorrect']],
                ], 422);
            }
        }

        if ($request->password !== $request->password_confirmation) {
            return response()->json(['message' => 'The new password and password confirmation do not match.'], 400);
        }

        $user->password = Hash::make($request->password);

        $user->save();

        return response()->json(['message' => 'Password updated successfully.']);
    }

    public function setCurrentTimezone(): JsonResponse
    {
        $user = auth()->user();

        $user->update(['current_timezone' => request('timezone')]);

        if ($user->timezone === 'UTC') {
            $user->update(['timezone' => request('timezone')]);
        }

        return response()->json([
            'user' => fractal($user, new MeTransformer())->toArray()['data'],
            'message' => 'Timezone updated successfully.'
        ]);
    }

    public function uploadUserPhoto(UploadPhotoRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (auth()->user()->addMedia($validated['photo'])->toMediaCollection('photo')) {
            return response()->json(['message' => 'Profile photo uploaded successfully.']);
        }
        return response()->json(['message' => 'An error occurred.'], 500);
    }

    public function enroll_user_info(EnrollPhoneEmailRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $rand = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $hash = bcrypt($rand);

        if (!empty($validated['phone'])) {
            Cache::put('user_hash_' . auth()->user()->id, [
                'secret' => $hash,
                'phone' => $validated['phone'],
            ], now()->addMinutes(3));

            auth()->user()->notify(new SendSmsNotification(
                "Your OTP for Vault is $rand."
                , $validated['phone']));
        }
        if (!empty($validated['email'])) {
            Cache::put('user_hash_' . auth()->user()->id, [
                'secret' => $hash,
                'email' => $validated['email'],
            ], now()->addMinutes(3  ));

            SendOtpEmail::dispatch(
                'Email Verification',
                $validated['email'],
                $rand
            );
        }

        return response()->json([
            'message' => 'OTP sent to ' . ($validated['email'] ?? $validated['phone']),
            'sent' => true,
        ]);
    }

    public function verify_user_info(VerifyPhoneEmailRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $data = Cache::get('user_hash_' . auth()->user()->id);

        if (empty($data)) {
            return response()->json([
                'verified' => false,
                'message' => 'OTP has expired.',
            ]);
        }

        if (Hash::check($validated['otp'], $data['secret'])) {
            $staff = auth()->user();

            $staff->email = $data['email'] ?? $staff->email;
            $staff->phone = $data['phone'] ?? $staff->phone;

            $staff->email_verified = now();

            $staff->save();
        }

        return response()->json([
            'verified' => Hash::check($validated['otp'], $data['secret']),
            'me' =>  fractal(auth()->user(), new MeTransformer())->toArray()['data']
        ]);
    }
}
