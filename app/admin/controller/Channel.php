<?php
namespace app\admin\controller;
use think\Db;
use think\Request;
use think\Controller;
use excel\excels;
use app\oss\controller\Aly;  //阿里云oss 操作控制器
use app\wdcz\controller\Wdcz;  //阿里云智能媒体管理控制器
class Channel extends Common
{
    public function _initialize(){
        parent::_initialize();
    }
    //直播频道
    public function index(){


        if(request()->isPost()) {

            $key = input('post.key');
            $this->assign('testkey', $key);
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');

            $list = db('channel')
                ->field('id,channel_name,channel_logo,user_type')
                ->where('channel_name', 'like', "%" . $key . "%")
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->toArray();

            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['addtime'] = date('Y-m-d H:s',$v['addtime']);
            }
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];

        }
        return $this->fetch();
    }

    //日期函数
    public function udate($format = 'u', $utimestamp = null) {
        if (is_null($utimestamp))
            $utimestamp = microtime(true);

        $timestamp = floor($utimestamp);
        $milliseconds = round(($utimestamp - $timestamp) * 1000000);

        return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
    }




    public function add(){
        if(request()->isPost()) {
            //构建数组
            $data = input('post.');

            $data['addtime'] = time();


            //创建直播推流、拉流链接
            /*传入自定义参数，即传入应用名称和流名称*/
            $AppName = 'AppName';

            $Channels = new Channel();
            $stnametime = $Channels -> udate('ymdHisu');

            $StreamName = 'StreamName'.$stnametime;
            /*
            时间戳，有效时间
            */
            $time = time() + 36000000;
            /*
            加密key，即直播后台鉴权里面自行设置
            */
            $key = 'zhibowj';
            $strpush = "/$AppName/$StreamName-$time-0-0-$key";
            /*
            里面的直播推流中心服务器域名、vhost域名可根据自身实际情况进行设置
            */
            $pushurl = "rtmp://video-center.alivecdn.com/$AppName/$StreamName?vhost=zhibo.meetv.com.cn&auth_key=$time-0-0-".md5($strpush);
            $strviewrtmp = "/$AppName/$StreamName-$time-0-0-$key";
            $strviewflv = "/$AppName/$StreamName.flv-$time-0-0-$key";
            $strviewm3u8 = "/$AppName/$StreamName.m3u8-$time-0-0-$key";
            $rtmpurl = "rtmp://zhibo.meetv.com.cn/$AppName/$StreamName?auth_key=$time-0-0-".md5($strviewrtmp);
            $flvurl = "http://zhibo.meetv.com.cn/$AppName/$StreamName.flv?auth_key=$time-0-0-".md5($strviewflv);
            $m3u8url = "http://zhibo.meetv.com.cn/$AppName/$StreamName.m3u8?auth_key=$time-0-0-".md5($strviewm3u8);

            /*打印推流地址，即通过鉴权签名后的推流地址*/
            $data['pushurl'] = $pushurl;

            /*打印三种直播协议播放地址，即鉴权后的播放地址*/
            $data['rtmpurl'] = $rtmpurl;
            $data['flvurl'] = $flvurl;
            $data['m3u8url'] = $m3u8url;
            $data['streamname'] = $StreamName.'/';
            db('channel')->insert($data);

            $result['code'] = 1;
            $result['msg'] = '直播频道添加成功!';
            cache('adList', NULL);
            $result['url'] = url('index');
            return $result;


        }else{
            $adtypeList=db('ad_type')->order('sort')->select();
            $this->assign('adtypeList',json_encode($adtypeList,true));

            $this->assign('title',lang('add').lang('ad'));
            $this->assign('info','null');
            return $this->fetch('form');
        }
    }

    //查看直播详情
    public function info(){
        $id=input('ad_id');
        $info = db('channel')->where("id = $id")->find();


        //删除该频道的数据
        Db::table('clt_code_view')->where('pid',$id)->delete();

        $codeview_info = db('code_view')->where("pid = $id")->find();

        //验证码详情
        $this -> assign('codeview_info',$codeview_info);
        //直播频道详情
        $this -> assign('info',$info);
        return $this->fetch();
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

    //用户导入白名单
    public function whitelist_user_list(){

        if(request()->isPost()){

            // 获取表单上传文件 例如上传了001.jpg
            $file = request()->file('image');
            // 移动到框架应用根目录/uploads/ 目录下
            $info = $file->move('public' . DS . 'uploads/excel');
            if($info){
               $path = ROOT_PATH.'public/uploads/excel/'.$info->getSaveName();
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }


            $excels=new excels();
            $excels->phpexcel();

            $file_types = explode ( ".", $path );
            $exts = $file_types [count ( $file_types ) - 1];

            if($exts == 'xls') {

                $excels->loaderexcel5(); //加载excel5
               $PHPReader = new \PHPExcel_Reader_Excel5();
            }else if ($exts == 'xlsx') {
                $excels->loaderexcel5(); //加载excel5
                $PHPReader = new \PHPExcel_Reader_Excel2007();
            }

            //载入文件
           $PHPExcel = $PHPReader->load($path);

            //获取表中的第一个工作表，如果要获取第二个，把0改为1，依次类推
            $currentSheet = $PHPExcel->getSheet(0);
            //获取总列数
            $highestColumn = $currentSheet->getHighestColumn();
            //获取总行数
            $highestRow = $currentSheet->getHighestRow();


            $pid = $_POST['pid'];
            //导入用户数据前,清空原先该频道的用户白名单
            Db::name ('whitelist_user_list')->where('pid',$pid)->delete();
            for($i=3;$i<$highestRow+1;$i++){  //i = 3 从第三行开始循环
                //取A列的值
                $data['w_name'] = $PHPExcel->getActiveSheet()->getCell('A'.$i)->getValue();
                //取B列的值
                $data['w_pwd'] = $PHPExcel->getActiveSheet()->getCell('B'.$i)->getValue();
                $data['pid'] = $pid;
                $data['w_time'] = date("Y-m-d h:i:s");

                Db::name('whitelist_user_list')->insert($data);

            }
            Db::name('channel')->where('id', $pid)->update(['user_type'=>'3']);
            return $this -> fetch("index");

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

            $pid = $data['pid'];  //频道id



            // 获取表单上传文件 例如上传了001.jpg
            $file_clogo = request()->file('channel_logo');
            // 移动到框架应用根目录/uploads/ 目录下
            $info_clogo = $file_clogo->validate(['size'=>500000,'ext'=>'jpg,png,gif'])->move('public' . DS . 'uploads');
            if($info_clogo){
                // 成功上传后 获取上传信息
                // 输出 jpg
                echo $info_clogo->getExtension();
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                echo $info_clogo->getSaveName();
                // 输出 42a79759f284b767dfcb2a0197904287.jpg
                echo $info_clogo->getFilename();
            }else{
                // 上传失败获取错误信息
                echo $file_clogo->getError();
            }
            $updata = [
                'channel_name' => $data['channel_name'],   //频道名称
                'channel_logo' => "/uploads/".$info_clogo->getSaveName(),
            ];
            Db::name('channel')->where("id = $pid")->update($updata);
            return $this -> fetch("index");

        }else{
            $c_id = input('ad_id');   //直播频道id
            $channel_Info = db('channel')->where(array('id'=>$c_id))->find();

            $this->assign('info',$channel_Info);
            /*$this->assign('title',lang('edit').lang('ad'));*/
            return $this->fetch('edit');
        }
    }


    //文档操作
    public function wdcz(){
        $pid = $_GET['ad_id'];
        $this -> assign("pid",$pid);

        return $this -> fetch();
    }

    //文档上传至oss 并 进行文件格式转换
    public function document(){

        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('image');

        // 移动到框架应用根目录/uploads/ 目录下
        $info = $file->validate(['size'=>50000,'ext'=>'pptx,ppt,xls,xlt,xlsx,doc,dot,wps,docx,dotx,docm,dotm,pdf'])->move('public' . DS . 'uploads/document');
        if($info){
            // 成功上传后 获取上传信息
            // 输出 jpg
            //echo $info->getExtension();
            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
            //echo $info->getSaveName();
            // 输出 42a79759f284b767dfcb2a0197904287.jpg
            //echo $info->getFilename();
        }else{
            // 上传失败获取错误信息
            $this -> error("上传失败!!!请核实上传文件");exit;
        }

        $aly = new Aly();
        $filename = $info->getFilename();
        $path = "public/uploads/document/".$info->getSaveName();
        //上传文件到oss中   $filename 上传文件名称和文件后缀  $path 文件路径
        $aly -> fileupload($filename,$path);
        //文件格式转换  $filename 上传文件名称和文件后缀
        $wdcz = new Wdcz();
        $outputpath = date("Ymdhis");
        $wdcz -> index($filename,$outputpath);
    }



    //用户登录模式设置
    public function usertype(){

        $pid = $_POST['pid'];  //直播频道id
        $code_title = $_POST['code_title']; //验证码观看
        $bmwj = $_POST['bmwj']; //接受报名问卷
        $codevar = $_POST['codevar'];  //自定义验证



        if($bmwj != ''){  //报名问卷
            Db::name('channel')
                ->where('id', $pid)
                ->update(['user_type' => 1]);
        }else if($code_title != '' && $bmwj == ''){  //验证码观看
            Db::name('channel')
                ->where('id', $pid)
                ->update(['user_type' => 2]);  //更改频道用户登录模式

            //添加到验证码观看
            $code_view_data = [
                'pid' => $pid,
                'code_title' => $code_title,
                'codevar' => $codevar,
            ];
            Db::name('code_view')->data($code_view_data)->insert();
        }else{  //游客模式观看
            Db::name('channel')
                ->where('id', $pid)
                ->update(['user_type' => 0]);
        }

        return $this->fetch('index');

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