<?php

namespace App\Console\Commands;

use App\Jobs\CheckMessageJob;
use App\Jobs\CreateCompany;
use App\Models\Company;
use App\Models\Order;
use Illuminate\Console\Command;
use JPush\Client;


class Dev extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dev';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(Client $client): void
    {
        CreateCompany::dispatch(Company::find(117));
        CreateCompany::dispatch(Company::find(119));
        CreateCompany::dispatch(Company::find(120));
        CreateCompany::dispatch(Company::find(121));
        CreateCompany::dispatch(Company::find(122));
        CreateCompany::dispatch(Company::find(123));
        CreateCompany::dispatch(Company::find(124));
        CreateCompany::dispatch(Company::find(125));
        CreateCompany::dispatch(Company::find(126));
        CreateCompany::dispatch(Company::find(127));
    }
}
