<?php

namespace App\Http\Controllers\Backend;

use App\Authorizable;
use App\Helpers\ResponseApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Log;

class RoleController extends Controller
{

    use Authorizable;

    function list() {
        try {
            // $roles = Role::get(['id','name']);
            $roles = Role::with('permissions')->get();
            return ResponseApi::success(collect($roles)->toArray());

        } catch (\Exception$e) {
            return ResponseApi::failed($e);
        }
    }

    /**
     * browse func for view data Roles
     *
     * @return void
     */
    public function browse()
    {
        $draw = request('draw');
        $start = request('start');
        $length = request('length');
        $search = request('search');
        $columns = request('columns');
        $order = request('order');

        $role = Role::query();
        $recordsTotal = $role->count('id');
        $recordsFiltered = 0;
        if ($search) {
            $firstColumn = true;
            foreach ($columns as $column) {
                if ($column['searchable'] === 'true') {
                    if ($firstColumn) {
                        $role->where(Str::snake($column['data']), 'LIKE', "%{$search}%");
                        $firstColumn = false;
                    } else {
                        $role->orWhere(Str::snake($column['data']), 'LIKE', "%{$search}%");
                    }
                }
            }
            $recordsFiltered = $role->count('id');
        } else {
            $recordsFiltered = $recordsTotal;
        }

        if ($columns[$order['column']]['orderable'] == 'true') {
            $role->orderBy($columns[$order['column']]['data'], $order['dir']);
        }

        $role->skip($start);
        $role->limit($length);

        $data = [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'datatables' => $role->get(),
        ];

        return ResponseApi::success(collect($data)->toArray());
    }

    public function add(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'name' => 'required|string',
            ]);

            $role = new Role();
            $role->name = $request->name;
            $role->save();

            $permissions = ($request->permissions) ?? [];
            $role->syncPermissions($permissions);

            DB::commit();
            activity('Role')
                ->causedBy(auth()->user() ?? null)
                ->withProperties(['attributes' => $role])
                ->performedOn($role)
                ->event('created')
                ->log('Role ' . $request->name . ' has been created');

            return ResponseApi::success();
        } catch (\Exception$e) {
            DB::rollBack();
            return ResponseApi::failed($e);
        }
    }

    /**
     * show by Collection Resources
     *
     * @param Role $role
     * @return void
     */
    public function show(Role $role)
    {
        try {
            $roleRow['error'] = null;
            $roleRow['message'] = __('api_response.200');
            $roleRow['data'] = new RoleResource($role->loadMissing('permissions'));
            return $roleRow;
        } catch (\Exception$e) {
            return ResponseApi::failed($e);
        }

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        DB::beginTransaction();

        try {
            // return response()->json($request->all());

            $request->validate([
                'id' => [
                    'required',
                    'exists:App\Models\Role,id',
                ],
                'name' => "required|unique:App\Models\Role,name,{$request->id}",
            ]);

            $role = Role::findOrFail($request->id);
            $role_old = $role->toArray();

            $role->name = $request->name;
            if ($role->save()) {
                $permissions = ($request->permissions) ?? [];
                $role->syncPermissions($permissions);
            };

            DB::commit();
            activity('Roles')
                ->causedBy(auth()->user() ?? null)
                ->withProperties(['attributes' => [
                    'old' => $role_old,
                    'new' => $role,
                ]])
                ->performedOn($role)
                ->event('edited')
                ->log('Role ' . $role->name . ' has been edited');

            return ResponseApi::success();

        } catch (\Exception$e) {
            return ResponseApi::failed($e);
        }
    }

    public function destroy(Request $request)
    {

        try {
            $request->validate([
                'id' => [
                    'required',
                    'exists:App\Models\Role,id',
                ],
            ]);

            $role = Role::findOrFail($request->id);

            $user_roles = auth()->user()->roles()->pluck('id');
            $role_users = $role->users;

            if ($request->id == 1) {
                return ResponseApi::failed("You can not delete 'Superadmin'!");
            } elseif (in_array($request->id, $user_roles->toArray())) {
                return ResponseApi::failed("You can not delete your Role!");
            } elseif ($role_users->count()) {
                return ResponseApi::failed(" Can not be deleted! " . $role_users->count() . " user found!");
            }

            $role->delete();

            activity('Role')
                ->causedBy(auth()->user() ?? null)
                ->performedOn($role)
                ->event('deleted')
                ->log('Role ' . $role->date . ' has been deleted');

            return ResponseApi::success();
        } catch (\Exception$e) {
            return ResponseApi::failed($e);
        }

    }

    /**
     * show by Collection Resources
     */
    public function read(Request $request, Role $id)
    {
        try {
            $request->validate([
                'id' => 'required|exists:App\Models\Role,id',
            ]);

            $permission = Role::find($request->id);

            $data['role'] = $permission;

            return ResponseApi::success($data);

        } catch (\Exception$e) {
            return ResponseApi::failed($e);
        }
    }

}
