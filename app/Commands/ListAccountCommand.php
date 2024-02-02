<?php

namespace App\Commands;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use LaravelZero\Framework\Commands\Command;

class ListAccountCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'list-accounts';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Display all Accounts';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $filePath = storage_path('accounts.txt');
        $file = 'accounts.txt';

        $accounts = (Storage::exists($file) && Storage::size($file) > 0) ? unserialize(file_get_contents($filePath)) : [];

        $this->table(['Name', 'Type'], collect($accounts)->map(function ($account){
            return [
                $account['name'],
                'n/a'
            ];
        }));

//        foreach ($accounts as $account){
//            $this->info( $account['name'] ?? 'n/a');
//        }
    }

}
