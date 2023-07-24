<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

class AuthController extends Controller
{
    /**
     * Login a user with email and get token
     *
     * @throws ValidationException
     */
    public function login(Request $request): mixed
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);
        $user = User::query()->where('email', $request->get('email'))->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ["We can't find a user with that email address."],
            ]);
        }

        if ($user instanceof MustVerifyEmail && !$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Your email address is not verified.',
            ], 403);
        }
        if (!Hash::check($request->get('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }
        $token = $user->createToken('web')->plainTextToken;
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => '',
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|max:255',
            'password' => 'required|min:6',
        ]);
        // begin database transaction
        DB::beginTransaction();
        try {
            $data = $request->all();
            $user = User::updateOrCreate([
                'email' => $data['email'],
            ], [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => $request->password,
            ]);
            $user->assignRole('subscriber');
            event(new Registered($user));

            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => 'Account successfully created please verify your email',
            ], 201);
        } catch (Throwable $exception) {
            // log exception
            report($exception);
            // rollback database
            DB::rollBack();
            // return failed message
            return response()->json([
                'message' => $exception->getMessage(),
            ], 400);
        }
    }

    /**
     * Check is email or mobile unique in database.
     */
    public function checkIsEmailMobileExist(Request $request): JsonResponse
    {
        $user_id = $request->query('user_id');
        $field = $request->query('username') ?? $request->query('email') ?? $request->query('mobile');
        $queries = collect([]);
        foreach ($request->query() as $key => $value) {
            $queries->push($key);
        }
        $name = $queries[1];
        $user = User::find($user_id);
        if ($user) {
            if ($user->$name === $field) {
                return response()->json([
                    'message' => "The $name is available.",
                    'valid' => true,
                ]);
            } else {
                if (User::where($name, '=', $field)->exists()) {
                    return response()->json([
                        'message' => "The $name has already taken.",
                        'valid' => false,
                    ]);
                } else {
                    return response()->json([
                        'message' => "The $name is available.",
                        'valid' => true,
                    ]);
                }
            }
        } else {
            if (User::where($name, '=', $field)->exists()) {
                return response()->json([
                    'message' => "The $name has already taken.",
                    'valid' => false,
                ]);
            } else {
                return response()->json([
                    'message' => "The $name is available.",
                    'valid' => true,
                ]);
            }
        }
    }

    /**
     * Get auth user response.
     */
    public function me(): JsonResponse
    {
        return response()->json(Auth::user());
    }

    /**
     * Log the user out (Invalidate the token).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);
        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            return $status === Password::RESET_LINK_SENT
                ? response()->json([
                    'message' => __($status),
                ])
                : response()->json([
                    'message' => __($status),
                ], 400);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => Lang::get('auth.error'),
                'error' => $exception->getMessage(),
            ], 400);
        }
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);
        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->forceFill([
                        'password' => bcrypt($password),
                    ])->setRememberToken(Str::random(60));
                    $user->save();
                    event(new PasswordReset($user));
                }
            );

            return $status === Password::PASSWORD_RESET
                ? response()->json([
                    'message' => __($status),
                ])
                : response()->json([
                    'message' => __($status),
                ], 400);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => Lang::get('auth.error'),
                'error' => $exception->getMessage(),
            ], 400);
        }
    }

    public function verify(Request $request, User $user)
    {
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Already your email is verified',
            ], 400);
        }

        $user->markEmailAsVerified();

        event(new Verified($user));

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Successfully verified',
            ], 200);
        } else {
            return response()->json([
                'message' => 'something is wrong',
            ], 400);
        }


    }

    /**
     * @param $email
     */
    public function resend(Request $request): JsonResponse
    {
        $this->validate($request, ['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (is_null($user)) {
            throw ValidationException::withMessages([
                'email' => 'User not found',
            ]);
        }
        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => 'Already your email is verified',
            ]);
        }
        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Successfully send. please check your email'
        ]);
    }
}
