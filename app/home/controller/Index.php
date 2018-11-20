<?php
namespace app\home\controller;
use think\Request;
use think\Cookie;
use think\Session;
class Index extends Common{
    public function _initialize(){
        parent::_initialize();
    }

    public function index(){

        //获取code
        $code = $_GET['code'];

        //直播频道信息
        $id = input('ad_id');

        //频道信息
        $info = db('channel')->where("id = $id")->find();
        $this -> assign('info',$info);

        // 是否为手机访问
        if (Request::instance()->isMobile()) {
            $this->redirect('MobileUserlogin/index', ['ad_id' => $id,'user_type'=>$info['user_type']]);
        }

        //验证码观看
        if($info['user_type'] == 2){
            $yzm_info = db('code_view')->where("pid = $id")->find();
            $this -> assign('yzm_info',$yzm_info);
        }





        //添加到访问记录表
        $adata['cid'] = $id;    //关联频道id
        $adata['username'] = Session::get('usernames');  //logininfo username
        $adata['type_id'] = 3;    //访问类型
        $adata['adate'] = date("Y-m-d h:i:s",time());  //访问直播时间
        db('access')->insert($adata);


        $this -> assign('usernames',Session::get('usernames')); //聊天框用户昵称


        //查询聊天数
        $chatcount = db('chat')->where("cid = $id")->count();
        $this -> assign('chatcount',$chatcount);

        //统计访问数
        $acount = db('access')->where("username is not null and cid = $id")->count();
        $this -> assign('acount',$acount);

        return $this->fetch();
    }
}