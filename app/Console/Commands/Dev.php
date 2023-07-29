<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\OrderQuotation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


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
    public function handle(): void
    {
        $quotation = OrderQuotation::find(34);

        $tempFile = sys_get_temp_dir() . '/' . Str::random() . '.pdf';

        App::make('snappy.pdf.wrapper')->loadHTML(view('quota.table', ['quotation' => $quotation])->render())->save($tempFile);

        $fileContent = file_get_contents($tempFile);

        $ossFile = '/quota_bill/' . date('Ymd') . '/' . md5($fileContent) . '.pdf';

        Storage::disk('qcloud')->put($ossFile, $fileContent);

        $quotation->company_name = Company::find($quotation->company_id)->name;

        $quotation->pdf = $ossFile;

        $quotation->save();
    }
}
