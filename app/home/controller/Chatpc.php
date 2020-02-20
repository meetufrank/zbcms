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


        $perPage=21; //由于layim框架的显示问题，这里需要多一条数据，用户看到的是20条数据
        //查询该用户是否可以查询该群组聊天记录
        $count=db('chat')->where("cid = $cid")->count();
        $chatlogs=[];
        if($count){
            $result = db('chat')->where("cid = $cid")
                ->order('id desc')->limit($perPage)->select();
            $result = array_reverse($result); //反转

            foreach ($result as $key => $value) {
                $result[$key]['mine']=false;
                $result[$key]['type']='group';
                $result[$key]['timestamp']=strtotime($result[$key]['chattime'])*1000;
            }

            $this->assign('chatlogs', json_encode($result));
        }else{
            $this->assign('chatlogs', json_encode($chatlogs));
        }



        //聊天头像
        if(Session::get('userimgurl') == ''){
           $userimgurl = "https://static.mudu.tv/index/avatar.png";
        }else{
            $userimgurl = Session::get('userimgurl');
        }

        //聊天人名称
        if(Session::get('usernames') == ''){
            $usernames = "网友";
        }else{
            $usernames = Session::get('usernames');
        }

        //聊天初始化数据
        $return = array(

            //我的信息
            'mine' => array(
                'username' => $usernames,     //聊天人名称
                'id' => $chat_userid,                //聊天人id
                'status' => 'online',     //在线状态 online：在线、hide：隐身
                'sign' => '',             //签名
                'jinyan' => '1', //禁言 1正常  2解禁  3被解禁
                'avatar' => $userimgurl  //头像
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