<?php

namespace App\Common\Messages;

use Overtrue\EasySms\Contracts\GatewayInterface;
use Overtrue\EasySms\Message;
use function Symfony\Component\Translation\t;

class CheckNotify extends Message
{
    protected $template = '1877302';

    public function __construct(protected $wusunCompanyName, protected $insuranceCompanyName, protected $caseNumber)
    {
        parent::__construct();
    }

    public function getData(GatewayInterface $gateway = null): array
    {
        return [
            $this->wusunCompanyName,
            $this->insuranceCompanyName,
            $this->caseNumber
        ];
    }
}