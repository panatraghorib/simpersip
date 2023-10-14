<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AuthPermission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:permission {name} {--R|remove}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membuat Permission untuk method default. Nama method harus jamak';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $permissions = $this->generatePermissions();

        $isRemove = $this->option('remove');

        if ($isRemove) {
            if (Permission::where('name', 'LIKE', '%' . $this->getArgument())->delete() . '%') {
                $this->warn('Permission' . implode(', ', $permissions) . ' deleted');
            } else {
                $this->warn('No permissions for' . $this->getNameArgument() . ' found!');

            }

        } else {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }
            // $this->info('Permissions ' . implode(', ', $permissions) . ' created.');
        }

        if ($role = Role::where('name', 'administrator')->first()) {
            $role->syncPermissions(Permission::all());
            $this->info('Admin Permissions Updated.');
        }
    }

    private function getArgument()
    {
        return strtolower(Str::plural($this->argument('name')));
    }

    private function generatePermissions()
    {
        $abilities = [
            'view',
            'add',
            'edit',
            'delete',
            'restore',
        ];
        // $name = $this->getArgument();
        $name = strtolower($this->argument('name'));

        return array_map(function ($val) use ($name) {
            return $val . '_' . $name;
        }, $abilities);

    }
}
