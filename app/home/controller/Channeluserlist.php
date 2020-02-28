<?php
namespace app\home\controller;
use think\Session;
use think\Request;
use Ipaddresscity\Iplocation;
use Think\Db;
use think\Cookie;
class channeluserlist extends Common{


    //频道白名单登录
    public function index()
    {
        $cid = $_GET['ad_id'];  //频道id
        if(Request::instance()->isMobile()) { //手机端
            $this->redirect('home/channeluserlist/mobiile_index', ['ad_id' => $cid]);
        }
        $this -> assign("cid",$cid);
        return $this -> fetch();
    }


    //手机端频道白名单登录
    public function mobiile_index()
    {
        $cid = $_GET['ad_id'];  //频道id
        if(!Request::instance()->isMobile()) { //pc端
            $this->redirect('home/channeluserlist/index', ['ad_id' => $cid]);
        }

        $this -> assign("cid",$cid);
        return $this -> fetch();
    }

    //验证用户白名单
    public function bmd(){
        $pid = $_GET['ad_id'];  //频道id
        if($pid != ''){
            $w_name = $_GET['w_name'];
            $w_pwd = $_GET['w_pwd'];

            //验证用户
            $where_username = "pid = ".$pid." and w_name = '".$w_name."'";
            $isusername = db('whitelist_user_list')->where($where_username)->find();


            //验证密码
            $where_pwd = "pid = ".$pid." and w_name = '".$w_name."' and w_pwd = '".$w_pwd."'";
            $ispwd = db('whitelist_user_list')->where($where_pwd)->find();

            Session::set('userid',$ispwd['id']);
            Cookie::set('usernames',$ispwd['w_name']);


            if(empty($isusername)){
                return  1; //用户名不存在
            }elseif(empty($ispwd)){
                return 2; //密码错误
            }else{
                return 3; //正确
            }

        }

    }



}