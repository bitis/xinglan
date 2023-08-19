<?php

namespace App\Console\Commands;

use App\Models\GoodsPrice;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BaseDataSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:base-data-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $http = new Client();
        $page = 1;
        $pageSize = 50;

        $area = ['北京市', '天津市', '河北省', '山西省', '内蒙古自治区', '辽宁省', '吉林省', '黑龙江省', '上海市', '江苏省', '浙江省', '安徽省', '福建省', '江西省', '山东省', '河南省', '湖北省', '湖南省', '广东省', '广西壮族自治区', '海南省', '重庆市', '四川省', '贵州省', '云南省', '西藏自治区', '陕西省', '甘肃省', '青海省', '宁夏回族自治区', '新疆维吾尔自治区', '台湾省'];

        do {
            $this->info(now()->toDateTimeString() . "\tPage:\t" . $page);
            $response = $http->post('https://api.retechcn.com/loss-config/query/base/data', [
                'headers' => [
                    'Authorization' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJzdWIiOiIxMjc5OnBjIiwiaWF0IjoxNjkyNDEzMjMyLCJleHAiOjE2OTMwMTgwMzJ9.WKaHvHtVftGCqpm-s07aQ7TFtImbnBkJRha2tRL0x343ALUlqBPwwXv-ZYdUuXmpd7VtCMfPEdx8XkOcmmxiIA'
                ],
                'json' => [
                    'pageNum' => $page,
                    'pageSize' => $pageSize,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true)['data'];

            try {
                DB::beginTransaction();
                foreach ($data['list'] as $datum) {
                    $insert = [];
                    foreach ($area as $item) {
                        $insert[] = [
                            'company_id' => $datum['toCompanyId'],
                            'company_name' => $datum['toCompanyName'],
                            'province' => $item,
                            'cat_id' => 61,
                            'cat_name' => '未分类',
                            'cat_parent_id' => 0,
                            'product_name' => $datum['itemName'],
                            'spec' => $datum['itemSpecification'],
                            'unit' => $datum['unit'],
                            'unit_price' => $datum['itemOneMoney'],
                            'remark' => $datum['remark'],
                            'created_at' => $datum['createTime'],
                            'updated_at' => $datum['createTime'],
                        ];

                    }
                    GoodsPrice::insert($insert);
                }
                $page = $data['pageNum'] + 1;
                DB::commit();
            } catch (\Exception $exception) {
                DB::rollBack();
                $this->error($exception->getMessage());
            }

        } while ($data['total'] / $pageSize > $data['pageNum']);
    }
}
