<?php
namespace app\home\controller;
use app\home\controller\Aliyun;

/*
传入自定义参数，即传入应用名称和流名称
*/
$AppName = 'AppName';
$StreamName = 'StreamName';
/*
时间戳，有效时间
*/
$time = time() + 1800;
/*
加密key，即直播后台鉴权里面自行设置
*/
$key = 'zhibowj';
$strpush = "/$AppName/$StreamName-$time-0-0-$key";
/*
里面的直播推流中心服务器域名、vhost域名可根据自身实际情况进行设置
*/
$pushurl = "rtmp://video-center.alivecdn.com/$AppName/$StreamName?vhost=zhibo.meetv.com.cn&auth_key=$time-0-0-".md5($strpush);
$strviewrtmp = "/$AppName/$StreamName-$time-0-0-$key";
$strviewflv = "/$AppName/$StreamName.flv-$time-0-0-$key";
$strviewm3u8 = "/$AppName/$StreamName.m3u8-$time-0-0-$key";
$rtmpurl = "rtmp://live1.playzhan.com/$AppName/$StreamName?auth_key=$time-0-0-".md5($strviewrtmp);
$flvurl = "http://live1.playzhan.com/$AppName/$StreamName.flv?auth_key=$time-0-0-".md5($strviewflv);
$m3u8url = "http://live1.playzhan.com/$AppName/$StreamName.m3u8?auth_key=$time-0-0-".md5($strviewm3u8);
/*
打印推流地址，即通过鉴权签名后的推流地址
*/
//echo $pushurl.'<br>';
/*
打印三种直播协议播放地址，即鉴权后的播放地址
*/
/*echo $rtmpurl.'<br>';
echo $flvurl.'<br>';
echo $m3u8url.'<br>';*/
/**
 * Created by PhpStorm.
 * User: ForeverTime
 * Date: 2016/12/10
 * Time: 16:27
 */
class Ali_Lite{
    protected  $config;
    protected  $aliLive;

    public function __construct()
    {

        $this -> aliLive = new Aliyun();
    }




    /**
     * 查询在线人数
     * @param $domainName  直播域名
     * @param $appName     应用名
     * @param $streamName  推流名
     */
    public function describeLiveStreamOnlineUserNum($domainName,$appName,$streamName){
        $apiParams = array(
            'Action'=>'DescribeLiveStreamOnlineUserNum',
            'DomainName'=>$domainName,
            'AppName'=>$appName,
            'StreamName'=>$streamName,
        );
        return $this -> aliLive -> aliApi($apiParams,$credential="GET", $domain="cdn.aliyuncs.com");
    }


    /**
     * 获取某个域名或应用下的直播流操作记录
     * @param $domainName      域名
     * @param $appName         应用名
     * @param $streamName      推流名
     */
    public function describeLiveStreamsControlHistory($domainName,$appName,$startTime,$endTime){
        $apiParams = array(
            'Action'=>'DescribeLiveStreamsControlHistory',
            'DomainName'=>$domainName,
            'AppName'=>$appName,
            'StartTime'=>$startTime,
            'EndTime'=>$endTime,
        );
        return $this -> aliLive -> aliApi($apiParams,$credential="GET", $domain="cdn.aliyuncs.com");
    }

    /**
     * 查看指定域名下（或者指定域名下某个应用）的所有正在推的流的信息
     * @param $domainName       域名
     * @param $appName          应用名
     * @return bool|int|mixed
     */
    public function describeLiveStreamsOnlineList($domainName,$appName){
        $apiParams = array(
            'Action'=>'DescribeLiveStreamsOnlineList',
            'DomainName'=>$domainName,
            'AppName'=>$appName,
        );
        return $this -> aliLive -> aliApi($apiParams,$credential="GET", $domain="cdn.aliyuncs.com");
    }

    /**
     * 查询推流黑名单列表
     * @param $domainName       域名
     * @return bool|int|mixed
     */
    public function describeLiveStreamsBlockList($domainName){
        $apiParams = array(
            'Action'=>'DescribeLiveStreamsBlockList',
            'DomainName'=>$domainName,
        );
        return $this -> aliLive -> aliApi($apiParams,$credential="GET", $domain="cdn.aliyuncs.com");
    }

