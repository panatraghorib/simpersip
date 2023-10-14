<?php

namespace App\Http\Controllers\Backend;

use App\Authorizable;
use App\Helpers\ResponseApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\NotulensiAddRequest;
use App\Http\Requests\NotulensiUpdateRequest;
use App\Http\Resources\NotulensiResource;
use App\Http\Traits\FileHandler;
use App\Models\Notulensi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotulensiController extends Controller
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

    public function add(NotulensiAddRequest $request)
    {
        DB::beginTransaction();

        try {

            /*
             **** Another way using valiation ****
            $request->validate([
            'title' => 'required|string',
            'meeting_leader' => 'required|string',
            'meeting_participants' => [
            function ($attribute, $value, $fail) {
            json_encode($value);
            $isJson = (json_last_error() == JSON_ERROR_NONE);
            if (!$isJson) {
            $fail('Invalid Json String');
            }
            },
            function ($attribute, $value, $fail) {
            if (!isset($value) || is_null($value)) {
            $fail('Data Minimal 1');
            }
            },
            ],
            'meeting_date' => 'required|string',
            'meeting_agenda' => 'required|string',
            'notulis' => 'required|string',
            'file_path' => 'required|mimes:pdf|max:15000',
            'category_id' => 'required|string',
            'additional_info' => 'nullable',
            ]);

            return ResponseApi::success($request->all());
             */

            $notulensi = new Notulensi();
            $notulensi->title = $request->title;
            $notulensi->meeting_leader = Str::ucfirst($request->meeting_leader);
            $notulensi->meeting_agenda = Str::ucfirst($request->meeting_agenda);
            $notulensi->meeting_participants = $request->meeting_participants;
            $notulensi->meeting_date = Carbon::createFromFormat('d/m/Y',
                $request->meeting_date)->format('Y-m-d');
            $notulensi->notulis = Str::of($request->notulis)->ucfirst();
            $notulensi->category_id = $request->category_id;
            $notulensi->additional_info = $request->additional_info;

            if ($request->file()) {
                $type = $request->category_id;
                $fileName = $this->renameFile($request->meeting_date, $type);
                $filePath = $request->file('file_path')
                    ->storeAs('files_notulensi', $fileName, 'public');
                $notulensi->file_name = $fileName;
                $notulensi->file_path = '/storage/' . $filePath;
            }

            // return ResponseApi::success($notulensi);

            $notulensi->save();

            DB::commit();
            activity('Notulensi')
                ->causedBy(auth()->user() ?? null)
                ->withProperties(['attributes' => $notulensi])
                ->performedOn($notulensi)
                ->event('created')
                ->log('Notulensi ' .
                    Carbon::createFromFormat('d/m/Y',
                        $request->meeting_date)->format('F') . ' has been created');

            return ResponseApi::success($request->all());
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
                'id' => 'required|exists:App\Models\Notulensi,id',
            ]);
            $tablePrefix = config('appstra.database.prefix');

            $notulensi = Notulensi::select([
                $tablePrefix . 'notulensi.id',
                $tablePrefix . 'notulensi.title',
                $tablePrefix . 'notulensi.slug',
                $tablePrefix . 'notulensi.meeting_leader',
                $tablePrefix . 'notulensi.meeting_agenda',
                $tablePrefix . 'notulensi.meeting_participants',
                $tablePrefix . 'notulensi.meeting_date',
                $tablePrefix . 'notulensi.notulis',
                $tablePrefix . 'notulensi.thumbnail',
                $tablePrefix . 'notulensi.file_path',
                $tablePrefix . 'notulensi.file_name',
                $tablePrefix . 'notulensi.status',
                $tablePrefix . 'notulensi.updated_by',
                $tablePrefix . 'notulensi.deleted_by',
                $tablePrefix . 'notulensi.additional_info',
                $tablePrefix . 'notulensi.category_id',
                $tablePrefix . 'notulensi.created_at',
            ]);
            $notulensi->where($tablePrefix . 'notulensi.id', '=', $request->id);
            $notulensiRow = $notulensi->first();
            $notulensiRow->participants = json_decode($notulensiRow->participants);

            $data['notulensi'] = $notulensiRow;

            return ResponseApi::success($data);
        } catch (\Exception$e) {
            return ResponseApi::failed($e);
        }

    }

    /**
     * show by Collection Resources
     *
     * @param Notulensi $notulensi
     * @return void
     */
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

    public function update(NotulensiUpdateRequest $request)
    {
        DB::beginTransaction();

        try {
            // return ResponseApi::success($request->all());

            $notulensi = Notulensi::findOrFail($request->id);

            $notulensi->title = $request->title;
            $notulensi->meeting_leader = Str::ucfirst($request->meeting_leader);
            $notulensi->meeting_agenda = Str::ucfirst($request->meeting_agenda);
            $notulensi->meeting_participants = $request->meeting_participants;
            $notulensi->meeting_date = Carbon::createFromFormat('d/m/Y',
                $request->meeting_date)->format('Y-m-d');
            $notulensi->notulis = Str::of($request->notulis)->ucfirst();
            $notulensi->category_id = $request->category_id;
            $notulensi->additional_info = $request->additional_info;

            if ($request->file()) {
                $type = $request->category_id;
                $fileName = $this->renameFile($request->meeting_date, $type);
                $filePath = $request->file('fpath')
                    ->storeAs('files_notulensi', $fileName, 'public');
                $notulensi->file_name = $fileName;
                $notulensi->file_path = '/storage/' . $filePath;
            }

            // return ResponseApi::success($notulensi);

            $notulensi->save();

            DB::commit();
            activity('Notulensi')
                ->causedBy(auth()->user() ?? null)
                ->withProperties(['attributes' => $notulensi])
                ->performedOn($notulensi)
                ->event('updated')
                ->log('Notulensi ' .
                    Carbon::createFromFormat('d/m/Y',
                        $request->meeting_date)->format('F') . ' has been created');

            // return ResponseApi::success($request->all());
            return ResponseApi::success();

        } catch (\Exception $e) {
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
                    'exists:App\Models\Notulensi',
                ],
            ]);

            $notulensi = Notulensi::findOrFail($request->id);
            $this->handleDeleteFile($notulensi->file_name);
            $notulensi->delete();

            DB::commit();
            activity('Notulensi')
                ->causedBy(auth()->user() ?? null)
                ->performedOn($notulensi)
                ->event('deleted')
                ->log('Notulensi ' . $notulensi->meeting_date . ' has been deleted');

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
    private function renameFile($date, $type)
    {
        // expected name: notulensi_kom_july_02_09_2023
        $date = Carbon::createFromFormat('d/m/Y',
            $date);
        $montName = Str::lower($date->format('F'));
        $notulenDate = $date->format('d_m_Y');

        switch ($type) {
            case 1:
                $prefix = 'notulensi_dir_%s_%s.pdf';
                $fileName = sprintf($prefix, $montName, $notulenDate);
                break;
            case 2:
                $prefix = 'notulensi_kom_%s_%s.pdf';
                $fileName = sprintf($prefix, $montName, $notulenDate);
                break;
            default:
                $fileName = 'undefinied.pdf';
                break;
        }

        return $fileName;
    }
}
