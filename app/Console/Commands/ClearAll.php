<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ClearAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all laravel cache';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('clear-compiled');
        Artisan::call('optimize:clear');

        // $spatieClear = $this->option()('spatie');

        // if($spatieClear) {
        //     Artisan::call('cache:forget spatie.permission.cache');
        //     $this->info('spatie have been cleared!');
        // }
        $this->info('cache;route;config;view;clear-compiled;optimize; All have been cleared!');


        return Command::SUCCESS;
    }
}
