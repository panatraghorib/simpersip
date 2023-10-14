<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\URL;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserProfile extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = null;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = ['avatar_url'];

    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function __construct(array $attributes = [])
    {
        $prefix = config('appstra.database.prefix');
        $this->table = $prefix . 'userprofiles';
        parent::__construct($attributes);
    }

    protected static $logAttributes = [
        'first_name',
        'last_name',
        'gender',
        'address',
        'last_ip',
    ];

    protected static $logFillable = [
        'first_name',
        'last_name',
        'gender',
        'address',
        'last_ip',
    ];

    protected static $logName = 'User Profile';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            fn($value) => $this->first_name . " " . $this->last_name,
            fn($value) => $this->first_name . " " . $this->last_name,
        );
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            fn() => $this->avatar ? URL::to($this->avatar) : null,
        );
    }

    protected function dateOfBirth(): Attribute
    {
        return Attribute::make(
            fn($value) => ($value !== null)
            ? (new \DateTime($value))->format('d/m/Y')
            : "",
        );
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}
