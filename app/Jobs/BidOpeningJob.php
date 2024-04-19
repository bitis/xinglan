<?php

namespace App\Jobs;

use App\Common\Messages\WinBidNotify;
use App\Models\BidOption;
use App\Models\Company;
use App\Models\ConsumerOrderDailyStats;
use App\Models\Enumerations\MessageType;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderDailyStats;
use App\Models\OrderLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Overtrue\EasySms\EasySms;

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
    public function handle(EasySms $easySms): void
    {
        $order = Order::find($this->order_id);

        try {
            $option = BidOption::findByCompany($order->insurance_company_id);

            if ($option && !$option->auto) return;

            if (!$order or $order->bid_status != 0) return;

            $quotations = $order->quotations()->where('total_price', '>', 0)->orderBy('total_price', 'asc')->get();

            if (!$quotations->count()) return;

            $order->bid_status = Order::BID_STATUS_FINISHED;

            foreach ($quotations as $index => $quotation) {
                if ($index == 0) {
                    $quotation->win = 1;

                    $company = Company::find($quotation->company_id);

                    OrderDailyStats::updateOrCreate([
                        'company_id' => $company->id,
                        'parent_id' => $company->parent_id,
                        'date' => substr($order->post_time, 0, 10),
                    ], [
                        'order_count' => DB::raw('order_count + 1')
                    ]);

                    ConsumerOrderDailyStats::updateOrCreate([
                        'company_id' => $company->id,
                        'date' => substr($order->post_time, 0, 10),
                        'insurance_company_id' => $order->insurance_company_id
                    ], [
                        'order_count' => DB::raw('order_count + 1')
                    ]);

                    if ($company->parent_id) { // 同时更新上级工单数量
                        $parentCompany = Company::find($company->parent_id);

                        OrderDailyStats::updateOrCreate([
                            'company_id' => $parentCompany->id,
                            'parent_id' => $parentCompany->parent_id,
                            'date' => substr($order->post_time, 0, 10),
                        ], [
                            'order_count' => DB::raw('order_count + 1')
                        ]);

                        if ($parentCompany->parent_id) {
                            $_parentCompany = Company::find($parentCompany->parent_id);
                            OrderDailyStats::updateOrCreate([
                                'company_id' => $_parentCompany->id,
                                'parent_id' => $_parentCompany->parent_id,
                                'date' => substr($order->post_time, 0, 10),
                            ], [
                                'order_count' => DB::raw('order_count + 1')
                            ]);
                        }
                    }

                    $order->wusun_company_id = $quotation->company_id;
                    $order->wusun_company_name = $company?->name;
                    $order->confim_wusun_at = $order->bid_end_time;
                    $order->bid_win_price = $quotation->total_price;

                    OrderLog::create([
                        'order_id' => $order->id,
                        'type' => OrderLog::TYPE_BID_OPEN,
                        'creator_id' => 0,
                        'creator_name' => '系统',
                        'creator_company_id' => $order->insurance_company_id,
                        'creator_company_name' => Company::find($order->insurance_company_id)?->name,
                        'content' => '自动开标；中标单位：' . $company?->name,
                        'platform' => '',
                    ]);

                    // Message
                    $message = new Message([
                        'send_company_id' => $order->insurance_company_id,
                        'to_company_id' => $order->wusun_company_id,
                        'type' => MessageType::OrderDispatch->value,
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'case_number' => $order->case_number,
                        'goods_types' => $order->goods_types,
                        'remark' => $order->remark,
                        'status' => 0,
                    ]);
                    $message->save();

                    $insuranceCompany = Company::find($order->insurance_company_id);

                    try {
                        $easySms->send(
                            $company->contract_phone,
                            new WinBidNotify($company->name, $insuranceCompany->name, $order->case_number)
                        );

                        if ($company->backup_contract_phone)
                            $easySms->send(
                                $company->backup_contract_phone,
                                new WinBidNotify($company->name, $insuranceCompany->name, $order->case_number)
                            );
                    } catch (\Exception $exception) {
                    }
                } else {
                    $quotation->win = 2;
                }
                $quotation->bid_end_time = $order->bid_end_time;
                $quotation->save();
            }

            $order->save();
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            $this->fail($exception);
        }

    }
}
