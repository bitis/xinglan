<?php

namespace App\Jobs;

use App\Common\Messages\QuotaNotify;
use App\Models\Company;
use App\Models\CompanyProvider;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;

/**
 * 报价通知短信
 */
class QuotaMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Order $order)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(EasySms $easySms): void
    {
        $order = $this->order;

        $insuranceCompany = Company::find($order->insurance_company_id);

        $providers = CompanyProvider::where('company_id', $order->insurance_company_id)->get();

        foreach ($providers as $provider) {
            $wusunCompany = Company::find($provider->provider_id);

            try {
                $easySms->send(
                    $wusunCompany->contract_phone,
                    new QuotaNotify($provider->name, $insuranceCompany->name, $order->case_number)
                );
            } catch (NoGatewayAvailableException  $e) {
                Log::error('SMS_ERROR', $e->results);
            }
        }
    }
}
