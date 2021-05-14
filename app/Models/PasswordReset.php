<?php

namespace App\Models;

use App\Mail\UserForgetPassword;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class PasswordReset extends Model
{
    use HasFactory;

    protected $fillable = [
        'email', 'token',
    ];

    public static function getPasswordReset($email)
    {
        $password_reset = self::where('email', $email)->first();

        return $password_reset;
    }

    public static function getToken($email)
    {
        $token = null;
        $password_reset = self::getPasswordReset($email);
        if (isset($password_reset)) {
            $token = $password_reset->token;
        }

        return $token;
    }

    public static function forgetPassword(User $user)
    {
        $email = $user->email;
        $password_reset = self::getPasswordReset($email);
        $created_at = date('Y-m-d H:i:s');
        $token = Hash::make(\Str::random(60));
        $data_update = [
            'token' => $token,
            'created_at' => $created_at,
        ];
        if (!isset($password_reset)) {
            $data_update['email'] = $email;
            $password_reset = self::create($data_update);
        } else {
            $password_reset->update($data_update);
        }

        UserForgetPassword::mailSend($user);

        return $password_reset;
    }

    public function getUser(): User
    {
        $user = User::getWithEmail($this->email);

        return $user;
    }

    public function getPasswordResetWithToken($token){
        return self::where('token', $token)->first();
    }

    public static function resetPassword($token, $new_password)
    {
        $password_reset = self::getPasswordResetWithToken($token);
        if($password_reset->token != $token) return throw new Exception('Sorry, cannot be change password, because your token not match, please retry with token match from receiver email!');
        $user = $password_reset->getUser();
        $user->password = Hash::make($new_password);
    }
}
