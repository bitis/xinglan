<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\ConsumerOrderDailyStats;
use App\Models\Order;
use App\Models\OrderDailyStats;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InitStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:init-stats';

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
        $orders = Order::without('lossPersons')->select('id', 'wusun_company_id', 'post_time', 'plan_type')->get();

        foreach ($orders as $order) {

            $this->info($order->id);

            if ($order->wusun_company_id) {

                $company = Company::find($order->wusun_company_id);

                $stats_update = [];

                if ($order->plan_type == Order::PLAN_TYPE_REPAIR)
                    $stats_update = ['order_repair_count' => DB::raw('order_repair_count + 1')];
                elseif ($order->plan_type == Order::PLAN_TYPE_MEDIATE)
                    $stats_update = ['order_mediate_count' => DB::raw('order_mediate_count + 1')];

                OrderDailyStats::updateOrCreate([
                    'company_id' => $order->wusun_company_id,
                    'parent_id' => $company->parent_id,
                    'date' => substr($order->post_time, 0, 10),
                ], array_merge($stats_update, [
                    'order_count' => DB::raw('order_count + 1'),
                ]));

                if ($company->parent_id) { // 同时更新上级工单数量
                    $parentCompany = Company::find($company->parent_id);

                    OrderDailyStats::updateOrCreate([
                        'company_id' => $parentCompany->id,
                        'parent_id' => $parentCompany->parent_id,
                        'date' => substr($order->post_time, 0, 10),
                    ], array_merge($stats_update, [
                        'order_count' => DB::raw('order_count + 1'),
                    ]));

                    if ($parentCompany->parent_id) {
                        $_parentCompany = Company::find($parentCompany->parent_id);
                        OrderDailyStats::updateOrCreate([
                            'company_id' => $_parentCompany->id,
                            'parent_id' => $_parentCompany->parent_id,
                            'date' => substr($order->post_time, 0, 10),
                        ], array_merge($stats_update, [
                            'order_count' => DB::raw('order_count + 1'),
                        ]));
                    }
                }
            }
        }
    }
}
