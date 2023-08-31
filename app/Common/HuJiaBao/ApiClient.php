<?php

namespace App\Common\HuJiaBao;

use App\Models\HuJiaBao\Files;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ApiClient
{
    protected string $host = 'http://hujiabao-sandbox.hujiabao.com';

    protected string $token = 'Bearer MOATJJo0mhkNAOlOOpj6VfFq-mRJRGqJ';

    protected Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => $this->host,
            'headers' => [
                'Authorization' => $this->token,
                'Content-Type' => 'multipart/form-data',
            ]
        ]);
    }

    public function survey()
    {

    }

    /**
     * 业务请求
     *
     * @param $url
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function request($url, array $data = []): array
    {
        try {
            $response = $this->client->post($url, $data);

            $result = json_decode($response->getBody()->getContents(), true);

            if ($result['Head']['ResponseCode'] == 0)
                throw new Exception($result['Head']['ErrorMessage']);

            return $result;

        } catch (GuzzleException $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * 文件上传
     *
     * @param $files
     * @param $BusinessType
     * @param $BusinessNo
     * @param $Directory
     * @return array
     * @throws Exception
     */
    public function upload($files, $BusinessType, $BusinessNo, $Directory): array
    {
        $url = '/attachment-core/attachment/v1/uploadMulti';

        $multipart = array_map(function (UploadedFile $file) {
            $fileName = $file->hashName();

            Storage::disk('qcloud')->put('uploads/' . $fileName, $file->getContent());

            return [
                'name' => 'Files',
                'contents' => $file->getContent(),
                'filename' => $fileName
            ];
        }, $files);

        try {
            $response = $this->client->post($url, [
                'multipart' => $multipart,
                'query' => [
                    'BusinessType' => $BusinessType,
                    'BusinessNo' => $BusinessNo,
                    'Directory' => $Directory,
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if ($result['Status'] == 'BLOCK')
                throw new Exception($result['Messages']['Message']);

            foreach ($result['Model'] as &$item) {
                $item['url'] = '/uploads/' . $item['OrgFileName'];
                unset($item['OldFileData']);
            }

            Files::insert($result['Model']);

            return $result['Model'];

        } catch (GuzzleException $exception) {
            throw $exception;
        }
    }

    /**
     * 提交查勘资料
     *
     * @param $data
     * @return array
     * @throws Exception
     */
    public function investigation($data): array
    {
        return $this->request('/claim-core/claim/v1/investigation', ['SubClaimInfo' => $data]);
    }
}
