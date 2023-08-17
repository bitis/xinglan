<?php

namespace App\Console\Commands;

use App\Jobs\QuotaBillPdfJob;
use App\Jobs\QuotaHistory;
use App\Models\Company;
use App\Models\Order;
use App\Models\OrderQuotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;


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
    public function handle(): void
    {
       QuotaHistory::dispatch(OrderQuotation::find(13));
    }
}
