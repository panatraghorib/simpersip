<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UserVerification extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = null;

    protected $fillable = [
        'user_id',
        'verification_token',
        'expired_at',
        'count_incorrect',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function __construct(array $attributes = [])
    {
        $prefix = config('appstra.database.prefix');
        $this->table = $prefix . 'user_verifications';
        parent::__construct($attributes);
    }

    protected static $logAttributes = true;
    protected static $logFillable = true;
    protected static $logName = 'UserVerification';

    public function getDescriptionForEvent(string $eventName): string
    {
        return "This model has been {$eventName}";
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->dontSubmitEmptyLogs();
    }
}
