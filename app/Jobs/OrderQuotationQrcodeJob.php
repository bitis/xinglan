<?php

namespace App\Jobs;

use App\Models\OrderQuotation;
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
    public function __construct(protected OrderQuotation $quotation)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $quotation = $this->quotation;

        $security_url = config('app.url') . '/quota/security/' . $quotation->security_code;

        $opt = new QROptions([
            'version' => 5,
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'eccLevel' => EccLevel::L,
            'imageBase64' => false,
            'bgColor' => [200, 150, 200],
            "scale" => 5,
        ]);

        $tempFile = (new QRCode($opt))->render($security_url);

        $file_name = 'security_code/' . md5($tempFile) . '.png';

        Storage::disk('oss')->put($file_name, $tempFile);

        $quotation->qrcode = $file_name;
        $quotation->save();
    }
}
