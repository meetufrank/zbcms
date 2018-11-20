<?php
namespace app\home\controller;
use think\Session;
use think\Cookie;

class Chatpc extends Common{
    public function _initialize(){
        parent::_initialize();
    }



    public function index(){

        //频道id
        $cid = $_GET['cid'];
        $usernames = $_GET['username'];


        //聊天人id
        $chat_userid = time();



        //聊天初始化数据
        $return = array(

            //我的信息
            'mine' => array(
                'username' => Session::get('usernames'),     //聊天人名称
                'id' => $chat_userid,                //聊天人id
                'status' => 'online',     //在线状态 online：在线、hide：隐身
                'sign' => '',             //签名
                'jinyan' => '1', //禁言 1正常  2解禁  3被解禁
                'avatar' => Session::get('userimgurl')  //头像
            ),

            //群租信息
            'group' => array(
                'groupname' => '12342345452',    //群组名
                'id' => $cid,                       //群租id
                'avatar' => 'http://oqi1zfida.bkt.clouddn.com/upload/jpeg/0086ea21bacae354a4efe292b4ed7227.jpeg'   //群租头像
            )

        );


        //频道账号信息
        $data['cu_name'] = $return['mine']['username'];   //频道用户名称
        $data['cu_img'] = $return['mine']['avatar'];   //频道用户头像
        $data['cu_date'] = date("Y-m-d H:i:s") ;   //频道用户进入频道时间
        $data['cid'] = $cid;    //频道用户进入时间
        db('channels_users')->insert($data);  //添加数据到频道用户表


        //聊天频道信息
        $return_json = json_encode($return);
        $this -> assign('return_json',$return_json);


        return $this->fetch();
    }



}