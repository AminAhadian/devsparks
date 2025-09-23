<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
/**
 * Register a new user
 *
 * Creates a new user account with the provided credentials and returns an API token.
 * Required fields: name, email, username, password (with confirmation).
 *
 * @param Request $request Contains user registration data
 * @return JsonResponse Returns authentication token and user data with HTTP 201 status
 */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|max:255',
            'email'    => 'required|email|unique:users',
            'username' => 'required|min:3|max:20|regex:/^[A-Za-z0-9_]{3,20}$/|string|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user], 201);
    }

/**
 * Authenticate user
 *
 * Logs in an existing user using either email or username with password.
 * Returns an API token for authenticated requests.
 *
 * @param Request $request Contains login credentials (email or username + password)
 * @return JsonResponse Returns authentication token and user data
 * @throws ValidationException If credentials are invalid
 */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'identity' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (! filter_var($value, FILTER_VALIDATE_EMAIL) && ! preg_match('/^[A-Za-z0-9_]{3,20}$/', $value)) {
                        $fail('The ' . $attribute . ' must be a valid email or username.');
                    }
                },
            ],
            'password' => 'required',
        ]);

        $identity = $request->identity;
        $user     = filter_var($identity, FILTER_VALIDATE_EMAIL)
            ? User::where('email', $identity)->first()
            : User::where('username', $identity)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['credentials' => ['The provided credentials are incorrect.']]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user]);
    }

/**
 * Logout user
 *
 * Revokes the current authentication token, effectively logging out the user.
 * Requires a valid authentication token in the request header.
 *
 * @param Request $request Contains authenticated user information
 * @return JsonResponse Returns success message with HTTP 200 status
 */
    public function logout(Request $request): JsonResponse
    {
        // revoke current token:
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out'], 200);
    }

/**
 * Get current user profile
 *
 * Returns the profile information of the currently authenticated user.
 * Requires a valid authentication token in the request header.
 *
 * @param Request $request Contains authenticated user information
 * @return JsonResponse Returns current user data
 */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
