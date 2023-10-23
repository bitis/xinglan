<?php

namespace App\Jobs;

use App\Models\BidOption;
use App\Models\Company;
use App\Models\CompanyProvider;
use App\Models\Enumerations\InsuranceType;
use App\Models\Enumerations\MessageType;
use App\Models\Enumerations\OrderDispatchRole;
use App\Models\Enumerations\Status;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderLog;
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

        $insuranceType = match ($this->order->insurance_type) {
            InsuranceType::Car->value => ['car_insurance' => 1],
            InsuranceType::Other->value => ['other_insurance' => 1],
            InsuranceType::CarPart->value => ['car_part' => 1],
        };

        $providers = CompanyProvider::where('company_id', $company->id)
            ->where($insuranceType)->where('status', $status)->get();

        // 无可用外协单位
        if (!count($providers)) return;

        $config = BidOption::where('company_id', $company->id)->first();

        $dispatchRole = empty($config) ? OrderDispatchRole::Queue->value : $config->order_dispatch_role;

        $provider = [];

        // 报案尾号匹配
        if ($this->order->case_number) {
            $lastChar = substr($this->order->case_number, -1);

            $mathOption = ProviderOption::where('company_id', $company->id)
                ->where('insurance_type', $this->order->insurance_type)
                ->whereRaw("find_in_set($lastChar, match_last_chars)")
                ->where('status', $status)
                ->first();

            $provider = CompanyProvider::find($mathOption?->relation_id);

            if ($provider) goto CONFIRM_PROVIDER;
        }

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
                'wusun_company_id' => $company->id,
                'wusun_company_name' => $company->name,
                'confim_wusun_at' => now()->toDateTimeString(),
                'bid_type' => Order::BID_TYPE_FENPAI,
                'bid_status' => Order::BID_STATUS_FINISHED,
                'bid_end_time' => now()->toDateTimeString(),
                'dispatched' => true
            ]);

            $providerCompany = Company::find($provider->provider_id);

            OrderLog::create([
                'order_id' => $this->order->id,
                'type' => OrderLog::TYPE_DISPATCH_CHECK,
                'creator_id' => 0,
                'creator_name' => '系统',
                'creator_company_id' => $company->id,
                'creator_company_name' => $company->name,
                'remark' => '根据系统配置规则派遣查勘服务商',
                'content' => '根据系统配置规则，派遣查勘服务商：' . $providerCompany->name,
                'platform' => 'system',
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

        CheckMessageJob::dispatch($this->order);

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
