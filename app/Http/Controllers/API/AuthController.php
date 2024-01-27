<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\VerificationMail;
use App\Models\User;
use App\Models\VerificationUser;
use Auth;
use Carbon\Carbon;
use Hash;
use Illuminate\Http\Request;
use Mail;

class AuthController extends Controller
{

    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, string $id)
    // {

    // }

    public function login(Request $request) {
        $data = $request->only('usernameOrEmail', 'password');

        $user = User::where('username', $data['usernameOrEmail'])
                    ->orWhere('email', $data['usernameOrEmail'])
                    ->first();

        if ($user)
            if (Hash::check($data['password'], $user->password)){
                //send otp via email
                $sendOTP = $this->sendOTP($user);

                if ($sendOTP) {
                    return ResponseFormatter::success(['user_id' => $user->id], 'Login Success. Go To OTP Page');
                }else{
                    return ResponseFormatter::error(500, 'Failed to Send OTP. Please Try Again');
                }
                // return ResponseFormatter::success(['user_id' => $user->id], 'Login Success. Go To OTP Page');
                // return ResponseFormatter::success(['user_id' => $sendOTP], 'Login Success. Go To OTP Page');
            }
            else{

                return ResponseFormatter::error(null, 'Wrong Password. Please Check Your Password');
            }

            return ResponseFormatter::error(null, 'User Not Found. Please Check Your Username or Email');
    }

    public function register(Request $request) {
        $data = $request->all();

        // Store User
        $user = User::create($data);

        $request->merge(['usernameOrEmail' => $user->email]);

        $login = $this->login($request);

        if ($login) {
            return ResponseFormatter::success(['user_id' => $user->id], 'Register Success. Go To OTP Page');
        }else{
            return ResponseFormatter::error(null, 'Register Failed');
        }
    }

    public function sendOTP($user) {
        //send otp via email
        $code = rand(100000, 999999);

        $verification = VerificationUser::create([
            'code' => $code,
            'user_id' => $user->id,
        ]);

        $sendMail = Mail::to($user->email)->send(new VerificationMail($verification));

        return $sendMail;
    }

    public function verification($id, Request $request) {
        $user = User::with(['verifications' => function($q){
            $q->latest()->first();
          }])->find($id);

        // Ambil waktu sekarang
        $currentTime = Carbon::now();
        if ($user->verifications[0]->created_at->diffinseconds($currentTime) < 300){
            if ($user->verifications[0]->code == $request->code){
                // if ($user->is_registered == 0) {
                    $update = $user->update([
                        'is_registered' => true,
                    ]);
                // }

                if (!$update) {
                    // Print the errors for debugging
                    dd($user->errors());
                }

                Auth::attempt([
                    'username' => $user->username,
                    'password' => $user->password]);

                return ResponseFormatter::success($user,'OTP Verification Success. Go to Home Page');
            }
            return ResponseFormatter::error(null, 'OTP Not Matching');
        }
        return ResponseFormatter::error(null, 'OTP Expired');
    }
}
