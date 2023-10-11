<?php

namespace App\Console\Commands;

use App\Jobs\CheckMessageJob;
use App\Jobs\CreateCompany;
use App\Jobs\OrderQuotationQrcodeJob;
use App\Jobs\QuotaBillPdfJob;
use App\Models\Company;
use App\Models\Enumerations\InsuranceType;
use App\Models\LossPerson;
use App\Models\Order;
use App\Models\OrderQuotation;
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
       cache()->forget('spatie.permission.cache');
    }
}
