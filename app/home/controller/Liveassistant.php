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
            $cinfo = db('channel')->where("id = $cid")->find();  //查询频道互动直播账号信息

            $this -> assign('username',$cinfo['chat_login_username']);
            $this -> assign('pwd',$cinfo['chat_login_pwd']);
            $this -> assign('cid',$cid);   //直播频道id
            return $this->fetch();
    }



}