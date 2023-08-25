<?php

namespace App\Common\HuJiaBao;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class ApiClient
{
    protected string $host = 'http://hujiabao-sandbox.hujiabao.com/easyclaim-core-v2/mmi/server/v1/serviceReceive';

    protected string $token = '';

    protected Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => $this->host,
            'headers' => [
                'Authorization' => $this->token,
                'Content-Type' => 'text/plain',
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

        $multipart = array_map(fn(UploadedFile $file) => [
            'name' => 'Files',
            'contents' => $file->getContent(),
            'filename' => $file->getClientOriginalName()
        ], $files);

        try {
            $response = $this->client->post($url, [
                'multipart' => $multipart,
                'form_params' => [
                    'BusinessType' => $BusinessType,
                    'BusinessNo' => $BusinessNo,
                    'Directory' => $Directory,
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if ($result['Status'] == 'BLOCK')
                throw new Exception($result['Messages']['Message']);

            return $result['Model'];

        } catch (GuzzleException $exception) {
            throw new Exception($exception->getMessage());
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
        return $this->request('/claim-core/claim/v1/investigation', $data);
    }
}
