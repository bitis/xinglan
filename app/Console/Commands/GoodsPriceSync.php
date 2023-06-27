<?php

namespace App\Console\Commands;

use App\Models\GoodsPrice;
use App\Models\GoodsPriceCat;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class GoodsPriceSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:goods-price-sync';

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
//        $this->cats();

        $http = new Client();
        $page = 1;
        $pageSize = 50;
        do {
            $this->info(now()->toDateTimeString() . "\tPage:\t" . $page);
            $response = $http->post('https://api.retechcn.com/loss-config/query/list', [
                'headers' => [
                    'Authorization' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJzdWIiOiIxMjc5OnBjIiwiaWF0IjoxNjg3ODUxMjQzLCJleHAiOjE2ODg0NTYwNDN9.RtMoXfz-fSjCwn3rDOXca8tEBVKHBDJbxgulMLQj8tAYTsbOekWggodgphtUMjobyEgg7TAIcG_BDwJOOAkexw'
                ],
                'json' => [
                    'pageNum' => $page,
                    'pageSize' => $pageSize,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true)['data'];

            foreach ($data['list'] as $datum) {
                GoodsPrice::create([
                    'id' => $datum['id'],
                    'company_id' => $datum['companyId'],
                    'company_name' => $datum['companyName'],
                    'province' => $datum['provinceName'],
                    'city' => $datum['cityName'],
                    'region' => $datum['regionName'],
                    'cat_id' => $datum['cateId'],
                    'cat_name' => $datum['cateName'],
                    'cat_parent_id' => $datum['cateParentId'],
                    'product_name' => $datum['productName'],
                    'spec' => $datum['spec'],
                    'unit' => $datum['unit'],
                    'brand' => $datum['brand'],
                    'unit_price' => $datum['unitPrice'],
                    'describe_image' => $datum['describeImageUrl'],
                    'remark' => $datum['remark'],
                    'status' => $datum['isEnable'],
                ]);
            }

            $page = $data['pageNum'] + 1;

        } while ($data['total'] / $pageSize > $data['pageNum']);
    }

    public function cats()
    {
        $json = '{"code":1,"msg":"执行成功!","data":[{"id":"1","parentId":"0","cateName":"房屋建筑类","sort":1,"cateLevel":1,"remark":"","createTime":"2022-07-18T09:06:46.000+00:00","createUser":"admin","children":[{"id":"19","parentId":"1","cateName":"房建施工类","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:15:11.000+00:00","createUser":"admin"},{"id":"20","parentId":"1","cateName":"彩钢瓦、石棉瓦等瓦类","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:15:38.000+00:00","createUser":"admin"},{"id":"21","parentId":"1","cateName":"玻璃类","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:16:04.000+00:00","createUser":"admin"},{"id":"22","parentId":"1","cateName":"砖、砂石、灰土","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:16:19.000+00:00","createUser":"admin"},{"id":"23","parentId":"1","cateName":"钢筋","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:16:32.000+00:00","createUser":"admin"},{"id":"24","parentId":"1","cateName":"水泥、混凝土","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:16:45.000+00:00","createUser":"admin"},{"id":"25","parentId":"1","cateName":"铝合金门窗","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:16:58.000+00:00","createUser":"admin"},{"id":"26","parentId":"1","cateName":"防腐油漆","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:17:11.000+00:00","createUser":"admin"},{"id":"27","parentId":"1","cateName":"石材","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:17:26.000+00:00","createUser":"admin"},{"id":"28","parentId":"1","cateName":"瓷砖","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:17:38.000+00:00","createUser":"admin"},{"id":"29","parentId":"1","cateName":"地坪、草坪、跑道","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:17:51.000+00:00","createUser":"admin"}]},{"id":"2","parentId":"0","cateName":"交通设施类","sort":1,"cateLevel":1,"remark":"","createTime":"2022-07-18T09:07:00.000+00:00","createUser":"admin","children":[{"id":"30","parentId":"2","cateName":"摄像头","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:18:11.000+00:00","createUser":"admin"},{"id":"31","parentId":"2","cateName":"护栏","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:18:33.000+00:00","createUser":"admin"},{"id":"32","parentId":"2","cateName":"防护网","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:18:49.000+00:00","createUser":"admin"},{"id":"33","parentId":"2","cateName":"防撞设施","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:19:02.000+00:00","createUser":"admin"},{"id":"34","parentId":"2","cateName":"灯杆灯具","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:19:14.000+00:00","createUser":"admin"},{"id":"35","parentId":"2","cateName":"市政道路","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:19:34.000+00:00","createUser":"admin"},{"id":"36","parentId":"2","cateName":"标识标牌","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:19:54.000+00:00","createUser":"admin"}]},{"id":"3","parentId":"0","cateName":"园林绿化类","sort":1,"cateLevel":1,"remark":"","createTime":"2022-07-18T09:07:13.000+00:00","createUser":"admin","children":[{"id":"37","parentId":"3","cateName":"绿化苗木","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:20:46.000+00:00","createUser":"admin"}]},{"id":"4","parentId":"0","cateName":"车库卷闸类","sort":1,"cateLevel":1,"remark":"","createTime":"2022-07-18T09:07:44.000+00:00","createUser":"admin","children":[{"id":"38","parentId":"4","cateName":"卷帘门","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:21:01.000+00:00","createUser":"admin"}]},{"id":"5","parentId":"0","cateName":"岗亭道闸类","sort":1,"cateLevel":1,"remark":"","createTime":"2022-07-18T09:07:58.000+00:00","createUser":"admin","children":[{"id":"39","parentId":"5","cateName":"道闸控制器及道闸杆","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:21:53.000+00:00","createUser":"admin"}]},{"id":"7","parentId":"0","cateName":"电网资产类","sort":1,"cateLevel":1,"remark":"","createTime":"2022-07-18T09:08:53.000+00:00","createUser":"admin","children":[{"id":"40","parentId":"7","cateName":"电线电缆","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:22:14.000+00:00","createUser":"admin","children":[{"id":"46","parentId":"40","cateName":"铜芯","sort":1,"cateLevel":3,"remark":"","createTime":"2022-07-18T10:10:15.000+00:00","createUser":"admin"},{"id":"47","parentId":"40","cateName":"铝芯","sort":1,"cateLevel":3,"remark":"","createTime":"2022-07-18T10:10:33.000+00:00","createUser":"admin"}]},{"id":"41","parentId":"7","cateName":"桥架、线槽及附件","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:22:41.000+00:00","createUser":"admin"},{"id":"42","parentId":"7","cateName":"电杆","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:22:53.000+00:00","createUser":"admin"},{"id":"43","parentId":"7","cateName":"变电站、变压器、配电箱、电器柜类","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:23:07.000+00:00","createUser":"admin"}]},{"id":"8","parentId":"0","cateName":"钢结构类","sort":1,"cateLevel":1,"remark":"","createTime":"2022-07-18T09:09:09.000+00:00","createUser":"admin","children":[{"id":"44","parentId":"8","cateName":"钢管型材类","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:23:27.000+00:00","createUser":"admin"}]},{"id":"9","parentId":"0","cateName":"仿古建筑类","sort":1,"cateLevel":1,"remark":"","createTime":"2022-07-18T09:09:17.000+00:00","createUser":"admin","children":[{"id":"45","parentId":"9","cateName":"木材","sort":1,"cateLevel":2,"remark":"","createTime":"2022-07-18T09:23:48.000+00:00","createUser":"admin"}]},{"id":"16","parentId":"0","cateName":"人工","sort":1,"cateLevel":1,"remark":"","createTime":"2022-07-18T09:13:57.000+00:00","createUser":"admin"},{"id":"17","parentId":"0","cateName":"机械台班","sort":1,"cateLevel":1,"remark":"","createTime":"2022-07-18T09:14:16.000+00:00","createUser":"admin"},{"id":"55","parentId":"0","cateName":"安装工程","sort":1,"cateLevel":1,"remark":null,"createTime":"2022-08-26T07:23:24.000+00:00","createUser":"admin"},{"id":"56","parentId":"0","cateName":"市政工程","sort":1,"cateLevel":1,"remark":null,"createTime":"2022-08-26T07:23:35.000+00:00","createUser":"admin"},{"id":"57","parentId":"0","cateName":"轨道交通","sort":1,"cateLevel":1,"remark":null,"createTime":"2022-08-26T07:23:45.000+00:00","createUser":"admin"},{"id":"58","parentId":"0","cateName":"交通水运工程","sort":1,"cateLevel":1,"remark":null,"createTime":"2022-08-27T06:04:04.000+00:00","createUser":"admin"},{"id":"59","parentId":"0","cateName":"装饰装修工程","sort":1,"cateLevel":1,"remark":null,"createTime":"2022-08-27T08:55:00.000+00:00","createUser":"admin"},{"id":"60","parentId":"0","cateName":"家禽性畜类","sort":1,"cateLevel":1,"remark":null,"createTime":"2022-08-28T06:50:33.000+00:00","createUser":"admin"}]}';

        $data = json_decode($json, true)['data'];

        foreach ($data as $datum) {
            GoodsPriceCat::create([
                'id' => $datum['id'],
                'parent_id' => $datum['parentId'],
                'name' => $datum['cateName'],
                'level' => $datum['cateLevel'],
            ]);
            if (!empty($datum['children']))
                foreach ($datum['children'] as $child) {
                    GoodsPriceCat::create([
                        'id' => $child['id'],
                        'parent_id' => $child['parentId'],
                        'name' => $child['cateName'],
                        'level' => $child['cateLevel'],
                    ]);
                }
        }
    }
}
