<?php

namespace App\Common\Messages;

use Overtrue\EasySms\Contracts\GatewayInterface;
use Overtrue\EasySms\Message;

class VerificationCode extends Message
{
    protected $template = 'SMS_180357551';

    public function __construct(protected $code)
    {
        parent::__construct();
    }

    public function getData(GatewayInterface $gateway = null): array
    {
        return [
            'code' => $this->code,
        ];
    }
}
