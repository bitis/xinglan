<?php

namespace App\Common\Messages;

use Overtrue\EasySms\Contracts\GatewayInterface;
use Overtrue\EasySms\Message;

class CheckNotify extends Message
{
    protected $template = '1918425';

    public function __construct(protected $wusunCompanyName, protected $insuranceCompanyName, protected $caseNumber)
    {
        parent::__construct();
    }

    public function getData(GatewayInterface $gateway = null): array
    {
        return [
            $this->wusunCompanyName,
            $this->caseNumber
        ];
    }
}
