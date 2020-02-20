<?php

/**
 * Created by PhpStorm.
 * User: Amazing
 * Date: 2017/12/5
 * Time: 11:27
 */
namespace app\oss\controller;
use Aliyun\Oss;
use think\Db;


class Aly
{

   //录播视频文件信息
   public function index(){


       $oss=new Oss();  //调用oss接口
       //找到StreamName值 == oss文件夹名称  recording/record/AppName/
       $oss_folder_arr = $oss->bucketlist("recording/record/AppName/");

       $oss_folder = [];  //oss文件夹
       foreach($oss_folder_arr as $k => $v){
           //删除掉StreamName中appname前缀
           $oss_folder_del_appname = str_replace("recording/record/AppName/","",$v);
           //删除掉StreamName中后缀/
           $oss_folder_del_xg = str_replace("/","",$oss_folder_del_appname);

           $oss_folder[] = $oss_folder_del_xg;
       }




       //找到所有频道中的视频路径名称
       $video_folder_db_arr = [];
       foreach($oss_folder_arr as $k => $v){

           $video_folder_arr = $oss->bucketlist($v);  //视频路径名称
           foreach($video_folder_arr as $key => $val){
               $data_video_folder = [];
               //streamname
               $streamname = trim(str_replace("recording/record/AppName/","",$v));
               
               $data_video_folder['streamname'] = $streamname; //oss视频存储文件夹和文件完整路径
               $data_video_folder['video_folder_name'] = trim($val);

               //上传视频时间
               $video_name = trim(basename($val));
               $data_video_folder['upload_time'] = substr($video_name,0,10);
               $data_video_folder['video_type'] = 1;   //录播视频


               //视频名称  频道名称 + 上传到oss的时间 年-月-日
               //频道名称
               $channel_name_where = "streamname = '".$streamname."'";
               $channel_name_select = Db::table('clt_channel')->where($channel_name_where)->field("channel_name")->find();
               $channel_name = $channel_name_select['channel_name'];   //频道名称

               //视频名称
               $data_video_folder['video_name'] = $channel_name." ".substr($video_name,0,10);

               $where=[
                   'video_folder_name'=>trim($val)
               ];

               //查询出所有视频文件
               $vdcount = Db::table('clt_video_folder')->where($where)->count();

               if(!$vdcount){
                   db('video_folder')->insert($data_video_folder);
               }


           }
       }

   }



   //文档操作
   public function fileupload($filename,$path){
       $oss = new Oss();
       $oss -> fileupload($filename,$path);
   }

   //视频上传
    public function videoupload($filename,$path){
        $oss = new Oss();

        $oss -> videoupload($filename,$path);
    }

}

