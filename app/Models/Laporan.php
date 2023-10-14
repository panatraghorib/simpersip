<?php

namespace App\Models;

use Illuminate\Support\Str;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Laporan extends Model
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
        $this->table = $prefix . 'laporan';
        parent::__construct($attributes);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['periode', 'year'])
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(50)
            ->usingSeparator('-')
            ->doNotGenerateSlugsOnUpdate();
    }

    protected function periode(): Attribute
    {
        return Attribute::make(
            fn($value) => ($value !== null)
            ? Str::ucfirst(Str::replace("_", " ", $value))
            : "",
        );
    }

    protected function date(): Attribute
    {
        return Attribute::make(
            fn($value) => ($value !== null)
            ? (new \DateTime($value))->format('d/m/Y')
            : "",
        );
    }

}
