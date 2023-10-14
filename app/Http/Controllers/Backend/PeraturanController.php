<?php

namespace App\Http\Controllers\Backend;

use App\Authorizable;
use App\Helpers\ResponseApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\PeraturanResource;
use App\Http\Traits\FileHandler;
use App\Models\Peraturan;
use App\Models\PerubahanPeraturan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PeraturanController extends Controller
{
    use Authorizable;
    use FileHandler;

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

    public function option_list()
    {
        try {
            $peraturan = Peraturan::select([
                config('appstra.database.prefix') . 'peraturan.id',
                config('appstra.database.prefix') . 'peraturan.nomor',
                config('appstra.database.prefix') . 'peraturan.nomor_peraturan',
                config('appstra.database.prefix') . 'peraturan.tahun',
                config('appstra.database.prefix') . 'peraturan.judul',
            ])->NotArchived()->get();

            $dataPeraturan = [];
            foreach ($peraturan as $data) {
                $dataPeraturan[] = [
                    'value' => $data->id,
                    'label' => $data->nomor . "-" . $data->judul,
                ];

            }
            return ResponseApi::success(collect($dataPeraturan)->toArray());
        } catch (\Exception$e) {
            return ResponseApi::failed($e);
        }
    }

    public function add(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'judul' => 'required|min:15',
                'nomor' => 'required',
                'tahun' => 'required|string|max:4|min:4',
                'tanggal_ditetapkan' => 'required|string',
                'category_id' => 'required|numeric',
                'file_path' => 'required|mimes:pdf|max:15000',
            ]);

            if ($request->perubahan['set'] === 'true') {
                $request->validate([
                    'perubahan.revoked_id' => 'required|numeric',
                    'perubahan.number_perubahan' => 'required|numeric',
                    'perubahan.cabut_peraturan' => 'nullable',
                ]);
            }

            $prefixNomor = "PDIR NO.";
            $prefixTahun = "TH";
            $req = $request->all();

            $peraturan = new Peraturan();
            $attributeFileName = [
                'nomor' => $req['nomor'],
                'tahun' => $req['tahun'],
            ];
            $type = $request->category_id;

            $nomorPer = $this->getPrefixNomor($attributeFileName, $type);

            $peraturan->nomor = $nomorPer;
            $peraturan->judul = Str::of($req['judul'])->upper();
            $peraturan->nomor_peraturan = $req['nomor'];
            $peraturan->tahun = $req['tahun'];
            $peraturan->tanggal_ditetapkan = Carbon::createFromFormat('d/m/Y', $req['tanggal_ditetapkan'])->format('Y-m-d');
            $peraturan->category_id = $req['category_id'];
            // return ResponseApi::success($peraturan);

            if ($request->file()) {

                $attributeFile = [
                    'nomor' => $peraturan->nomor_peraturan,
                    'tahun' => $peraturan->tahun,
                ];
                $type = $request->category_id;
                // $fileName = time() . '_' . $request->file_path->getClientOriginalName();
                $fileName = $this->renameFile($attributeFile, $type);
                $filePath = $request->file('file_path')
                    ->storeAs('files_peraturan', $fileName, 'public');
                $peraturan->file_name = $fileName;
                $peraturan->file_path = '/storage/' . $filePath;
            }

            if ($peraturan->save()) {
                if ($request->perubahan['set'] === 'true') {
                    $perubahan = new PerubahanPeraturan();
                    $revokedNumber = Peraturan::whereId($request->perubahan['revoked_id'])->first();
                    $perubahan->revoking_id = $peraturan->id;
                    $perubahan->revoking_number = $nomorPer;
                    $perubahan->revoked_id = $request->perubahan['revoked_id'];
                    $perubahan->revoked_number = $revokedNumber->nomor;
                    $perubahan->step = $request->perubahan['number_perubahan'];
                    $perubahan->revoking_type = $request->perubahan['cabut_peraturan'] ? 1 : 2;
                    $perubahan->save();
                }
            }
            DB::commit();
            activity('User')
                ->causedBy(auth()->user() ?? null)
                ->withProperties(['attributes' => $peraturan])
                ->performedOn($peraturan)
                ->event('created')
                ->log('Peraturan No ' . $req['nomor'] . ' has been created');

            return ResponseApi::success($request->all());
        } catch (\Exception$e) {
            DB::rollBack();
            return ResponseApi::failed($e);
        }
    }

    public function show(Peraturan $peraturan)
    {
        try {

            #1
            // return new PeraturanResource($peraturan->loadMissing('perubahan'));

            #2
            $peraturanRow['error'] = null;
            $peraturanRow['message'] = __('api_response.200');
            $peraturanRow['data'] = new PeraturanResource($peraturan->loadMissing('perubahan'));
            return $peraturanRow;

            #3
            // $peraturanRow =  new PeraturanResource($peraturan->loadMissing('perubahan'));
            // return ResponseApi::res(collect($peraturanRow)->toArray());
        } catch (\Exception$e) {
            return ResponseApi::failed($e);
        }

    }

    public function update(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'id' => 'required|exists:App\Models\Peraturan,id',
                'judul' => 'required|min:15',
                'nomor' => 'required',
                'tahun' => 'required|string|max:4|min:4',
                'tanggal_ditetapkan' => 'required|string',
                'category_id' => 'required|numeric',
                'filePath' => 'required|mimes:pdf|max:15000',
            ]);

            if ($request->perubahan['revoked_id'] != null) {
                $request->validate([
                    'perubahan.revoked_id' => 'required|numeric',
                    'perubahan.step' => 'required|numeric',
                    'perubahan.revoking_type' => 'nullable',
                ]);
            }

            $req = $request->all();
            $attributeFileName = [
                'nomor' => $req['nomor_peraturan'],
                'tahun' => $req['tahun'],
            ];
            
            $type = $request->category_id;

            $nomorPer = $this->getPrefixNomor($attributeFileName, $type);

            $peraturan = Peraturan::findOrFail($request->id);
            $peraturan->nomor = $nomorPer;
            $peraturan->judul = Str::of($req['judul'])->upper();
            $peraturan->nomor_peraturan = $req['nomor_peraturan'];
            $peraturan->tahun = $req['tahun'];
            $peraturan->tanggal_ditetapkan = Carbon::createFromFormat('d/m/Y', $req['tanggal_ditetapkan'])->format('Y-m-d');

            $peraturan->category_id = $req['category_id'];

            if ($request->file()) {
                $fileName = $this->renameFile($attributeFileName, $type);
                $filePath = $request->file('filePath')->storeAs('files_peraturan', $fileName, 'public');
                $peraturan->file_name = $fileName;
                $peraturan->file_path = '/storage/' . $filePath;
            }

            if ($peraturan->save()) {
                if ($request->perubahan['revoked_id'] != null) {
                    $perubahan = PerubahanPeraturan::where('revoking_id', '=', $peraturan->id)->firstOrNew();
                    $revokedNumber = Peraturan::whereId($request->perubahan['revoked_id'])->first();
                    $perubahan->revoking_id = $peraturan->id;
                    $perubahan->revoking_number = $nomorPer;
                    $perubahan->revoked_id = $request->perubahan['revoked_id'];
                    $perubahan->revoked_number = $revokedNumber->nomor;
                    $perubahan->step = $request->perubahan['step'];
                    $perubahan->revoking_type = $request->perubahan['revoking_type'] == 'true' ? 1 : 2;
                    $perubahan->save();
                } else if ($request->perubahan['revoked_id'] == null) {
                    $perubahan = PerubahanPeraturan::where('revoking_id', '=', $peraturan->id)->first();
                    if ($perubahan) {
                        $perubahan->delete();
                    }
                }
            }
            DB::commit();
            activity('Peraturan')
                ->causedBy(auth()->user() ?? null)
                ->withProperties(['attributes' => $peraturan])
                ->performedOn($peraturan)
                ->event('updated')
                ->log('Peraturan No ' . $peraturan->nomor . ' has been updated');
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
                    'exists:App\Models\Peraturan',
                ],
            ]);

            $peraturan = Peraturan::findOrFail($request->id);
            $this->handleDeleteFile($peraturan->file_name);
            $peraturan->delete();

            DB::commit();
            activity('User')
                ->causedBy(auth()->user() ?? null)
                ->performedOn($peraturan)
                ->event('deleted')
                ->log('Peraturan ' . $peraturan->judul . 'Nomo' . $peraturan->nomor . ' has been deleted');

            return ResponseApi::success();
        } catch (\Exception$e) {
            DB::rollBack();
            return ResponseApi::failed($e);
        }

    }

    private function getPrefixNomor(array $attributeFile, $type)
    {
        $prefix = "";
        $nomor = $attributeFile['nomor'];
        switch ($type) {
            case 1:
                $prefix = 'PERKOM NO. %s TH. %d';
                $prefixNumber = sprintf($prefix, $nomor, $attributeFile['tahun']);
                break;
            case 2:
                $prefix = 'PERDIR NO. %s TH. %d';
                $prefixNumber = sprintf($prefix, $nomor, $attributeFile['tahun']);
                break;
            case 3:
                $prefix = 'SK NO. %s TH. %d';
                $prefixNumber = sprintf($prefix, $nomor, $attributeFile['tahun']);
                break;
            case 4:
                $prefix = 'PP NO. %s TH. %d';
                $prefixNumber = sprintf($prefix, $nomor, $attributeFile['tahun']);
                break;

            default:
                $prefixNumber = 'undefinied';
                break;
        }

        return $prefixNumber;

    }

    private function renameFile(array $attributeFile, $type)
    {
        $nomor = Str::replace('/', '-', $attributeFile['nomor']);

        switch ($type) {
            case 1:
                $prefix = 'perkom_no_%s_th_%d.pdf';
                $fileName = sprintf($prefix, $nomor, $attributeFile['tahun']);
                break;
            case 2:
                $prefix = 'perdir_no_%s_th_%d.pdf';
                $fileName = sprintf($prefix, $nomor, $attributeFile['tahun']);
                break;
            case 3:
                $prefix = 'kepdir_no_%s_th_%d.pdf';
                $fileName = sprintf($prefix, $nomor, $attributeFile['tahun']);
                break;
            case 4:
                $prefix = 'pp_no_%s_th_%d.pdf';
                $fileName = sprintf($prefix, $nomor, $attributeFile['tahun']);
                break;

            default:
                $fileName = 'undefinied.pdf';
                break;
        }

        return $fileName;
    }

    public function trashed()
    {
        # code...
    }

    public function restore($id)
    {
        # code...
    }

    public function showElse(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:App\Models\User,id',
            ]);

            $user = User::select([
                config('appstra.database.prefix') . 'users.id',
                config('appstra.database.prefix') . 'users.email',
                config('appstra.database.prefix') . 'users.username',
                config('appstra.database.prefix') . 'users.name',
                config('appstra.database.prefix') . 'users.nik',
                config('appstra.database.prefix') . 'users.phone',
                config('appstra.database.prefix') . 'users.email_verified_at',
            ]);
            $user->where(config('appstra.database.prefix') . 'users.id', '=', $request->id);
            $userRow = $user->first();
            $userRow->email_verified = !is_null($userRow->email_verified_at);

            $data['user'] = $userRow;

            return ResponseApi::success($data);
        } catch (\Exception$e) {
            return ResponseApi::failed($e);
        }

    }

}
