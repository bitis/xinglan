<?php

namespace App\Console\Commands;

use App\Models\GoodsPrice;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:image-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(Client $client)
    {
        GoodsPrice::where('status', 1)
            ->whereNotNull('describe_image')
            ->orderBy('id')
            ->chunk(100, function ($goods) use ($client) {
                $this->info(now()->toDateTimeString());
                foreach ($goods as $index => $good) {
                    if ($good->describe_image && Str::startsWith($good->describe_image, 'https')) {
                        $content = $client->get($good->describe_image)->getBody()->getContents();
                        $fileName = '/upload/goods/' . date('Ymd') . '/' . Str::random(40) . '.' . pathinfo($good->describe_image)['extension'];
                        if (Storage::disk('qcloud')->put($fileName, $content)) {
                            $good->describe_image = $fileName;
                            $good->status = 0;
                            $good->save();
                        }
                        $this->info($index . ":\t" . $good->describe_image);
                    }
                }
            });
    }
}
