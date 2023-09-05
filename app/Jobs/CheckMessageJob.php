<?php

namespace App\Jobs;

use App\Common\Messages\CheckNotify;
use App\Common\Messages\QuotaNotify;
use App\Models\Company;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Overtrue\EasySms\EasySms;

class CheckMessageJob implements ShouldQueue
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

        if (!$order->wusun_company_id) return;

        $wusunCompany = Company::find($order->wusun_company_id);
        $insuranceCompany = Company::find($order->insurance_company_id);

        $easySms->send(
            $wusunCompany->contract_phone,
            new CheckNotify($wusunCompany->name, $insuranceCompany->name, $order->case_number)
        );

        if ($wusunCompany->backup_contract_phone)
            $easySms->send(
                $wusunCompany->backup_contract_phone,
                new CheckNotify($wusunCompany->name, $insuranceCompany->name, $order->case_number)
            );
    }
}
