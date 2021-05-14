<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PasswordReset;
use App\Models\ProfileAnotherBorrower;
use App\Models\ProfileCollegeStudent;
use App\Models\User;
use App\Utils\Response\ResponseFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api-jwt', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]);

            $credentials = request(['email', 'password']);

            if (!$token = Auth::guard('api-jwt')->attempt($credentials)) {
                return ResponseFormatter::otherFailedResponse(__('auth.failed'), null, [
                    'authorize' => ['no authorization'],
                ], 401);
            }

            return ResponseFormatter::success(__('auth.success'), [
                'auth' => $this->resultsWithToken($token),
            ]);
        } catch (\Exception $e) {
            return ResponseFormatter::failed($e);
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(Auth::guard('api-jwt')->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        Auth::guard('api-jwt')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->resultsWithToken(Auth::guard('api-jwt')->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function resultsWithToken($token)
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api-jwt')->factory()->getTTL() * 60,
        ];
    }

    public function register(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'status_register' => ['required', 'in:college_student,another_borrower'],
                'address' => ['required'],
                'email' => ['required', 'unique:users,email'],
                'password' => ['required', 'confirmed'],
                'nik' => [Rule::requiredIf(function () use ($request) {
                    return $request->status_register == 'another_borrower';
                })],
                'nim' => [Rule::requiredIf(function () use ($request) {
                    return $request->status_register == 'college_student';
                })],
            ]);

            $user = User::create([
                'name' => 'user',
                'role_id' => User::getRoleIdWithRoleName($request->status_register),
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'avatar' => 'users/default.png',
            ]);
            $data_profile = [
                'user_id' => $user->id,
                'address' => $request->address,
                'created_at' => $request,
            ];

            $profile = null;

            if ($request->status_register == 'college_student') {
                $data_profile['nim'] = $request->nim;
                $profile = ProfileCollegeStudent::create($data_profile);
            } else {
                $data_profile['nik'] = $request->nik;
                $profile = ProfileAnotherBorrower::create($data_profile);
            }

            DB::commit();

            return ResponseFormatter::success('success register', [
                'users' => $user,
                'profile' => $profile,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return ResponseFormatter::failed($e);
        }
    }

    public function forgetPassword(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'email' => ['required', function ($key, $att, $email) {
                    if (!User::emailExists($email)) {
                        return 'Your email not match';
                    }
                }],
            ]);

            $user = User::where('email', $request->email)->first();

            PasswordReset::forgetPassword($user);

            return ResponseFormatter::success('Success request forget password');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return ResponseFormatter::failed($e);
        }
    }

    public function resetPassword(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'token' => ['required'],
                'password' => ['required', 'confirmed'],
            ]);

            PasswordReset::resetPassword($request->token, $request->password);

            return ResponseFormatter::success('Success request reset password');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return ResponseFormatter::failed($e);
        }
    }
}
