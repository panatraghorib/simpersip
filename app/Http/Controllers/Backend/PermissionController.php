<?php

namespace App\Http\Controllers\Backend;

use App\Authorizable;
use App\Helpers\ResponseApi;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    use Authorizable;


    public function list()
    {
            try {
                $permission = Permission::select(['id','name'])->orderBy('id')->get();
                $data['permissions'] = $permission;
                return ResponseApi::success(collect($data)->toArray());
            }
            catch(Exception $e) {
                return ResponseApi::failed($e);
            }
    }
    /**
     * browse func for view data Roles
     *
     * @return void
     */
    public function browse(Request $request)
    {
        $maxItem = $request->maxItem ?? 10;
        try {
            $dataPermission = Permission::query()
                ->when($request,
                    function ($query) {
                        $query->where('name', 'LIKE', '%' . request('keyword') . '%');
                    })->orderBy('name')->paginate($maxItem);

            // $permission = Permission::paginate(10);
            // $data['permission'] = $permission;
            $data['permission'] = $dataPermission;

            return ResponseApi::success(collect($data)->toArray());
        } catch (\Exception$e) {
            return ResponseApi::failed($e);
        }
    }

    public function read(Request $request, Permission $id)
    {
        try {
            $request->validate([
                'id' => 'required|exists:App\Models\Permission,id',
            ]);

            $permission = Permission::find($request->id);

            $data['permission'] = $permission;

            return ResponseApi::success($data);

        } catch (\Exception$e) {
            return ResponseApi::failed($e);
        }
    }

    public function delete(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'id' => [
                    'required',
                    'exists:App\Models\Permission,id',
                ],
            ]);

            $permission = Permission::findOrFail($request->id);
            $permission->delete();

            DB::commit();
            activity('Permissions')
                ->causedBy(auth()->user() ?? null)
                ->withProperties(['attributes' => $request->all()])
                ->performedOn($permission)
                ->event('deleted')
                ->log('Permission ' . $permission->key . ' has been deleted');

            return ResponseApi::success();

        } catch (\Exception$e) {
            DB::rollback();
            return ResponseApi::failed($e);
        }
    }

    public function update(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'id' => [
                    'required',
                    'exists:App\Models\Permission,id',
                ],
                'name' => "required|unique:App\Models\Permission,name,{$request->id}",
                'guard_name' => "required",
            ]);

            $permission = Permission::find($request->id);
            $permission_old = $permission->toArray();

            $permission->name = $request->name;
            $permission->guard_name = $request->guard_name;
            $permission->save();

            DB::commit();
            activity('Permissions')
                ->causedBy(auth()->user() ?? null)
                ->withProperties(['attributes' => [
                    'old' => $permission_old,
                    'new' => $permission,
                ]])
                ->performedOn($permission)
                ->event('edited')
                ->log('Permission ' . $permission->key . ' has been edited');

            return ResponseApi::success($permission);

        } catch (\Exception$e) {
            return ResponseApi::failed($e);
        }
    }
}
