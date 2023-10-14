<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\KategoriPeraturan;
use App\Models\Peraturan;

class DashboardController extends Controller
{
    public function cardList()
    {
        $kategori = KategoriPeraturan::select(['judul'])
            ->withCount([
                'peraturan as per' => function ($query) {
                    $query->where('status', 1);
        }])->get();
        return response()->json(compact('kategori'));
    }
}
