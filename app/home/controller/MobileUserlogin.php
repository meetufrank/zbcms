<?php
namespace app\home\controller;
use think\Session;
use think\Request;
class MobileUserlogin extends Common{


    public function index(){

        $id = input("ad_id");  //频道id
        $type = input('user_type');   //用户登录类型

        //频道信息
        $info = db('channel')->where("id = $id")->find();
        $this -> assign('info',$info);

        if($type == 0){
            $this->redirect('Home/Chatmobile/index', ['ad_id' => $id,'user_type'=>$type]);
        }else if($type == 1){  //报名问卷
            return $this -> fetch("index/mobilelogininfo");   //问卷页面
        }else if($type == 2){   //验证码观看
            $this->redirect('Home/Chatmobile/index', ['ad_id' => $id]);
        }else if($type == 3){  //用户导入白名单
            $this->redirect('Home/Chatmobile/index', ['ad_id' => $id]);
        }
    }


    //验证码观看
    public function yzmgk(){

        $pid = $_GET['ad_id'];  //频道id
        $codevar = $_GET['codevar'];
        $yzmwhere = "pid = ".$pid." and codevar ="."'".$codevar."'";
        $yzmdata = db('code_view')->where($yzmwhere)->find();
        if($yzmdata['codevar'] == ''){
            echo 1;
        }else{
            echo 2;
        }
    }


}