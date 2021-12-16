<?php

namespace EsSwoole\Base\Common;

use EasySwoole\Component\Singleton;
use EasySwoole\Oss\AliYun;
use EasySwoole\Utility\File;

/**
 * 文件上传类
 *
 * @author wangy.3 <wangy.3@jifenn.com>
 */
class FileUpload
{
    use Singleton;

    protected $ossBucket;
    protected $ossPath;
    protected $endpoint;

    private $ossClient;
    private $localPath = EASYSWOOLE_TEMP_DIR . '/upload';
    private $allowExt  = [
        'jpg',
        'jpeg',
        'gif',
        'png',
        'bmp',
    ];

    /**
     * 初始化阿里云OSS
     */
    public function __construct()
    {
        $aliyunConfig    = config("esCommon.oss.aliyun");
        $this->ossBucket = $aliyunConfig['oss_bucket'] ?? '';
        $this->ossPath   = $aliyunConfig['oss_path'] ?? '';
        $this->endpoint  = $aliyunConfig['endpoint'] ?? '';
        $clientConfig    = new AliYun\Config([
            'accessKeyId'     => $aliyunConfig['access_key_id'] ?? '',
            'accessKeySecret' => $aliyunConfig['access_key_secret'] ?? '',
            'endpoint'        => $aliyunConfig['endpoint'] ?? '',
        ]);
        $this->ossClient = new AliYun\OssClient($clientConfig);
    }

    /**
     * 上传到本地
     *
     * @param \EasySwoole\Http\Message\UploadFile $fileObj
     *
     * @return array
     */
    public function upload($fileObj): array
    {
        if (!$fileObj instanceof \EasySwoole\Http\Message\UploadFile) { // 仅支持easyswoole上传文件对象
            return Api::fail('文件有误');
        }

        $data['getStream']          = $fileObj->getSize();            // 文件大小（字节）
        $data['getClientFilename']  = $fileObj->getClientFilename();  // 源文件名称
        $data['getClientMediaType'] = $fileObj->getClientMediaType(); // 文件类型

        $localPath = $this->getLocalPath($data['getClientFilename']); // 设置保存文件路径
        if (!$localPath) {
            return Api::fail('upload failed.');
        }

        try {
            $fileObj->moveTo($localPath); // 保存文件
        } catch (\Throwable $throwable) {
            return Api::fail('upload failed.');
        }

        return Api::success(['local_file' => $localPath]);
    }

    /**
     * 获取本地上传路径
     *
     * @param string $filename
     *
     * @return string|bool
     */
    private function getLocalPath(string $filename)
    {
        $pathinfo  = pathinfo($filename);
        $extension = $pathinfo['extension']; // 获取文件后缀
        $dir       = $this->localPath . '/' . date('Ymd');
        if (!File::createDirectory($dir)) {
            return false;
        }

        $basename = md5(microtime(true) . $filename) . '.' . $extension;

        return $dir . '/' . $basename;
    }

    /**
     * 上传到OSS
     *
     * @param \EasySwoole\Http\Message\UploadFile|string $fileObj  上传文件对象/本地文件路径/base64图片
     * @param string                                     $filename 上传文件名，base64图片流可用
     *
     * @return array
     */
    public function ossUpload($fileObj, string $filename = ''): array
    {
        if ($fileObj instanceof \EasySwoole\Http\Message\UploadFile) { // 上传文件对象
            $res              = $this->upload($fileObj);
            $filenameWithPath = $res['data']['local_file'] ?? '';

        } else if (is_string($fileObj) && is_file($fileObj)) { // 本地文件
            $filenameWithPath = $fileObj;
        } else if (!empty($filename) && is_string($fileObj)) { // base64图片流
            $res              = $this->uploadImageByBase64($fileObj, $filename);
            $filenameWithPath = $res['data']['local_file'] ?? '';
        } else {
            return Api::fail('上传文件有误');
        }

        if (empty($filenameWithPath)) {
            return Api::fail('upload failed.');
        }

        $fileArr   = explode('/', $filenameWithPath);
        $filename  = $fileArr[count($fileArr) - 1];
        $ossObject = $this->ossPath . date('Ymd/') . $filename;
        try {
            $this->ossClient->uploadFile($this->ossBucket, $ossObject, $filenameWithPath); // 上传至OSS
            if (true !== $this->ossClient->doesObjectExist($this->ossBucket, $ossObject)) { // 上传后在OSS未获取到文件
                return Api::fail('upload failed.');
            }
        } catch (\Throwable $throwable) {
            return Api::fail('upload failed.');
        }

        return Api::success(['file_url' => 'http://' . $this->ossBucket . '.' . $this->endpoint . '/' . $ossObject]);
    }

    /**
     * 上传base64图片流到本地
     *
     * @param string $baseInfo //流数据
     * @param string $filename //文件名
     *
     * @return array
     */
    function uploadImageByBase64(string $baseInfo, string $filename): array
    {
        preg_match('/^(data:\s*image\/(\w+);base64,)/', $baseInfo, $result);
        $type       = $result[2] ?? ''; // 文件类型
        $extensions = strtolower($type);
        if (!in_array($extensions, $this->allowExt)) {
            return Api::fail('上传的文件不在允许内。');
        }

        $data = base64_decode(str_replace($result[1] ?? '', '', $baseInfo));
        if (false === $data) {
            return Api::fail('文件数据异常');
        }

        $filename  = explode('.', $filename)[0] . '.' . $extensions;
        $localPath = $this->getLocalPath($filename);
        if (false === $localPath) {
            return Api::fail('upload failed.');
        }

        file_put_contents($localPath, $data); //写入文件并保存

        return Api::success(['local_file' => $localPath]);
    }
}