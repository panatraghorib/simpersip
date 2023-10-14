<?php

namespace App\Models;

use App\Models\IsiPeraturan;
use App\Models\KategoriPeraturan;
use App\Models\PerubahanPeraturan;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Peraturan extends Model
{
    use HasFactory, HasSlug, Searchable;
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

    protected $with = ['perubahan', 'kategori'];

    const SEARCHABLE_FIELD = ['id', 'judul', 'tahun'];

    public function __construct(array $attributes = [])
    {
        $prefix = config('appstra.database.prefix');
        $this->table = $prefix . 'peraturan';
        parent::__construct($attributes);
    }

    /*
    public function getScoutKey()
    {
    return $this->email;
    }
     */

    /**
     * Get the key name used to index the model.
     *
     * @return mixed
     */
    /*
    public function getScoutKeyName()
    {
    return 'email';
    }
     */

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */

    public function toSearchableArray()
    {
        return $this->only(self::SEARCHABLE_FIELD);
    }

    public function makeAllSearchableUsing($query)
    {
        return $query->with('contents');
    }

    public function searchableAs()
    {
        return 'peraturan_index';
    }

    public function perubahan()
    {
        return $this->hasOne(PerubahanPeraturan::class, 'revoking_id', 'id');
    }

    public function kategori()
    {
        return $this->belongsTo(KategoriPeraturan::class, 'category_id');
    }

    public function contents()
    {
        return $this->hasMany(IsiPeraturan::class);
    }

    protected function tanggalDitetapkan(): Attribute
    {
        return Attribute::make(
            fn($value) => ($value !== null)
            ? (new \DateTime($value))->format('d/m/Y')
            : "",
        );
    }

    public function scopeNotArchived($query)
    {
        $query->where('status_peraturan', '=', 1);
        // return $query->whereHas('assetstatus', function ($query) {
        // });
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('nomor')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(50)
            ->usingSeparator('_')
            ->doNotGenerateSlugsOnUpdate();
    }

}
