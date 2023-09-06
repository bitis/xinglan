<?php

namespace App\Jobs;

use App\Models\History;
use App\Models\Order;
use App\Models\OrderQuotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class QuotaHistory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected OrderQuotation $quotation)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->quotation->refresh();
        $quotation = $this->quotation;
        $order = Order::find($quotation->order_id);

        History::where('order_number', $order->order_number)->delete();

        $data = [];

        foreach ($quotation->items as $item) {
            $data[] = [
                'province' => $order->province,
                'city' => $order->city,
                'area' => $order->area,
                'name' => $item->name,
                'specs' => $item->specs,
                'unit' => $item->unit,
                'price' => $item->price,
                'remark' => $item->remark,
                'order_number' => $order->order_number,
                'created_at' => $quotation->checked_at,
                'updated_at' => $quotation->checked_at
            ];
        }

        History::insert($data);
    }
}
