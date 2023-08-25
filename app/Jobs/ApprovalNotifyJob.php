<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use JPush\Client;

class ApprovalNotifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    /**
     * @var array
     */
    protected array $extras;

    /**
     * Create a new job instance.
     */
    public function __construct(int $user_id, $extras = [])
    {
        $this->user = User::find($user_id);
        $this->extras = $extras;
    }

    /**
     * Execute the job.
     */
    public function handle(Client $client): void
    {
        if (empty($this->user->push_id)) return;

        $android = [
            'title' => '您有新的审批项待处理',
            'sound' => 'sound',
            'channel_id' => 'jpush_1',
            'alert_type' => 1,
            'badge_add_num' => 1,
            'extras' => $this->extras,
        ];

        $ios = [
            'sound' => 'sound',
            'extras' => $this->extras
        ];

//            if ($extras['event'] == 'resetBadge') {
//                unset($ios['sound']);
//                unset($android['alert_type']);
//                $ios['badge'] = 0;
//            }

        $client->push()
            ->setPlatform('all')
            ->addRegistrationId($this->user->push_id)
            ->androidNotification('您有新的审批项待处理', $android)
            ->iosNotification('您有新的审批项待处理', $ios)
            ->setOptions(5,)
            ->send();
    }
}
