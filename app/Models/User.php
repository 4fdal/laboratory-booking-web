<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use TCG\Voyager\Models\Role;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends \TCG\Voyager\Models\User implements JWTSubject
{
    use HasFactory;
    use Notifiable;

    // Rest omitted for brevity

    public static $roleNameCollegeStudent = 'college_student';
    public static $roleNameAnotherBorrower = 'another_borrower';
    public static $roleNameLabTechnical = 'lab_technical';

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public static function getRoleIdWithRoleName(string $roleName)
    {
        $role = Role::where('name', $roleName)->first();
        if (!isset($role)) {
            throw new \Exception('[error] getRoleIdWithRoleName Role name not match, please check check roleName');
        }

        return $role->id;
    }

    public static function emailExists($email)
    {
        $user_count = User::where('email', $email)->count();

        return $user_count > 0;
    }

    public static function getWithEmail($email)
    {
        return User::where('email', $email)->first();
    }

    public static function profile()
    {
        $user = Auth::user();
        $role = Role::where('id', $user->role_id)->first();
        switch ($role->name) {
            case self::$roleNameAnotherBorrower:
                return ProfileAnotherBorrower::where('user_id', $user->id)->first();
                break;
            case self::$roleNameCollegeStudent:
                return ProfileCollegeStudent::where('user_id', $user->id)->first();
                break;
            case self::$roleNameLabTechnical:
                return ProfileLabTechnicial::where('user_id', $user->id)->first();
                break;

            default:
                return null;
                break;
        }
    }
}
