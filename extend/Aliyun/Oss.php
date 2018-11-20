<?php

/**
 * Created by PhpStorm.
 * User: Amazing
 * Date: 2017/12/5
 * Time: 11:27
 */
namespace Aliyun;
use OSS\OssClient;
use OSS\Core\OssException;
require('vendor/aliyuncs/oss-sdk-php/autoload.php');
/**
 * Class Common
 *
 * 示例程序【Samples/*.php】 的Common类，用于获取OssClient实例和其他公用方法
 */
class Oss
{
    public   $client;
    private  $bucket;


    public function  __construct()
    {
        $this->getOssClient();
        $this->bucket = config('aliyun.OSS_TEST_BUCKET');
    }


    //创建存储空间
    public function newbucket(){

        $bucket = "wjlovedj";
        try {
            $ossClient = new OssClient(config('aliyun.OSS_ACCESS_ID'), config('aliyun.OSS_ACCESS_KEY'), config('aliyun.OSS_ENDPOINT'));
            $ossClient->createBucket($bucket);
        } catch (OssException $e) {
            print $e->getMessage();
        }
    }

    //文件上传
    public function fileupload($filename,$path){
        // 存储空间名称
        $bucket= "meetuuu";
        // 文件名称
        $object = "wdzh/".$filename;
        // <yourLocalFile>由本地文件路径加文件名包括后缀组成，例如/users/local/myfile.txt
        $filePath = "$path";

        try{
            $ossClient = new OssClient(config('aliyun.OSS_ACCESS_ID'), config('aliyun.OSS_ACCESS_KEY'), config('aliyun.OSS_ENDPOINT'));
            $ossClient->uploadFile($bucket, $object, $filePath);
        } catch(OssException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }

    }



    public function bucketlist($folder_name){
        // 存储空间名称
        $bucket= "meetuuu";

        $ossClient = new OssClient(config('aliyun.OSS_ACCESS_ID'), config('aliyun.OSS_ACCESS_KEY'), config('aliyun.OSS_ENDPOINT'));
        $prefix = $folder_name;
        $delimiter = '/';
        $nextMarker = '';
        $maxkeys = 10;
        $options = array(
            'delimiter' => $delimiter,
            'prefix' => $prefix,
            'max-keys' => $maxkeys,
            'marker' => $nextMarker,
        );
        try {
            $listObjectInfo = $ossClient->listObjects($bucket, $options);
        } catch (OssException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
        //print(__FUNCTION__ . ": OK" . "\n");
        $objectList = $listObjectInfo->getObjectList(); // object list
        $prefixList = $listObjectInfo->getPrefixList(); // directory list
        if (!empty($objectList)) {
            //print("objectList:\n");

            $video_folder = [];
            foreach ($objectList as $objectInfo) {
                $video_folder[] = $objectInfo->getKey() . "\n";
            }
            return $video_folder;
        }
        if (!empty($prefixList)) {
            //print("prefixList: \n");

            $oss_folder = [];
            foreach ($prefixList as $prefixInfo) {
                //print($prefixInfo->getPrefix() . "\n");
                $oss_folder[] = $prefixInfo->getPrefix() . "\n";

            }
            return $oss_folder;

        }

    }

    /**
     * 根据Config配置，得到一个OssClient实例
     *
     * @return OssClient 一个OssClient实例
     */
    public  function getOssClient()
    {
        try {
            if(!isset($this->client))
            {
                $this->client =   $ossClient = new OssClient(config('aliyun.OSS_ACCESS_ID'), config('aliyun.OSS_ACCESS_KEY'), config('aliyun.OSS_ENDPOINT'), false);
            }

        } catch (OssException $e) {
            printf(__FUNCTION__ . "creating OssClient instance: FAILED\n");
            printf($e->getMessage() . "\n");
        }

    }
    /**
     * 上传文件到oss并删除本地文件
     * @param  string $path 文件路径
     * @return bollear      是否上传
     */
    public function upload($path){
        // 先统一去除左侧的.或者/ 再添加./
        $oss_path=ltrim($path,'./');
        $path='./'.$oss_path;

        if (file_exists($path)) {
            // 上传到oss
            $this->client->uploadFile($this->bucket,$oss_path,$path);
            // 如需上传到oss后 自动删除本地的文件 则删除下面的注释
            // unlink($path);
            return true;
        }
        return false;
    }


    /**
     * 删除oss上指定文件
     * @param  string $object 文件路径 例如删除 /Public/README.md文件  传Public/README.md 即可
     */
    public function delete_object($object){
        $object=ltrim($object,'./');
        $res= $this->client->deleteObject($this->bucket,$object);
        return $res;
    }





}

