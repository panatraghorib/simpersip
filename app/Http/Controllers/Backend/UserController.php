<?php

namespace App\Http\Controllers\Backend;

use App\Authorizable;
use App\Events\Backend\UserCreated;
use App\Helpers\ResponseApi;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\AlphabetAndUnderLineCharactersOnly;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // use Authorizable;

    public function browse(Request $request)
    {

        try {
            $draw = request('draw');
            $start = request('start');
            $length = request('length');
            $search = request('search');
            $columns = request('columns');
            $order = request('order');

            $users = User::query();
            $recordsTotal = $users->count('id');
            $recordsFiltered = 0;
            if ($search) {
                $firstColumn = true;
                foreach ($columns as $column) {
                    if ($column['searchable'] === 'true') {
                        if ($firstColumn) {
                            $users->where($column['data'], 'LIKE', "%{$search}%");
                            $firstColumn = false;
                        } else {
                            $users->orWhere($column['data'], 'LIKE', "%{$search}%");
                        }
                    }
                }
                $recordsFiltered = $users->count('id');
            } else {
                $recordsFiltered = $recordsTotal;
            }

            if ($columns[$order['column']]['orderable'] == 'true') {
                $users->orderBy($columns[$order['column']]['data'], $order['dir']);
            }

            $users->skip($start);
            $users->limit($length);

            $data = [
                'draw' => $draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'datatables' => $users->get(),
            ];

            return ResponseApi::success(collect($data)->toArray());

        } catch (\Exception$e) {
            return ResponseApi::failed($e);

        }

    }

    public function add(Request $request)
    {
        DB::beginTransaction();

        try {

            $request->validate([
                'email' => 'required|email|unique:App\Models\User,email',
                'username' => ['required', 'string', 'max:25', 'unique:App\Models\User,username', new AlphabetAndUnderLineCharactersOnly],
                'nik' => 'required|unique:App\Models\User,nik',
                'password' => 'required|confirmed|min:6',
                'name' => 'required|string|max:255|min:3',
                // 'avatar' => 'nullable',
            ]);
            // return ResponseApi::success($request->all());

            $user = new User();
            $user->name = $request->name;
            $user->username = $request->username;
            $user->nik = $request->nik;
            $user->email = $request->email;
            // $user->avatar = $request->avatar;
            $user->phone = $request->phone;
            $user->additional_info = $request->additional_info;
            $user->password = Hash::make($request->password);
            $user->blocked_status = 0;

            if ($request->email_verified == true) {
                $user->email_verified_at = date('Y-m-d H:i:s');
            }

            if ($user->save()) {
                $roles = ($request->roles) ?? [];
                $user->assignRole($roles);
                event(new UserCreated($user));
            }

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

    public function show(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:App\Models\User,id',
            ]);

            $user['user'] = User::with('roles')
                ->where('id', $request->id)->first();

            // ELSE
            // $user['user'] = User::query()->select(
            //     config('appstra.database.prefix') . 'users.id',
            //     config('appstra.database.prefix') . 'users.email',
            //     config('appstra.database.prefix') . 'users.username',
            //     config('appstra.database.prefix') . 'users.name',
            //     config('appstra.database.prefix') . 'users.nik',
            //     config('appstra.database.prefix') . 'users.phone',
            //     config('appstra.database.prefix') . 'users.email_verified_at',
            // )->with(['roles' => function ($query) {
            //     $query->select('id', 'name');
            // }])->where('id', $request->id)->first();

            return ResponseApi::success(collect($user)->toArray());
        } catch (\Exception$e) {
            return ResponseApi::failed($e);
        }
    }

    public function update(Request $request)
    {
        DB::beginTransaction();

        try {

            $request->validate([
                'id' => 'required|exists:App\Models\User,id',
                'email' => "required|email|unique:App\Models\User,email,{$request->id}",
                'username' => "required|string|max:255|unique:App\Models\User,username,{$request->id}",
                'name' => 'required',
                // 'avatar' => 'nullable',
            ]);

            $user = User::findOrFail($request->id);
            $old_user = $user->toArray();

            $user->name = $request->name;
            $user->username = $request->username;
            $user->email = $request->email;
            $user->nik = $request->nik;
            $user->phone = $request->phone;
            // $user->avatar = $request->avatar;
            $user->additional_info = $request->additional_info;
            if ($request->password && $request->password != '') {
                $user->password = Hash::make($request->password);
            }

            if ($request->email_verified) {
                $user->email_verified_at = date('Y-m-d H:i:s');
            } else {
                $user->email_verified_at = null;
            }

            if ($user->save()) {
                $roles = ($request->roles) ?? [];
                $user->syncRoles($roles);
            }

            DB::commit();
            activity('User')
                ->causedBy(auth()->user() ?? null)
                ->withProperties(['attributes' => [
                    'old' => $old_user,
                    'new' => $user,
                ]])
                ->performedOn($user)
                ->event('updated')
                ->log('User ' . $user->name . ' has been updated');

            return ResponseApi::success($user);
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
                    'exists:App\Models\User',
                ],
            ]);

            if (auth()->user()->id == $request->id || $request->id == 1) {
                return ResponseApi::failed("You can not delete this user");
            }

            $user = User::findOrFail($request->id);
            // $this->handleDeleteFile($user->avatar);
            $user->delete();

            DB::commit();
            activity('User')
                ->causedBy(auth()->user() ?? null)
                ->performedOn($user)
                ->event('deleted')
                ->log('User ' . $user->name . ' has been deleted');

            return ResponseApi::success();
        } catch (\Exception$e) {
            DB::rollBack();

            return ResponseApi::failed($e);
        }

    }

    public function trashed()
    {
        try {
            $draw = request('draw');
            $start = request('start');
            $length = request('length');
            $search = request('search');
            $columns = request('columns');
            $order = request('order');

            $users = User::query()->onlyTrashed();
            $recordsTotal = $users->count('id');
            $recordsFiltered = 0;
            if ($search) {
                $firstColumn = true;
                foreach ($columns as $column) {
                    if ($column['searchable'] === 'true') {
                        if ($firstColumn) {
                            $users->where($column['data'], 'LIKE', "%{$search}%");
                            $firstColumn = false;
                        } else {
                            $users->orWhere($column['data'], 'LIKE', "%{$search}%");
                        }
                    }
                }
                $recordsFiltered = $users->count('id');
            } else {
                $recordsFiltered = $recordsTotal;
            }

            if ($columns[$order['column']]['orderable'] == 'true') {
                $users->orderBy($columns[$order['column']]['data'], $order['dir']);
            }

            $users->skip($start);
            $users->limit($length);

            $data = [
                'draw' => $draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'datatables' => $users->get(),
            ];

            return ResponseApi::success(collect($data)->toArray());
        } catch (\Exception$e) {
            return ResponseApi::failed($e);

        }
    }

    public function restore($id)
    {
        # code...
    }

}
