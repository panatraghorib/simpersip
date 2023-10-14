<?php

namespace App\Http\Controllers\Backend;

use App\Authorizable;
use App\Helpers\ResponseApi;
use App\Http\Controllers\Controller;
use App\Models\KategoriPeraturan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class KategoriPeraturanController extends Controller
{
    use Authorizable;

    public function browse(Request $request)
    {

        $draw = request('draw');
        $start = request('start');
        $length = request('length');
        $search = request('search');
        $columns = request('columns');
        $order = request('order');

        //ATR: Experimental
        // $x = ["checkbox","judul","actions"];
        // $select = collect($columns)->pluck('data')->toArray();
        // var_dump($x);
        // var_dump($select);
        // var_dump($columns);
        // $category = KategoriPeraturan::query()->select($x);
        //===================================================

        $category = KategoriPeraturan::query();
        $recordsTotal = $category->count('id');
        $recordsFiltered = 0;
        if ($search) {
            $firstColumn = true;
            foreach ($columns as $column) {
                if ($column['searchable'] === 'true') {
                    if ($firstColumn) {
                        $category->where($column['data'], 'LIKE', "%{$search}%");
                        $firstColumn = false;
                    } else {
                        $category->orWhere($column['data'], 'LIKE', "%{$search}%");
                    }
                }
            }
            $recordsFiltered = $category->count('id');
        } else {
            $recordsFiltered = $recordsTotal;
        }

        if ($columns[$order['column']]['orderable'] == 'true') {
            $category->orderBy($columns[$order['column']]['data'], $order['dir']);
        }

        // ->with('kategori')
        // ->with('perubahan')

        $category->skip($start);
        $category->limit($length);

        $data = [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'datatables' => $category->get(),
        ];

        return ResponseApi::success(collect($data)->toArray());
    }

    public function option_list()
    {
        try {
            $peraturan = KategoriPeraturan::select([
                'kategori.id',
                'kategori.judul',
            ])->orderBy('urut')->get();

            $dataPeraturan = [];
            foreach ($peraturan as $data) {
                $dataPeraturan[] = [
                    'value' => $data->id,
                    'label' => $data->judul,
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
                'email' => 'required|email|unique:App\Models\User',
                'password' => 'required|confirmed|min:6',
                'name' => 'required|string|max:255|min:3',
                'username' => 'required|string|max:255|unique:App\Models\User,username',
                'nik' => 'required|unique:App\Models\User,nik',
                // 'avatar' => 'nullable',
            ]);

            $user = new User();
            $user->name = $request->name;
            $user->username = $request->username;
            $user->nik = $request->nik;
            $user->email = $request->email;
            // $user->avatar = $request->avatar;
            $user->phone = $request->phone;
            $user->additional_info = $request->additional_info;
            $user->password = Hash::make($request->password);
            if ($request->email_verified == true) {
                $user->email_verified_at = date('Y-m-d H:i:s');
            }
            $user->save();

            DB::commit();
            activity('User')
                ->causedBy(auth()->user() ?? null)
                ->withProperties(['attributes' => $user])
                ->performedOn($user)
                ->event('created')
                ->log('User ' . $user->name . ' has been created');

            return ResponseApi::success($user);
        } catch (\Exception$e) {
            DB::rollBack();
            return ResponseApi::failed($e);
        }
    }

    public function update(Request $request)
    {
    }

    public function destroy(Request $request)
    {
    }

    public function trashed()
    {
        # code...
    }

    public function restore($id)
    {
        # code...
    }

}
