<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Config extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = null;

    public function __construct(array $attributes = [])
    {
        $prefix = config('appstra.database.prefix');
        $this->table = $prefix.'app_config';
        parent::__construct($attributes);
    }

    protected $fillable = [
        'key',
        'display_name',
        'value',
        'details',
        'type',
        'order',
        'group',
        'can_delete',
    ];

    protected static $logAttributes = true;
    protected static $logFillable = true;
    protected static $logName = 'Config';

    public function getDescriptionForEvent(string $eventName): string
    {
        return "This model has been {$eventName}";
    }

    public function getCanDeleteAttribute($value)
    {
        return $value == 1;
    }

    /**
     * Dont store empty logs. Storing empty logs can happen when you only
     * want to log a certain attribute but only another changes.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->dontSubmitEmptyLogs();
    }
}
