<?php
namespace app\home\controller;
use think\Session;
use think\Request;
class Userlogin extends Common{



    //用户登录
    public function index(){

        $pid = $_GET['ad_id'];  //频道id
        $type = $_GET['user_type'];  //用户登录类型

        //频道信息
        $info = db('channel')->where("id = $pid")->find();
        $this -> assign('info',$info);

        //0.游客登录模式
        if($type == 0){

            //获取到聊天用户的ip地址
            $request = Request::instance();
            $ip = $request->ip();  //获取客户端ip

            $Index = new Index();
            //$ipres = $Index -> getIpInfo("211.161.194.117" ); //根据ip获取城市地址 $ipres['city']
            $ipres = $Index -> getIpInfo($ip);

            if($ipres['city'] == ''){
                $ipres = $Index -> getIpInfo($ip);
            }
            $usernames = $ipres['city']."网友";

            Session::set('usernames',$usernames);
            Session::set('userimgurl','https://static.mudu.tv/index/avatar.png');  //用户昵称

            $this->redirect('Home/Index/index', ['ad_id' => $pid]);
        }else if($type == 1){     //1.报名问卷
            return $this -> fetch("index/logininfo");   //问卷页面
        }else if($type == 2){   //验证码观看
            $this->redirect('Home/Index/index', ['ad_id' => $pid]);
        }else if($type == 3){  //用户导入白名单
            $this->redirect('Home/Index/index', ['ad_id' => $pid]);
        }else if($type == 4){       //4.微信登录
            //获取code
            $code = $_GET['code'];
            //微信模式登录  nickname  headimgurl
            $wx_user_info = $this -> weLogin($code);
            Session::set('usernames',$wx_user_info->nickname);  //用户昵称
            Session::set('userimgurl',$wx_user_info->headimgurl);  //用户昵称
            $this->redirect('Home/Index/index', ['ad_id' => $pid]);
        }



        //3.用户白名单



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

    //用户白名单观看
    public  function bmd(){
        $pid = $_GET['ad_id'];  //频道id
        $w_name = $_GET['w_name'];
        $w_pwd = $_GET['w_pwd'];

        //验证用户
        $where_username = "pid = ".$pid." and w_name = '".$w_name."'";
        $isusername = db('whitelist_user_list')->where($where_username)->find();

        //验证密码
        $where_pwd = "pid = ".$pid." and w_name = '".$w_name."' and w_pwd = '".$w_pwd."'";
        $ispwd = db('whitelist_user_list')->where($where_pwd)->find();

        if(empty($isusername)){
            return  1; //用户名不存在
        }elseif(empty($ispwd)){
            return 2; //密码错误
        }else{
            return 3; //正确
        }

    }


    public function userlogin(){
        $pid = $_POST['pid'];
        $name = $_POST['name'];
        $phone = $_POST['phone'];


        Session::set('usernames',$name);
        Session::set('userimgurl','https://static.mudu.tv/index/avatar.png');  //用户昵称
        $this->redirect('Home/Index/index', ['ad_id' => $pid]);

    }

    //微信登录
    public function weLogin($code){

        //根据coke获取access_token
        $get_ac_url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=wx7eee3208b7b59ea1&secret=03a65444476d29a5d60c100b6d53a116&code=$code&grant_type=authorization_code";
        $res_data = $this->curlRequest($get_ac_url);
        $res_arr = json_decode($res_data,true);

        //根据access_token获取用户信息
        $get_user_url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$res_arr['access_token'].'&openid=wx7eee3208b7b59ea1&lang=zh_CN';
        $user_data = $this->curlRequest($get_user_url);
        $user_info = json_decode($user_data);

        return $user_info;
    }







}