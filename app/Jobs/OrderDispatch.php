<?php

namespace App\Jobs;

use App\Models\BidOption;
use App\Models\Company;
use App\Models\CompanyProvider;
use App\Models\Enumerations\OrderCheckStatus;
use App\Models\Enumerations\OrderDispatchRole;
use App\Models\Enumerations\Status;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OrderDispatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Order $order)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->order->dispatched) return;

        $status = Status::Normal->value;

        $company = Company::find($this->order->insurance_company_id);

        // 无可用外协单位
        if (!$providers = CompanyProvider::where('company_id', $company->id)->where('status', $status)->get()) return;

        $config = BidOption::where('company_id', $company->id)->first();

        // 未配置派单规则
        if (empty($config)) return;

        $dispatchRole = $config->order_dispatch_role;

        if ($dispatchRole == OrderDispatchRole::Queue) {
            $index = $company->queue_index % count($providers);

            $provider = $providers[$index];

            $this->order->fill([
                'check_wusun_company_id' => $provider->provider_id,
                'check_wusun_company_name' => $provider->provider_name,
                'dispatch_check_wusun_at' => now()->toDateTimeString(),
                'check_status' => OrderCheckStatus::DispatchCompany
            ]);

            $this->order->save();

            $company->queue_index++;
            $company->save();
        }

    }
}