    /**
     * 生成推流地址
     * @param $streamName 用户专有名
     * @param $vhost 加速域名
     * @param $time 有效时间单位秒
     */
    public function getPushSteam($streamName,$vhost,$time=3600){
        $time = time()+$time;
        $videohost = $this->aliLive->video_host;
        $appName=$this->aliLive->appName;
        $privateKey=$this->aliLive->privateKey;
        if($privateKey){
            $auth_key =md5('/'.$appName.'/'.$streamName.'-'.$time.'-0-0-'.$privateKey);
            $url =$videohost.'/'.$appName.'/'.$streamName.'?vhost='.$vhost.'&auth_key='.$time.'-0-0-'.$auth_key;
        }else{
            $url = $videohost.'/'.$appName.'/'.$streamName.'?vhost='.$vhost;
        }
        return $url;
    }

    /**
     * 生成拉流地址
     * @param $streamName 用户专有名
     * @param $vhost 加速域名
     * @param $type 视频格式 支持rtmp、flv、m3u8三种格式
     */
    public function getPullSteam($streamName,$vhost,$time=3600,$type='rtmp'){
        $time = time()+$time;
        $appName=$this->aliLive->appName;
        $privateKey=$this->aliLive->privateKey;
        $url='';
        switch ($type){
            case 'rtmp':
                $host = 'rtmp://'.$vhost;
                $url = '/'.$appName.'/'.$streamName;
                break;
            case 'flv':
                $host = 'http://'.$vhost;
                $url = '/'.$appName.'/'.$streamName.'.flv';
                break;
            case 'm3u8':
                $host = 'http://'.$vhost;
                $url = '/'.$appName.'/'.$streamName.'.m3u8';
                break;
        }
        if($privateKey){
            $auth_key =md5($url.'-'.$time.'-0-0-'.$privateKey);
            $url = $host.$url.'?auth_key='.$time.'-0-0-'.$auth_key;
        }else{
            $url = $host.$url;
        }
        return $url;
    }

    /**
     * 禁止推流接口
     * @param $domainName   	 您的加速域名
     * @param $appName          应用名称
     * @param $streamName       流名称
     * @param $liveStareamName  用于指定主播推流还是客户端拉流, 目前支持”publisher” (主播推送)
     * @param $resumeTime       恢复流的时间 UTC时间 格式：2015-12-01T17:37:00Z
     * @return bool|int|mixed
     */
    public function forbid($streamName,$resumeTime,$domainName='www.test.com',$appName='xnl',$liveStreamType='publisher'){
        $apiParams = array(
            'Action'=>'ForbidLiveStream',
            'DomainName'=>$domainName,
            'AppName'=>$appName,
            'StreamName'=>$streamName,
            'LiveStreamType'=>$liveStreamType,
            'ResumeTime'=>$resumeTime
        );
        return $this -> aliLive -> aliApi($apiParams,$credential="GET", $domain="cdn.aliyuncs.com");
    }

    /**
     * 恢复直播流推送
     * @param $streamName              流名称
     * @param string $appName          应用名称
     * @param string $liveStreamType   用于指定主播推流还是客户端拉流, 目前支持”publisher” (主播推送)
     * @param string $domainName       您的加速域名
     */
    public function resumeLive($streamName,$domainName='www.test.top',$appName='xnl',$liveStreamType='publisher'){
        $apiParams = array(
            'Action'=>'ResumeLiveStream',
            'DomainName'=>$domainName,
            'AppName'=>$appName,
            'StreamName'=>$streamName,
            'LiveStreamType'=>$liveStreamType,
        );
        return $this -> aliLive -> aliApi($apiParams,$credential="GET", $domain="cdn.aliyuncs.com");
    }

}


$live  =new Ali_Lite();


//生成推流地址

//$a = $live -> getPullSteam('zhibo.meetv.com.cn','zhibo.meetv.com.cn');

//查看该域名下所有在推流的端
//$live_type = $live -> describeLiveStreamsOnlineList("zhibo.meetv.com.cn","AppName");



?>