<?php

namespace App\Http\Controllers\Frontend;

use App\Helpers\ResponseApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\LaporanResource;
use App\Models\Laporan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LaporanController extends Controller
{

    public function browse(Request $request)
    {

        $draw = request('draw');
        $start = request('start');
        $length = request('length');
        $search = request('search');
        $columns = request('columns');
        $order = request('order');

        $laporan = Laporan::query();

        $recordsTotal = $laporan->count('id');
        $recordsFiltered = 0;
        if ($search) {
            $firstColumn = true;
            foreach ($columns as $column) {
                if ($column['searchable'] === 'true') {
                    if ($firstColumn) {
                        $laporan->where(Str::snake($column['data']), 'LIKE', "%{$search}%");
                        $firstColumn = false;
                    } else {
                        $laporan->orWhere(Str::snake($column['data']), 'LIKE', "%{$search}%");
                    }
                }
            }
            $recordsFiltered = $laporan->count('id');
        } else {
            $recordsFiltered = $recordsTotal;
        }

        if ($columns[$order['column']]['orderable'] == 'true') {
            $laporan->orderBy($columns[$order['column']]['data'], $order['dir']);
        }

        $laporan->skip($start);
        $laporan->limit($length);

        $data = [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'datatables' => $laporan->get(),
        ];

        return ResponseApi::success(collect($data)->toArray());
    }

    public function show(Laporan $laporan)
    {
        try {
            $laporanRow['error'] = null;
            $laporanRow['message'] = __('api_response.200');
            $laporanRow['data'] = new LaporanResource($laporan);
            return $laporanRow;
        } catch (\Exception$e) {
            return ResponseApi::failed($e);
        }

    }

    public function documentViewer(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|string|exists:\App\Models\Laporan,file_name',
            ]);

            $file = Storage::disk('public')->get('files_laporan/' . $request->file);
            return (new Response($file, 200))
                ->header('Content-Type', 'application/pdf');
        } catch (\Exception$e) {
            return ResponseApi::failed($e);
        }

    }

}
