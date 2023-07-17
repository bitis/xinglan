<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Enumerations\CheckStatus;
use App\Models\Order;
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
                $quotation = OrderQuotation::find(22);

        $tempFile = sys_get_temp_dir() . '/' . Str::random() . '.pdf';

        App::make('snappy.pdf.wrapper')->loadHTML(view('quota.table', ['quotation' => $quotation])->render())->save($tempFile);

        $fileContent = file_get_contents($tempFile);

        $ossFile = 'quota_bill/' . date('Ymd') . '/' . md5($fileContent) . '.pdf';

        Storage::disk('oss')->put($ossFile, $fileContent);

        $quotation->pdf = $ossFile;

        $quotation->save();
    }
}
