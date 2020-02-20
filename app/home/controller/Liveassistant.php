<?php
namespace app\home\controller;
use think\Request;
use think\Cookie;
use think\Session;
use think\Db;
use Ipaddresscity\Iplocation;
class Liveassistant extends Common{
    public function _initialize(){
        parent::_initialize();
    }

    public function index(){   //直播助手页面
            $cid =  $_GET['ad_id'];  //频道id
            $this -> assign('cid',$cid);   //直播频道id
            return $this->fetch();
    }



}