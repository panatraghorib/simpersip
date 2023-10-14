<?php

namespace App\Http\Controllers\Backend;

use App\Exceptions\SingleException;
use App\Helpers\ResponseApi;
use App\Http\Controllers\Controller;
use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ConfigurationsController extends Controller
{
    protected $configuration = [];

    /**
     * get all data of configurations by order
     *
     * @return object
     */
    public function browse()
    {
        $configs = Config::orderBy('order')->get();
        $data['configuration'] = $configs;
        return ResponseApi::success(collect($data)->toArray());
    }

    /**
     * add configuration data
     *
     * @return void
     */
    public function add(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'key' => 'required|unique:App\Models\Config',
                'display_name' => 'required',
                'gorup' => 'required',
                'type' => 'required',
                'details' => [
                    function ($attribute, $value, $fail) use ($request) {
                        if (in_array($request->type, ['checkbox', 'radio', 'select', 'select_multiple'])) {
                            json_decode($value);
                            $isJson = (json_last_error() == JSON_ERROR_NONE);

                            if (!$isJson) {
                                $fail('Invalid Json String');
                            }
                        }
                    },
                    function ($attribute, $value, $fail) use ($request) {
                        if (in_array($request->type, ['checkbox', 'radio', 'select', 'select_multiple'])) {
                            if (!isset($value) || is_null($value)) {
                                $fail('Opsi diperlukan untuk checkbox, radio, select, select-multiple');
                            }

                        }
                    },
                ], // Validation Using Closures
            ]);

            $config = new Config();
            $data = $request->all();
            $data['can_delete'] = $request->get('can_delete', true);
            $configFillable = $config->getFillable();

            foreach ($data as $key => $value) {
                $property = Str::snake($key);
                if (in_array($property, $configFillable)) {
                    $config->{$property} = $value;
                }
            }

            $config->save();

            DB::commit();

            return ResponseApi::success($config);
        } catch (\Exception$e) {
            DB::rollBack();

            return ResponseApi::failed($e);
        }
    }

    /**
     * get data of configurations by requested id
     *
     * @param Request $request->id
     * @return object
     */
    public function read(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required',
            ]);

            $config = Config::findOrFail($request->id);
            $data['configuration'] = $config;

            return ResponseApi::success($data);
        } catch (\Exception$e) {
            ResponseApi::failed($e);
        }
    }

    /**
     * Edit for configurations
     *
     * @param Request $request
     * @return object
     */
    public function update(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'id' => 'required',
            ]);

            $config = Config::findOrFail($request->id);
            if (!is_null($config)) {
                $config->key = $request->key;
                $config->display_name = $request->display_name;
                $config->value = $request->value;
                $config->details = $request->details;
                $config->type = $request->type;
                $config->order = $request->order;
                $config->group = $request->group;
                $config->save();
            }

            DB::commit();

            return ResponseApi::success($config);

        } catch (\Exception$e) {
            DB::rollBack();
            return ResponseApi::failed($e);
        }

    }

    /**
     * Update data configs
     *
     * @param Request $request
     * @return object
     */
    public function updateMultiple(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'configurations' => 'required',
            ]);

            foreach ($request->configurations as $config) {
                Validator::make($config, ['id' => ['required'],
                ])->validate();

                $updatedConfig = Config::find($config['id']);

                if (!is_null($config)) {
                    $updatedConfig->key = $config['key'];
                    $updatedConfig->details = json_encode($config['details']);
                    $updatedConfig->display_name = $config['display_name'];
                    $updatedConfig->value = $config['value'];
                    $updatedConfig->type = $config['type'];
                    $updatedConfig->order = $config['order'];
                    $updatedConfig->group = $config['group'];
                    $updatedConfig->save();
                }
            }

            // RedisConfig::save();
            DB::commit();

            activity('Configurations')
                ->causedBy(auth()->user ?? null)
                ->withProperties(['attributes' => $request->configurations])
                ->performedOn($updatedConfig)
                ->event('updated')
                ->log('Configuration telah di perbarui');

            return ResponseApi::success(json_decode(json_encode($request->input())));

        } catch (\Exception$e) {
            DB::rollBack();
            return ResponseApi::failed($e);
        }
    }

    /**
     * Delete data config
     *
     * @param Request $request
     * @return void
     */
    public function delete(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'id' => $request->id,
            ]);

            $config = Config::findOrFail($request->id);
            if ($config->can_delete) {
                $config->delete();
            } else {
                throw new SingleException('Config tidak dapat dihapus!');
            }
            DB::commit();

            return ResponseApi::success();
        } catch (\Exception$e) {
            DB::rollBack();
            return ResponseApi::failed($e);
        }
    }

    /**
     * Aplyable data configs
     *
     * @return object
     */
    public function applyable()
    {
        try {
            $configs = Config::all();
            $config = [];

            foreach ($configs as $row) {
                if ($row->type === "upload_image") {
                    $config[$row->key] = URL::to($row->value);
                } else {
                    $config[$row->key] = $row->value;
                }

            }

            $data['configuration'] = $config;

            return ResponseApi::success(json_decode(json_encode($data)));
        } catch (\Exception$e) {
            ResponseApi::failed($e);
        }
    }

    public function fetch(Request $request)
    {
        try {
            $request->validate([
                'key' => 'sometimes|required|exists:App\Models\Config,key',
                'group' => 'sometimes|required|exists:App\Model\Config,group',
            ]);

            $config = Config::when($request->key, function ($query, $key) {
                $query->orWhere('key', $key);
            })
                ->when($request->group, function ($query, $group) {
                    $query->orWhere('group', $group);
                })
                ->get()
                ->toArray();

            $data['configuration'] = $config;

            return ResponseApi::success($data);
        } catch (\Exception$e) {
            DB::rollBack();

            return ResponseApi::failed($e);
        }
    }

    public function fetchMultiple(Request $request)
    {
        try {
            $request->validate([
                'key' => 'sometimes|required',
                'group' => 'sometimes|required',
            ]);

            $config = Config::when($request->key,
                function ($query, $key) {
                    foreach (explode(',', $key) as $key => $value) {
                        $query->orWhere('key', $value);
                    }
                    return $query;
                })
                ->when($request->group,
                    function ($query, $group) {
                        foreach (explode(',', $group) as $key => $value) {
                            $query->orWhere('group', $value);
                        }

                        return $query;
                    })
                ->get()
                ->toArray();

            $data['configuration'] = $config;

            return ResponseApi::success($data);

        } catch (\Exception$e) {
            return ResponseApi::failed($e);
        }
    }

}
