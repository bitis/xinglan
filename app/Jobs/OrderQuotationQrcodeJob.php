<?php

namespace App\Jobs;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class OrderQuotationQrcodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected $quotation)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $quotation = $this->quotation;

        $security_url = config('app.url') . '/security/code?=' . $quotation->security_code;

        $opt = new QROptions([
            'version' => 5,
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'eccLevel' => EccLevel::L,
            'imageBase64' => false,
            'bgColor' => [200, 150, 200],
            "scale" => 5,
        ]);

        $tempFile = (new QRCode($opt))->render($security_url);

        $file_name = md5($tempFile) . '.png';

        Storage::disk('oss')->put('security_code/' . $file_name, $tempFile);

        $quotation->qrcode = $security_url;
        $quotation->save();
    }
}
