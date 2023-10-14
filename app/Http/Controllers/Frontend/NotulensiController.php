<?php

namespace App\Http\Controllers\Frontend;

use App\Models\Notulensi;
use Illuminate\Support\Str;
use App\Helpers\ResponseApi;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\NotulensiResource;

class NotulensiController extends Controller
{

    public function browse(Request $request)
    {

        $draw = request('draw');
        $start = request('start');
        $length = request('length');
        $search = request('search');
        $columns = request('columns');
        $order = request('order');

        $notulensi = Notulensi::query();

        $recordsTotal = $notulensi->count('id');
        $recordsFiltered = 0;
        if ($search) {
            $firstColumn = true;
            foreach ($columns as $column) {
                if ($column['searchable'] === 'true') {
                    if ($firstColumn) {
                        $notulensi->where(Str::snake($column['data']), 'LIKE', "%{$search}%");
                        $firstColumn = false;
                    } else {
                        $notulensi->orWhere(Str::snake($column['data']), 'LIKE', "%{$search}%");
                    }
                }
            }
            $recordsFiltered = $notulensi->count('id');
        } else {
            $recordsFiltered = $recordsTotal;
        }

        if ($columns[$order['column']]['orderable'] == 'true') {
            $notulensi->orderBy($columns[$order['column']]['data'], $order['dir']);
        }

        $notulensi->skip($start);
        $notulensi->limit($length);

        $data = [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'datatables' => $notulensi->get(),
        ];

        return ResponseApi::success(collect($data)->toArray());
    }

    public function show(Notulensi $notulensi)
    {
        try {
            $notulensiRow['error'] = null;
            $notulensiRow['message'] = __('api_response.200');
            $notulensiRow['data'] = new NotulensiResource($notulensi);
            return $notulensiRow;
        } catch (\Exception$e) {
            return ResponseApi::failed($e);
        }

    }

    public function documentViewer(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|string|exists:\App\Models\Notulensi,file_name',
            ]);

            $file = Storage::disk('public')->get('files_notulensi/' . $request->file);
            return (new Response($file, 200))
                ->header('Content-Type', 'application/pdf');
        } catch (\Exception$e) {
            return ResponseApi::failed($e);
        }

    }

}
