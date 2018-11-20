<?php
require_once 'aliyun-php-sdk-core/Config.php';
use think\Controller;
use imm\Request\V20170906 as Imm;
class alywdcz extends Controller{
    public function index($filename,$outputpath){

        $iClientProfile = DefaultProfile::getProfile(
            "cn-shanghai",                   # 您的 Region ID
            "4lPzapbWtDWLX2TN",               # 您的 AccessKey ID
            "PwghWOjP7vrPwo2MhEnz7CqilZ25xk"            # 您的 AccessKey Secret
        );

        $client = new DefaultAcsClient($iClientProfile);

        // 设置您的项目名称，请确保您已经在控制台创建该项目
        $projectName = "wdzh";
        // 创建文档转换任务
        $request = new Imm\CreateOfficeConversionTaskRequest();

        $request->setProject($projectName);

        // 设置待转换对文件OSS路径
        $ossurl = "oss://meetuuu/wdzh/".$filename;
        $request->setSrcUri($ossurl);

        // 设置文件输出格式
        $request->setTgtType("png");
        // 设置转换后的输出路径
        $setTgtUri = "oss://meetuuu/wdzh/zhuanhuanhou/".$outputpath;
        $request->setTgtUri($setTgtUri);

        $response = $client->getAcsResponse($request);
        $this->success('上传成功!', 'Channel/index');
        exit;
        print_r($response);
        print_r("韩国瑜");exit;
        // 获取文档转换任务结果
        // 由于转换结果非实时给出，需要轮询该接口
        // 设置最大轮询次数
        $maxRetryCount = 30;
        // 设置每次轮询的间隔
        $retryDelay = 1;
        $request = new Imm\GetOfficeConversionTaskRequest();
        $request->setTaskId($response->TaskId);
        $request->setProject($projectName);
        while($maxRetryCount--){
            $response = $client->getAcsResponse($request);
            print_r($response);
            if($response->Status != 'Running') break;
            sleep($retryDelay);
        }
    }
}
?>