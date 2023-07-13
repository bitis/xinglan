<?php

namespace App\Jobs;

use App\Models\BidOption;
use App\Models\Company;
use App\Models\CompanyProvider;
use App\Models\Enumerations\CompanyType;
use App\Models\Enumerations\MessageType;
use App\Models\Enumerations\OrderCheckStatus;
use App\Models\Enumerations\OrderDispatchRole;
use App\Models\Enumerations\OrderStatus;
use App\Models\Enumerations\Status;
use App\Models\Message;
use App\Models\Order;
use App\Models\ProviderOption;
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

        /**
         * 物损公司自建工单直接派发给自己
         */
        if (Company::find($this->order->creator_company_id)->type == CompanyType::WuSun->value) {
            $provider = CompanyProvider::where('company_id', $company->id)
                ->where('provider_id', $this->order->creator_company_id)
                ->where('status', $status)
                ->first();

            goto CONFIRM_PROVIDER;
        }

        // 无可用外协单位
        if (!$providers = CompanyProvider::where('company_id', $company->id)->where('status', $status)->get()) return;

        $config = BidOption::where('company_id', $company->id)->first();

        $dispatchRole = empty($config) ? OrderDispatchRole::Queue->value : $config->order_dispatch_role;

        $provider = [];

        if ($dispatchRole == OrderDispatchRole::Queue->value) {
            $index = $company->queue_index % count($providers);

            $provider = $providers[$index];

            $company->queue_index++;
            $company->save();
        } elseif ($dispatchRole == OrderDispatchRole::Area->value) {
            $options = ProviderOption::where('company_id', $company->id)
                ->where('insurance_type', $this->order->insurance_type)
                ->where('province', $this->order->province)
                ->where('city', $this->order->city)
                ->where('status', $status)
                ->get();

            $available = [];

            foreach ($options as $option) {
                if (in_array($this->order->area, $option['area']))
                    $available[] = $option;
            }

            $provider = $this->dispatchByWeight($available);
        }

        CONFIRM_PROVIDER:

        if ($provider) {
            $this->order->fill([
                'check_wusun_company_id' => $provider->provider_id,
                'check_wusun_company_name' => $provider->provider_name,
                'dispatch_check_wusun_at' => now()->toDateTimeString(),
                'order_status' => OrderStatus::WaitCheck->value,
                'dispatched' => true
            ]);

            // Message
            $message = new Message([
                'send_company_id' => $this->order->insurance_company_id,
                'to_company_id' => $this->order->check_wusun_company_id,
                'type' => MessageType::NewOrder->value,
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'case_number' => $this->order->case_number,
                'goods_types' => $this->order->goods_types,
                'remark' => $this->order->remark,
                'status' => 0,
            ]);
            $message->save();
        }

        $this->order->save();
    }

    protected function dispatchByWeight(array $available)
    {
        switch (count($available)) {
            case 0:
                $provider = null;
                break;
            case 1:
                $provider = $available[0];
                break;
            default:
                $availableProviders = [];
                $totalWeight = 0;
                foreach ($available as $key => $item) {
                    $totalWeight += $item['weight'] * 100;
                    $availableProviders = array_pad($availableProviders, $totalWeight, $key);
                }

                $winner = $availableProviders[random_int(0, $totalWeight)];

                $provider = CompanyProvider::find($available[$winner]->relation_id);
        }

        return $provider;
    }
}
