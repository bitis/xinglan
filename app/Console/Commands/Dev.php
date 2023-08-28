<?php

namespace App\Console\Commands;

use App\Jobs\ApprovalNotifyJob;
use App\Jobs\QuotaBillPdfJob;
use App\Models\ApprovalOrderProcess;
use App\Models\OrderQuotation;
use Illuminate\Console\Command;
use JPush\Client;


class Dev extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dev';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(Client $client): void
    {
        $process = ApprovalOrderProcess::find(333);

        $extras = [
            'type' => 'approval',
            'order_id' => $process->order_id,
            'process_id' => $process->id,
            'creator_name' => $process->creator_name,
        ];

        $android = [
            'title' => '您有新的审批项待处理',
            'sound' => 'sound',
            'alert_type' => 1,
            'extras' => $extras,
        ];

        $ios = [
            'sound' => 'sound',
            'extras' => $extras
        ];

//            if ($extras['event'] == 'resetBadge') {
//                unset($ios['sound']);
//                unset($android['alert_type']);
//                $ios['badge'] = 0;
//            }

        $client->push()
            ->setPlatform('all')
            ->addRegistrationId('13065ffa4f1089c0a78')
            ->androidNotification('您有新的审批项待处理', $android)
            ->iosNotification('您有新的审批项待处理', $ios)
            ->setOptions(5,)
            ->send();
    }
}
