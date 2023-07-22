<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Enumerations\CheckStatus;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BidOpeningJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected int $order_id)
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $order = Order::find($this->order_id);

        if (!$order or $order->bid_status != 0) return;

        $quotations = $order->quotations()->where([
            'check_status' => CheckStatus::Accept->value,
            'submit' => 1
        ])->orderBy('total_price', 'asc')->get();

        if (!$quotations->count()) return;

        $order->bid_status = Order::BID_STATUS_FINISHED;

        foreach ($quotations as $index => $quotation) {
            if ($index == 0) {
                $quotation->win = 1;

                $order->wusun_company_id = $quotation->company_id;
                $order->wusun_company_name = Company::find($quotation->company_id)->name;
                $order->confim_wusun_at = $order->bid_end_time;
            } else {
                $quotation->win = 2;
            }
            $quotation->bid_end_time = $order->bid_end_time;
            $quotation->save();
        }

        $order->save();
    }
}
