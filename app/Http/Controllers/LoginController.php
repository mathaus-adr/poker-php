<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class LoginController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate(['email' => 'string|email', 'password' => 'string']);

        $user = User::where('email', $validated['email'])->first();

        if(!$user) {
            return response()->noContent(403);
        }

        if(Hash::check($validated['password'], $user->password)) {
            return ['access_token' => $user->createToken('new_access')->accessToken];
        }

        return response()->noContent(403);
    }
}
