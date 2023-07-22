<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\OrderQuotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QuotaBillPdfJob implements ShouldQueue
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

        $tempFile = sys_get_temp_dir() . '/' . Str::random() . '.pdf';

        App::make('snappy.pdf.wrapper')->loadHTML(view('quota.table', ['quotation' => $quotation])->render())->save($tempFile);

        $fileContent = file_get_contents($tempFile);

        $ossFile = '/quota_bill/' . date('Ymd') . '/' . md5($fileContent) . '.pdf';

        Storage::disk('oss')->put($ossFile, $fileContent);

        $quotation->company_name = Company::find($quotation->company_id)->name;

        $quotation->pdf = $ossFile;

        $quotation->save();
    }
}
