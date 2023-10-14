<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\Redis\RedisConfig;
use App\Helpers\ResponseApi;
use App\Http\Controllers\Controller;
use App\Models\Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    /**
     *
     */
    private $app;
    private $excepts = [];
    private $prefix = null;
    private $app_maintenance = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->excepts = config('appstra.whitelist.appstra');
        $this->prefix = config('appstra.admin_panel_route_prefix');
        $this->app_maintenance = config('appstra.app_maintenance');
    }

    public function isMaintenance(Request $request)
    {
        if ($this->checkMaintenanceConfig() || $this->app->isDownForMaintenance()) {
            if ($this->isAdmin()) {
                return ResponseApi::success(['maintenance' => false]);
            }

            if ($this->inExceptArray($request)) {
                return ResponseApi::success(['maintenance' => false]);
            }

            return ResponseApi::success(['maintenance' => true]);
        }

        return ResponseApi::success(['maintenance' => false]);
    }

    private function checkMaintenanceConfig()
    {

        if (isset($this->app_maintenance)) {
            return $this->app_maintenance === true ?? false;
        } else {
            try {
                RedisConfig::save();
                $configModel = RedisConfig::get();
                $maintenance = $configModel->where('key', 'maintenance')->firstOrFail();
                return $maintenance->value == '1' ? true : false;
            } catch (\Exception $th) {
                $maintenance = Config::where('key', 'maintenance')->firstOrFail();
                return $maintenance->value == '1' ? true : false;
            }
        }
    }

    private function isAdmin()
    {
        //TODO: normalize block code
        $user = auth()->user();
        if (isset($user)) {
            $roles = $user->roles ?? null;
            if (isset($roles)) {
                $role = $roles->first() ?? null;
                if (isset($role)) {
                    $role_name = $role->name ?? null;
                    if ($role_name === 'administrator') {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function inExceptArray($request)
    {
        foreach ($this->excepts as $except) {
            $except = '/' . $this->prefix . $except;
            $path = trim($request->path, '/');

            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->fullUrlIs($except) || $request->is($except) || $path == $except) {
                return true;
            }
        }

        return false;
    }
}
