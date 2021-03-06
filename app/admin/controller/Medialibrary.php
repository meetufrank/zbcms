<?php
namespace app\admin\controller;
use think\Db;
use think\Request;
use think\Controller;
use app\oss\controller\Aly;
use app\wdcz\controller\Wdcz;  //阿里云智能媒体管理控制器
class Medialibrary extends Common
{
    public function _initialize(){
        parent::_initialize();
    }
    //直播频道
    public function index(){


        //调用阿里云oss获取视频文件路径接口
        $aly = new Aly();
        $aly -> index();  //录播视频文件信息


        if(request()->isPost()) {

            $key = input('post.key');

            $this->assign('testkey', $key);
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');


            $where = [
                'c.channel_name' => ['like',"%".$key."%"],
                'v.update_type' => 1
            ];
            /*$list = db('video_folder')->alias('v')
                ->join(config('database.prefix').'channel c','v.streamname = c.streamname','left')
                ->field('v.id,v.streamname,v.video_folder_name,c.channel_name,v.upload_time,video_name,v.video_type')
                ->where($where)
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->toArray();*/

            $list = db('video_folder')
                ->field('id,streamname,video_folder_name,upload_time,video_name,video_type')
                ->where('video_name', 'like', "%" . $key . "%")
                ->order('id desc')
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->toArray();



            foreach ($list['data'] as $k=>$v){
                //查找频道名称
                $streamname = $list['data'][$k]['streamname'];
                $channName_where = "streamname = '".$streamname."'";
                $channName = Db::table('clt_channel')->where($channName_where)->find();

                if(!empty($channName['channel_name'])){
                    $list['data'][$k]['channel_name'] = $channName['channel_name'];
                }else{
                    $list['data'][$k]['channel_name'] = "上传视频";
                }



                //视频链接
                $list['data'][$k]['videourl'] = "https://meetuuu.oss-cn-shanghai.aliyuncs.com/".$v['video_folder_name'];
            }
            /*print_r($list['data']);exit;*/
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];

        }
        return $this->fetch();
    }


    //上传视频
    public function addvideo(){
        return $this -> fetch();
    }

    //本地视频上传至obs中
    public function upload_video(){

        $file = request()->file('video');

        // 移动到框架应用根目录/uploads/ 目录下
        $info = $file->validate(['size'=>5000000,'ext'=>'wmv,asf,asx,rm,rmvb,mp4,3gp,mov,m4v,avi,dat,mkv,flv,vob'])->move('public' . DS . 'uploads/video');
        if($info){

        }else{
            // 上传失败获取错误信息
            $this -> error("上传失败!!!请核实上传文件");exit;
        }


        $aly = new Aly();
        $filename = $info->getInfo()['name'];  //视频名称加后缀
        $path = "public/uploads/video/".$info->getSaveName();   //本地视频文件路径


        //上传文件到oss中   $filename 上传文件名称和文件后缀  $path 文件路径
        $aly -> videoupload($filename,$path);

        return $this -> fetch('index');
    }


    //视频设置
    public function info(){
        $id=input('ad_id');

        $info = db('video_folder')->where("id = $id")->field("video_folder_name,video_name,id")->find();

        //组装视频文件链接
        $video_url = "https://meetuuu.oss-cn-shanghai.aliyuncs.com/".$info['video_folder_name'];
        $info['video_folder_name'] = $video_url;

        $this -> assign("info",$info); //视频库详情

        return $this->fetch();
    }


    //视频文件修改
    public function video_edit(){

        $vid = input('vid'); //视频文件主键id
        $video_name = input("video_name");   //视频文件名称

        Db::name('video_folder')
            ->where('id', $vid)
            ->update(['video_name' => $video_name]);

        return $this -> fetch('index');
    }


    /*public function edit(){

        $vid = input('vid'); //视频文件主键id
        $video_name = input("video_name");   //视频文件名称
       /* print_r($vid);
        print_r($video_name);
        exit;*/
       /* Db::name('video_folder')
            ->where('id', $vid)
            ->update(['update_type' => '2']);  //假性删除状态*/


    //}

    //视频库假性删除
    public function typedelete(){
        $id=input('ad_id');
        Db::name('video_folder')
            ->where('id', $id)
            ->update(['update_type' => '2']);  //假性删除状态
        return $this -> fetch('index');
    }


    //日期函数
    public function udate($format = 'u', $utimestamp = null) {
        if (is_null($utimestamp))
            $utimestamp = microtime(true);

        $timestamp = floor($utimestamp);
        $milliseconds = round(($utimestamp - $timestamp) * 1000000);

        return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
    }



    //用户统计
    public function userinfo(){

        if(request()->isPost()) {
            $id=input('ad_id');
            $info = db('channels_users')->where("cid = $id")->select();
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$info,'rel'=>1];
        }else{
            $id=input('ad_id');
            $this -> assign('id',$id);
            return $this->fetch();
        }

    }


    //用户数据导出
    public function out()
    {

        //导出
        $path = dirname(__FILE__); //找到当前脚本所在路径
        vendor("PHPExcel.PHPExcel.PHPExcel");
        vendor("PHPExcel.PHPExcel.Writer.IWriter");
        vendor("PHPExcel.PHPExcel.Writer.Abstract");
        vendor("PHPExcel.PHPExcel.Writer.Excel5");
        vendor("PHPExcel.PHPExcel.Writer.Excel2007");
        vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);


        // 实例化完了之后就先把数据库里面的数据查出来
        $id = input("ad_id");
        $sql = db('channels_users')->where("cid = $id")->select();

        // 设置表头信息
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '用户名称')
            ->setCellValue('B1', '首次登陆时间');

        /*--------------开始从数据库提取信息插入Excel表中------------------*/

        $i=2;  //定义一个i变量，目的是在循环输出数据是控制行数
        $count = count($sql);  //计算有多少条数据
        for ($i = 2; $i <= $count+1; $i++) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $sql[$i-2]['cu_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $sql[$i-2]['cu_date']);
        }

        //设置单元格宽度
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);

        /*--------------下面是设置其他信息------------------*/

        $objPHPExcel->getActiveSheet()->setTitle('productaccess');      //设置sheet的名称
        $objPHPExcel->setActiveSheetIndex(0);                   //设置sheet的起始位置
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');   //通过PHPExcel_IOFactory的写函数将上面数据写出来

        $PHPWriter = \PHPExcel_IOFactory::createWriter( $objPHPExcel,"Excel2007");

        $datetime = date("YmdHis");
        $filename = "用户列表".$datetime.".xlsx";

        header('Content-Disposition: attachment;filename='.$filename);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $PHPWriter->save("php://output"); //表示在$path路径下面生成demo.xlsx文件

    }



    //聊天数据
    public function chatdata(){

        if(request()->isPost()) {
            $id=input('ad_id');
            $info = db('chat')->where("cid = $id")->select();
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$info,'rel'=>1];
        }else{
            $id=input('ad_id');
            $this -> assign('id',$id);
            return $this->fetch();
        }

    }


    //聊天数据导出
    public function chatdataout()
    {

        //导出
        $path = dirname(__FILE__); //找到当前脚本所在路径
        vendor("PHPExcel.PHPExcel.PHPExcel");
        vendor("PHPExcel.PHPExcel.Writer.IWriter");
        vendor("PHPExcel.PHPExcel.Writer.Abstract");
        vendor("PHPExcel.PHPExcel.Writer.Excel5");
        vendor("PHPExcel.PHPExcel.Writer.Excel2007");
        vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);


        // 实例化完了之后就先把数据库里面的数据查出来
        $id = input("ad_id");
        $sql = db('chat')->where("cid = $id")->select();

        // 设置表头信息
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '用户昵称')
            ->setCellValue('B1', '回复内容')
            ->setCellValue('C1', '回复时间');
        /*--------------开始从数据库提取信息插入Excel表中------------------*/

        $i=2;  //定义一个i变量，目的是在循环输出数据是控制行数
        $count = count($sql);  //计算有多少条数据
        for ($i = 2; $i <= $count+1; $i++) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $sql[$i-2]['username']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $sql[$i-2]['content']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $sql[$i-2]['chattime']);
        }

        //设置单元格宽度
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(100);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);

        /*--------------下面是设置其他信息------------------*/

        $objPHPExcel->getActiveSheet()->setTitle('productaccess');      //设置sheet的名称
        $objPHPExcel->setActiveSheetIndex(0);                   //设置sheet的起始位置
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');   //通过PHPExcel_IOFactory的写函数将上面数据写出来

        $PHPWriter = \PHPExcel_IOFactory::createWriter( $objPHPExcel,"Excel2007");

        $datetime = date("YmdHis");
        $filename = "聊天互动导出_".$datetime.".xlsx";

        header('Content-Disposition: attachment;filename='.$filename);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $PHPWriter->save("php://output"); //表示在$path路径下面生成demo.xlsx文件

    }







    //修改频道基础信息
 /*   public function edit(){

            $cid = input('ad_id'); //频道id
            $cInfo = db('channel')->where(array('id'=>$cid))->find();  //查询出该频道信息
            $this -> assign('cInfo',$cInfo);
            return $this->fetch('edit');
    }

    public function edit_info(){
        $channel_name = input('channel_name');  //频道名称
        $channel_logo = input('channel_logo');  //频道logo
        print_r($channel_name);
        print_r($channel_logo);
        exit;

    }*/

    //修改频道基础信息
    public function edit(){
        if(request()->isPost()) {
            $data = input('post.');
            $typeId = explode(':',$data['type_id']);
            $data['type_id'] =$typeId[1];
            db('ad')->update($data);
            $result['code'] = 1;
            $result['msg'] = '广告修改成功!';
            cache('adList', NULL);
            $result['url'] = url('index');
            return $result;
        }else{
            $c_id = input('ad_id');   //直播频道id
            $channel_Info = db('channel')->where(array('id'=>$c_id))->find();
            /*print_r($channel_Info);exit;*/
            $this->assign('info',json_encode($channel_Info,true));
            $this->assign('title',lang('edit').lang('ad'));
            return $this->fetch('form');
        }
    }
    //设置广告状态
    public function editState(){
        $id=input('post.id');
        $open=input('post.open');
        if(db('ad')->where('ad_id='.$id)->update(['open'=>$open])!==false){
            return ['status'=>1,'msg'=>'设置成功!'];
        }else{
            return ['status'=>0,'msg'=>'设置失败!'];
        }
    }
    public function adOrder(){
        $ad=db('ad');
        $data = input('post.');
        if($ad->update($data)!==false){
            cache('adList', NULL);
            return $result = ['msg' => '操作成功！','url'=>url('index'), 'code' =>1];
        }else{
            return $result = ['code'=>0,'msg'=>'操作失败！'];
        }
    }

    //删除单条频道频道信息
    public function del(){
        db('channel')->where(array('id'=>input('id')))->delete();
        cache('adList', NULL);
        return ['code'=>1,'msg'=>'删除成功！'];
    }

    //删除选中频道信息列表
    public function delall(){
        $map['id'] =array('in',input('param.ids/a'));
        db('channel')->where($map)->delete();
        cache('adList', NULL);
        $result['msg'] = '删除成功！';
        $result['code'] = 1;
        $result['url'] = url('index');
        return $result;
    }

    /***************************位置*****************************/
    //位置



    public function type(){
        if(request()->isPost()) {
            $key = input('key');
            $this->assign('testkey', $key);
            $list = db('ad_type')->where('name', 'like', "%" . $key . "%")->order('sort')->select();
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list,'rel'=>1];
        }
        return $this->fetch();
    }
    public function typeOrder(){
        $ad_type=db('ad_type');
        $data = input('post.');
        if($ad_type->update($data)!==false){
            return $result = ['msg' => '操作成功！','url'=>url('type'), 'code' =>1];
        }else{
            return $result = ['code'=>0,'msg'=>'操作失败！'];
        }
    }
    public function addType(){
        if(request()->isPost()) {
            db('ad_type')->insert(input('post.'));
            $result['code'] = 1;
            $result['msg'] = '广告位保存成功!';
            $result['url'] = url('type');
            return $result;
        }else{
            $this->assign('title',lang('add').lang('ad').'位');
            $this->assign('info','null');
            return $this->fetch('typeForm');
        }
    }
    public function editType(){
        if(request()->isPost()) {
            db('ad_type')->update(input('post.'));
            $result['code'] = 1;
            $result['msg'] = '广告位修改成功!';
            $result['url'] = url('type');
            return $result;
        }else{
            $type_id=input('param.type_id');
            $info=db('ad_type')->where('type_id',$type_id)->find();
            $this->assign('title',lang('edit').lang('ad').'位');
            $this->assign('info',json_encode($info,true));
            return $this->fetch('typeForm');
        }
    }
    public function delType(){
        $map['type_id'] = input('param.type_id');
        db('ad_type')->where($map)->delete();//删除广告位
        db('ad')->where($map)->delete();//删除该广告位所有广告
        return ['code'=>1,'msg'=>'删除成功！'];
    }
}