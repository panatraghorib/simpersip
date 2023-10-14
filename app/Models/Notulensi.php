<?php

namespace App\Models;

use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notulensi extends Model
{
    use HasFactory, SoftDeletes, HasSlug;
    protected $table = null;
    protected $guarded = [
        // 'id',
        'updated_at',
        '_token',
        '_method',
    ];

    protected $dates = [
        'deleted_at',
    ];

    public function __construct(array $attributes = [])
    {
        $prefix = config('appstra.database.prefix');
        $this->table = $prefix . 'notulensi';
        parent::__construct($attributes);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['title', 'meeting_date'])
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(50)
            ->usingSeparator('-')
            ->doNotGenerateSlugsOnUpdate();
    }

    protected function meetingDate(): Attribute
    {
        return Attribute::make(
            fn($value) => ($value !== null)
            ? (new \DateTime($value))->format('d/m/Y')
            : "",
        );
    }
}
