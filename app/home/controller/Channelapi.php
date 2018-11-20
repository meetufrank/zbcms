<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/13
 * Time: 13:31
 */
namespace app\home\controller;
use think\Request;
use think\Db;
//接口
class Channelapi{


    //频道信息
    public function channelinfo(){

        //判断是否为post提交
        if(Request::instance()->isPost()){
            //频道id
           $id = $_POST['cid'];
           if($id != ''){
               $channelidinfo = db('channel')->where("id = $id")->field("id,channel_name,channel_logo,m3u8url")->find();

               //判断直播id是否存在
               if(is_array($channelidinfo)){

                   $errorarray = ['code'=>0,'list'=>$channelidinfo];
                   echo json_encode($errorarray);
               }else{

                   $errorarray = ['code'=>10001,'errmsg'=>'直播频道id不存在'];
                   echo json_encode($errorarray);
                   exit;
               }
           }else{
               $errorarray = ['code'=>10001,'errmsg'=>'直播频道id不存在'];
               echo json_encode($errorarray);
               exit;
           }
        }else{
            $errorarray = ['code'=>1,'errmsg'=>'提交方式错误，只能用POST方式提交'];
            echo json_encode($errorarray);
            exit;
        }
    }





}