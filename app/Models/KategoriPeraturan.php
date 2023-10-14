<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriPeraturan extends Model
{
    use HasFactory;

    protected $table = 'kategori';

    protected $fillable = ['judul', 'slug', 'deskripsi', 'status'];


    public function peraturan()
    {
        return $this->hasMany(Peraturan::class, 'category_id');
    }

    public function judulPeraturan(): Attribute
    {
        return Attribute::make(
            fn($value) => $this->peraturan()->first()->judul,
        );

    }
}
