<?php

namespace App\Console\Commands\Cron;

use App\Models\Company;
use App\Models\Enumerations\CompanyType;
use Illuminate\Console\Command;

class OrderDailyStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:order-daily-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = now()->toDateString();

        Company::where('type', CompanyType::WuSun->value)->check(100, function ($companies) use ($date) {
            foreach ($companies as $company) {
                \App\Models\OrderDailyStats::create([
                    'company_id' => $company->id,
                    'parent_id' => $company->parent_id,
                    'date' => $date,
                    'order_repair_count' => 0,
                    'order_mediate_count' => 0,
                ]);
            }
        });
    }
}
