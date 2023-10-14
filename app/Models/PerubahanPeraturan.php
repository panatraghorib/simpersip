<?php

namespace App\Models;

use App\Models\Peraturan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerubahanPeraturan extends Model
{
    use HasFactory;

    protected $table = 'perubahan';

    public function peraturan()
    {
        return $this->belongsTo(Peraturan::class);
    }
}
