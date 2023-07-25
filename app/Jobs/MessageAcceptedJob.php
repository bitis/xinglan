<?php

namespace App\Jobs;

use App\Models\Enumerations\MessageType;
use App\Models\Message;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MessageAcceptedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Message $message)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $order = Order::find($this->message->order_id);

        switch ($this->message->type) {
            case MessageType::NewOrder->value:// 物损公司接受派单
                $order->accept_check_wusun_at = $this->message->accept_at;
                $order->save();
                break;
            case MessageType::NewCheckTask->value: // 查勘接受查勘任务
                $order->wusun_check_accept_at = $this->message->accept_at;
                $order->save();
                break;
        }
    }
}
