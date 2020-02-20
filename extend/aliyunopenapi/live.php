<?php
namespace aliyunopenapi;
use live\Request\V20161101\DescribeLiveStreamOnlineUserNumRequest as alylive;

Class live{

    public function index(){

        //include_once 'aliyun-php-sdk-live/live/Request/V20161101/DescribeLiveStreamOnlineUserNumRequest.php';
        $live = new alylive();
        $info = $live -> aaaa();

        print_r("直播调用成功!!!");exit;
    }
}

