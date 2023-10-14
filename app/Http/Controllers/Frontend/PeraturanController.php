<?php

namespace App\Http\Controllers\Frontend;

use App\Helpers\ResponseApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\PeraturanResource;
use App\Models\Peraturan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PeraturanController extends Controller
{

    public function browse(Request $request)
    {

        $draw = request('draw');
        $start = request('start');
        $length = request('length');
        $search = request('search');
        $columns = request('columns');
        $order = request('order');

        $peraturan = Peraturan::query();

        $recordsTotal = $peraturan->count('id');
        $recordsFiltered = 0;
        if ($search) {
            $firstColumn = true;
            foreach ($columns as $column) {
                if ($column['searchable'] === 'true') {
                    if ($firstColumn) {
                        $peraturan->where(Str::snake($column['data']), 'LIKE', "%{$search}%");
                        $firstColumn = false;
                    } else {
                        $peraturan->orWhere(Str::snake($column['data']), 'LIKE', "%{$search}%");
                    }
                }
            }
            $recordsFiltered = $peraturan->count('id');
        } else {
            $recordsFiltered = $recordsTotal;
        }

        if ($columns[$order['column']]['orderable'] == 'true') {
            $peraturan->orderBy($columns[$order['column']]['data'], $order['dir']);
        }

        $peraturan->skip($start);
        $peraturan->limit($length);

        $data = [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'datatables' => $peraturan->get(),
        ];

        return ResponseApi::success(collect($data)->toArray());
    }

    public function show(Peraturan $peraturan)
    {
        try {
            $peraturanRow['error'] = null;
            $peraturanRow['message'] = __('api_response.200');
            $peraturanRow['data'] = new PeraturanResource($peraturan->loadMissing(['perubahan', 'kategori']));
            return $peraturanRow;
        } catch (\Exception$e) {
            return ResponseApi::failed($e);
        }

    }

    public function documentViewer(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|string|exists:\App\Models\Peraturan,file_name',
            ]);

            $file = Storage::disk('public')->get('files_peraturan/' . $request->file);
            return (new Response($file, 200))
                ->header('Content-Type', 'application/pdf');
        } catch (\Exception$e) {
            return ResponseApi::failed($e);
        }

    }

}
