<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\PasswordResetMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'nationality' => 'nullable|string|max:100',
            'role' => 'nullable|string|in:user,admin',
            'admin_secret' => 'required_if:role,admin|prohibited_unless:role,admin|nullable|string',
        ]);

        $role = 'user';

        if ($request->input('role') === 'admin') {
            $configuredSecret = config('services.admin_register_secret');

            if (! is_string($configuredSecret) || trim($configuredSecret) === '') {
                throw ValidationException::withMessages([
                    'admin_secret' => ['Admin registration is disabled on this server.'],
                ]);
            }

            $provided = (string) $request->input('admin_secret', '');

            if (! hash_equals($configuredSecret, $provided)) {
                throw ValidationException::withMessages([
                    'admin_secret' => ['Invalid admin secret.'],
                ]);
            }

            $role = 'admin';
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'nationality' => $request->nationality,
        ]);
        $user->forceFill(['role' => $role])->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }
        if ($user->role === 'blocked') {
            throw ValidationException::withMessages([
                'email' => ['This account has been blocked.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'nationality' => 'nullable|string|max:100',
            'password' => 'nullable|string|min:8|confirmed',
            'avatar' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048',
        ]);

        $user = $request->user();
        if ($request->filled('name')) {
            $user->name = $request->name;
        }
        if ($request->has('nationality')) {
            $user->nationality = $request->nationality;
        }
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }
        $user->save();

        return response()->json(['user' => $user]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $code, 'created_at' => now()]
        );

        Mail::to($request->email)->send(new PasswordResetMail($code));

        Log::info("Password reset code for {$request->email}: {$code}");

        return response()->json(['message' => 'Reset code sent to your email.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $reset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->code)
            ->where('created_at', '>', now()->subMinutes(60))
            ->first();

        if (! $reset) {
            throw ValidationException::withMessages([
                'code' => ['Invalid or expired reset code.'],
            ]);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password reset successfully.']);
    }
}
