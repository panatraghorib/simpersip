<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Console\Input\InputOption;

class AdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'appstra:admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Admin Account';


    protected function getOptions()
    {
        return [
            ['create', null, InputOption::VALUE_NONE, 'Create an admin user', null],
            ['name', null, InputOption::VALUE_REQUIRED, 'Name of the user', null],
            ['username', null, InputOption::VALUE_REQUIRED, 'Username of the user', null],
            ['nik', null, InputOption::VALUE_REQUIRED, 'NIK Pengguna Admin', null],
            ['password', null, InputOption::VALUE_REQUIRED, 'Password of the user', null],
            ['confirm_password', null, InputOption::VALUE_REQUIRED, 'Confirmation password', null],
        ];
    }

    /**
     * Get command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['email', InputOption::VALUE_REQUIRED, 'The email of the user.', null],
        ];
    }

    public function fire()
    {
        return $this->handle();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Get or create user
        $user = $this->getUser(
            $this->option('create')
        );

        // the user not returned
        if (!$user) {
            $this->info('User not found.');
            exit;
        }

        $this->info('The user now has full access to your site.');

        // return Command::SUCCESS;
    }

    /**
     * Get or create user.
     *
     * @param  bool  $create
     * @return \App\User
     */
    protected function getUser($create = false)
    {
        $email = $this->argument('email');
        $name = $this->option('name');
        $username = $this->option('username');
        $nik = $this->option('nik');
        $password = $this->option('password');
        $confirmPassword = $this->option('confirm_password');

        // $role = $this->getAdministratorRole();

        // var_dump($role);
        // exit;
        // If we need to create a new user go ahead and create it
        if ($create) {
            if (!$name) {
                $name = $this->ask('Masukkan nama pengguna admin');
            }

            if (!$username) {
                $username = $this->ask('Masukkan admin username (lowercase)');
            }

            if (!$nik) {
                $nik = $this->ask('Masukkan NIK admin');
            }

            if (!$password) {
                $password = $this->secret('Masukkan password admin');
            }

            if (!$confirmPassword) {
                $confirmPassword = $this->secret('Konfirmasi password admin');
            }

            // Ask for email if there wasnt set one
            if (!$email) {
                $email = $this->ask('Masukkan Email');
            }

            // Passwords don't match
            if ($password != $confirmPassword) {
                $this->info("Passwords don't match");

                return;
            }

            $this->info('Creating admin account');

            $user = new User();
            $user->name = $name;
            $user->username = $username;
            $user->nik = $nik;
            $user->email = $email;
            $user->email_verified_at = date('Y-m-d H:i:s');
            $user->password = Hash::make($password);
            $user->save();

            $role = $this->getAdministratorRole();
            $user->assignRole($role);

            return $user;
        } else {
            $user = User::where('email', $email)->first();
            return $user;
        }
    }

    /**
     * Get the administrator role, create it if it does not exists.
     *
     * @return mixed
     */
    protected function getAdministratorRole()
    {
        $role = Role::where('name', 'superadmin')->first();
        var_dump($role);
        if (is_null($role)) {
            $role = Role::create(['name' => 'superadmin', 'guard_name' => 'api']);
        }

        return $role;
    }
}
