<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Facade\FlareClient\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    //
    public function login(Request $request)
    {
        try{
            $request->validate([
                // validasi input
                'email' => 'email|required',
                'password' => 'required'
            ]);

            // mengecek credential(login)
            $credentials = request(['email','password']);
            if(!Auth::attempt($credentials)) {
                return ResponseFormatter::error([
                    'message' => 'Unauthorized',
                ], 'Authentication Failed', 500);
            }

            // jika hash tidak sesuai maka beri error
            $user = User::where('email', $request->email)->first();
            if(!Hash::check($request->password, $user->password, [])) {
                throw new \Exception('Invalid Credentials');
            }

            // jika berhasil  maka loginkan
            $tokenResult = $user->createToken('authToken')->plainTextToken;
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'Authenticated');

        }catch(Exception $error){
            return ResponseFormatter::error([
                'message' => 'Something wrong',
                'error' => $error
            ], 'Authentication Failed', 500);
        }
    }
}
