<?php
namespace app\home\controller;
use Ipaddresscity\Iplocation;
use think\Session;
use think\Request;

class MobileUserlogin extends Common{


    public function index(){

        $id = input("ad_id");  //频道id

        $type = input('user_type');   //用户登录类型
        if($id == 102 and $type == 0){  //频道id为思科
            $this->redirect('http://qiandao.easylaa.com/webinar/cisco/index.aspx?b=30324');
        }


        //微信端微信支付观看直播
          if($type == 6){
                $this->redirect('Home/Chatmobile/wxdpaylive', ['ad_id' => $id]);
            }


        //用户白名单
        $channel_user_list = db('whitelist_user_list')->where("pid =$id")->find();   //查询该频道是否有用户白名单
        if($id != 79){
            if(!empty($channel_user_list)){   //该频道存在用户白名单

                if(Request::instance()->isMobile()) { //手机端
                    $this->redirect('home/channeluserlist/mobiile_index', ['ad_id' => $id]);
                }else{   //pc端
                    $this->redirect('home/channeluserlist/index', ['ad_id' => $id]);
                }
            }
        }


        if($type == 5){  //用户网页授权

            $this->redirect('Home/Chatmobile/index', ['ad_id' => $id,'user_type'=>5]);

        }




        if($type == ''){
            $type = 0;  //为空为普通登陆模式
        }

        //获取到聊天用户的ip地址
        $request = Request::instance();
        $ip = $request->ip();  //获取客户端ip

        $Index = new IpLocation();
        $ipres = $Index -> getlocation($ip);


        if($ipres['city'] == ''){
            $ipres = $Index -> getIpInfo($ip);
        }
        $usernames = $ipres['city']."网友";

        Session::set('username',$usernames);

        //频道信息
        $info = db('channel')->where("id = $id")->find();
        $this -> assign('info',$info);

        if($type == 0){  //普通登陆模式
            $this->redirect('Home/Chatmobile/index', ['ad_id' => $id,'user_type'=>$type]);
        }else if($type == 1){  //报名问卷
            return $this -> fetch("index/mobilelogininfo");   //问卷页面
        }else if($type == 2){   //验证码观看
            $this->redirect('Home/Chatmobile/index', ['ad_id' => $id]);
        }else if($type == 3){  //用户导入白名单
            $this->redirect('Home/Chatmobile/index', ['ad_id' => $id]);
        }else if($type == 4){  //微信登陆模式
            $this->redirect('Home/Chatmobile/index', ['ad_id' => $id,'user_type'=>$type]);
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