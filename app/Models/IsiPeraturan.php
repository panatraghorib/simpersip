<?php

namespace App\Models;

use App\Models\Peraturan;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Attributes\SearchUsingPrefix;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IsiPeraturan extends Model
{
    use HasFactory, Searchable { Searchable::search as parentSearch; }

    protected $primaryKey = null;
    protected $table = null;
    protected $with = ['peraturan'];

    protected $prefix;
    const SEARCHABLE_FIELD = [
        'id',
        'peraturan_id',
        'konten',
        'simper_peraturan.judul',
    ];

    public function __construct(array $attributes = [])
    {
        $prefix = config('appstra.database.prefix');
        $this->table = $prefix . 'contents';
        $this->primaryKey = $prefix . 'contents.id';
        $this->prefix = $prefix;
        parent::__construct($attributes);
    }

    public function searchableAs()
    {
        return 'contents_index';
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */

    // #[SearchUsingPrefix(['id', 'peraturan_id'])]
    // #[SearchUsingFullText(['konten'])]
    function toSearchableArray()
    {
        // return $this->only(self::SEARCHABLE_FIELD);

        return [
            'konten' => '',
            // $this->prefix . 'peraturan.judul' => '',
        ];
    }

        /**
     * Perform a search against the model's indexed data.
     *
     * @param  string  $query
     * @param  \Closure  $callback
     * @return \Laravel\Scout\Builder
     */
    public static function search($query = '', $callback = null, array $condition = [])
    {
        $prefix = config('appstra.database.prefix');

        return static::parentSearch($query, $callback)->query(function ($builder) use ($prefix, $condition) {
            $builder->join($prefix . 'peraturan', $prefix . 'contents.peraturan_id', '=', $prefix . 'peraturan.id');

            if($condition && is_array($condition)) {
                foreach($condition as $key => $val) {
                    $builder->where($prefix . $key, $val);
                }
            }
        });
    }

    function makeAllSearchableUsing($query)
    {
        return $query->with('peraturan');
    }

    function peraturan()
    {
        return $this->belongsTo(Peraturan::class, 'peraturan_id');
    }

    function judulPeraturan(): Attribute
        {
        return Attribute::make(
            fn($value) => $this->peraturan()->first()->judul,
        );
    }

    function nomorPeraturan(): Attribute
        {
        return Attribute::make(
            fn($value) => $this->peraturan()->first()->nomor,
        );
    }

    function hasPasal(): Attribute
        {
        return Attribute::make(function ($value) {
            if ($this->is_pasal !== 0) {
                return "Pasal " . $this->urutan_kelompok;
            }
            return $this->is_pasal;
        });
    }
}
