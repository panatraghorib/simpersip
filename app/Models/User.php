<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Http\Traits\GetAuthenticUser;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens,
    HasFactory,
    Notifiable,
    LogsActivity,
    HasRoles,
        SoftDeletes;

    use GetAuthenticUser;

    protected $with = ['profile'];

    protected $table = null;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
    ];

    protected $dates = [
        'deleted_at',
    ];

    // protected $guard_name = 'api';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'blocked_status' => 'boolean',
    ];

    public function __construct(array $attributes = [])
    {
        $prefix = config('appstra.database.prefix');
        $this->table = $prefix . 'users';
        parent::__construct($attributes);
    }

    protected static $logAttributes = [
        'name',
        'email',
        'avatar',
        'additional_info',
    ];

    protected static $logFillable = [
        'name',
        'email',
        'avatar',
        'additional_info',
    ];

    protected static $logName = 'User';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
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

    public function profile()
    {
        return $this->hasOne(UserProfile::class, 'user_id', 'id');
    }

}
