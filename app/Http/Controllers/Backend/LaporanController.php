<?php

namespace App\Http\Controllers\Backend;

use App\Authorizable;
use App\Helpers\ResponseApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\LaporanAddRequest;
use App\Http\Requests\LaporanUpdateRequest;
use App\Http\Resources\LaporanResource;
use App\Http\Traits\FileHandler;
use App\Models\Laporan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LaporanController extends Controller
{
    use FileHandler;
    use Authorizable;

    public function browse()
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

    public function add(LaporanAddRequest $request)
    {
        DB::beginTransaction();

        try {

            $laporan = new Laporan();
            $laporan->title = $request->title;
            $laporan->prologue = $request->input('prologue', true);
            $laporan->periode = $request->periode;
            $laporan->date = Carbon::createFromFormat('d/m/Y',
                $request->date)->format('Y-m-d');
            $laporan->year = Carbon::createFromFormat('d/m/Y',
                $request->date)->format('Y');
            $laporan->status = !!$request->status;
            $laporan->category_id = $request->category_id;
            $laporan->desc = $request->desc;

            if ($request->file()) {
                $type = $request->category_id;
                $fileName = $this->renameFile($request->date, $request->periode, $type);
                $filePath = $request->file('file_path')
                    ->storeAs('files_laporan', $fileName, 'public');
                $laporan->file_name = $fileName;
                $laporan->file_path = '/storage/' . $filePath;
            }

            // return ResponseApi::success($laporan);

            $laporan->save();

            DB::commit();
            activity('Laporan')
                ->causedBy(auth()->user() ?? null)
                ->withProperties(['attributes' => $laporan])
                ->performedOn($laporan)
                ->event('created')
                ->log('Laporan ' .
                    Carbon::createFromFormat('d/m/Y',
                        $request->date)->format('F') . ' has been created');

            return ResponseApi::success();
        } catch (\Exception$e) {
            DB::rollBack();
            return ResponseApi::failed($e);
        }
    }

    /**
     * funtion show
     *
     * @param Request $request
     * @return Object
     * not in use
     */
    public function x_show(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:App\Models\Laporan,id',
            ]);
            $tablePrefix = config('appstra.database.prefix');

            $laporan = Laporan::select([
                $tablePrefix . 'laporan.id',
                $tablePrefix . 'laporan.title',
                $tablePrefix . 'laporan.slug',
                $tablePrefix . 'laporan.periode',

                $tablePrefix . 'laporan.meeting_leader',
                $tablePrefix . 'laporan.meeting_agenda',
                $tablePrefix . 'laporan.date',
                $tablePrefix . 'laporan.notulis',
                $tablePrefix . 'laporan.thumbnail',
                $tablePrefix . 'laporan.file_path',
                $tablePrefix . 'laporan.file_name',
                $tablePrefix . 'laporan.status',
                $tablePrefix . 'laporan.updated_by',
                $tablePrefix . 'laporan.deleted_by',
                $tablePrefix . 'laporan.desc',
                $tablePrefix . 'laporan.category_id',
                $tablePrefix . 'laporan.created_at',
            ]);
            $laporan->where($tablePrefix . 'laporan.id', '=', $request->id);
            $laporanRow = $laporan->first();
            $laporanRow->participants = json_decode($laporanRow->participants);

            $data['laporan'] = $laporanRow;

            return ResponseApi::success($data);
        } catch (\Exception$e) {
            return ResponseApi::failed($e);
        }

    }

    /**
     * show by Collection Resources
     *
     * @param Laporan $laporan
     * @return void
     */
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

    public function update(LaporanUpdateRequest $request)
    {
        DB::beginTransaction();

        try {
            $laporan = Laporan::findOrFail($request->id);
            $laporan->title = $request->title;
            $laporan->prologue = $request->input('prologue', true);
            $laporan->periode = $request->periode;
            $laporan->date = Carbon::createFromFormat('d/m/Y',
                $request->date)->format('Y-m-d');
            $laporan->year = Carbon::createFromFormat('d/m/Y',
                $request->date)->format('Y');
            $laporan->status = !!$request->status;
            $laporan->category_id = $request->category_id;
            $laporan->desc = $request->desc;

            if ($request->file()) {
                $type = $request->category_id;
                $fileName = $this->renameFile($request->date, $request->periode, $type);
                $filePath = $request->file('fpath')
                    ->storeAs('files_laporan', $fileName, 'public');
                $laporan->file_name = $fileName;
                $laporan->file_path = '/storage/' . $filePath;
            }
            // return ResponseApi::success($laporan);
            $laporan->save();

            DB::commit();
            activity('Laporan')
                ->causedBy(auth()->user() ?? null)
                ->withProperties(['attributes' => $laporan])
                ->performedOn($laporan)
                ->event('updated')
                ->log('Laporan ' .
                    Carbon::createFromFormat('d/m/Y',
                        $request->date)->format('F') . ' has been created');

            return ResponseApi::success();

        } catch (\Exception$e) {
            DB::rollBack();
            return ResponseApi::failed($e);
        }
    }

    public function destroy(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'id' => [
                    'required',
                    'exists:App\Models\Laporan',
                ],
            ]);

            $laporan = Laporan::findOrFail($request->id);
            $this->handleDeleteFile($laporan->file_name);
            $laporan->delete();

            DB::commit();
            activity('Laporan')
                ->causedBy(auth()->user() ?? null)
                ->performedOn($laporan)
                ->event('deleted')
                ->log('Laporan ' . $laporan->date . ' has been deleted');

            return ResponseApi::success();
        } catch (\Exception$e) {
            DB::rollBack();
            return ResponseApi::failed($e);
        }

    }
    /**
     * private function renaming file name
     *
     * @param String $date
     * @param Integer $type
     * @return String Name
     */
    private function renameFile($date, $periode, $type)
    {
        // expected name: laporan_kom_july_02_09_2023
        $date = Carbon::createFromFormat('d/m/Y',
            $date);
        // $montName = Str::lower($date->format('F'));
        $year = $date->format('Y');

        switch ($type) {
            case 1:
                $prefix = 'laporan_dir_%s_%s.pdf';
                $fileName = sprintf($prefix, $periode, $year);
                break;
            case 2:
                $prefix = 'laporan_kom_%s_%s.pdf';
                $fileName = sprintf($prefix, $periode, $year);
                break;
            case 3:
                $prefix = 'laporan_tb_%s_%s.pdf';
                $fileName = sprintf($prefix, $periode, $year);
                break;
            default:
                $fileName = 'lap_lain.pdf';
                break;
        }

        return $fileName;
    }

    public function documentViewer(Request $request)
    {
        $file = Storage::disk('public')->get('files/' . $request->fname);
        return (new Response($file, 200))
            ->header('Content-Type', 'application/pdf');
    }

}
