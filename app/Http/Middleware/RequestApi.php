<?php

namespace App\Http\Middleware;

use App\Helpers\ConvertCase;
use App\Helpers\FileHandle;
use App\Helpers\Redis\RedisConfig;
use App\Helpers\ResponseApi;
use App\Models\Config;
use Closure;
use Illuminate\Contracts\Foundation\Application;

class RequestApi
{
    /**
     * The application implementation.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The URIs that should be accessible while maintenance mode is enabled.
     *
     * @var array
     */
    protected $except = [];

    /**
     * URIs prefix.
     *
     * @var string
     */
    protected $prefix = null;

    /**
     * Maintenance key status.
     *
     * @var string
     */
    private $app_maintenance = null;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->excepts = config('appstra.whitelist');
        $this->prefix = config('appstra.admin_panel_route_prefix');
        $this->app_maintenance = config('appstra.app_maintenance');
    }

    public function handle($request, Closure $next)
    {
        $lang = ($request->hasHeader('Accept-Language')) ? $request->header('Accept-Language') : 'en';
        app()->setLocale($lang);

        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $request->merge(ConvertCase::snake($request->all()));
        $request->merge(FileHandle::normalize($request->all()));

        if ($this->isUnderMaintenance() || $this->app->isDownForMaintenance()) {
            if ($this->isAdmin()) {
                return $next($request);
            }

            dump($request);
            dd($this->inExceptArray($request));
            if ($this->inExceptArray($request)) {
                return $next($request);
            }

            return ResponseApi::serviceUnavailable();
        }

        return $next($request);

    }

    protected function isUnderMaintenance()
    {
        if (isset($this->app_maintenance)) {
            return $this->app_maintenance === true ?? false;
        } else {
            try {
                // RedisConfig::save();
                $configModel = RedisConfig::get();
                $maintenance = $configModel->where('key', 'maintenance')->firstOrFail();
                return $maintenance->value == '1' ? true : false;
            } catch (\Exception$th) {
                $maintenance = Config::where('key', 'maintenance')->firstOrFail();
                $th = $th;
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
        $excepts = [];

        foreach ($this->excepts['api'] as $key => $path) {
            $excepts[] = $this->prefix . $path;
        }

        foreach ($excepts as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            // if request uri in wihitelist uri return true
            // not affected for maintenance
            if ($request->fullUrlIs($except) || $request->is($except) || $path == $except) {
                return true;
            }
        }

        return false;
    }
}
