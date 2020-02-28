<?php
namespace app\home\controller;
use think\Request;
use think\Cookie;
use think\Session;
use think\Db;
use Ipaddresscity\Iplocation;

class Index extends Common{
    public function _initialize(){
        parent::_initialize();
    }

    //中金财富02版白名单
    public function zhojiner(){
        return $this->fetch();
    }

    //中金财富02版白名单验证\存储用户
    public function bmd(){
        $pid = $_GET['ad_id'];  //频道id
        if($pid == 79){
            $w_email = $_GET['w_name'];
            $w_pwd = $_GET['w_pwd'];

            //验证用户
            $where_username = "pid = ".$pid." and w_email = '".$w_email."'";
            $isusername = db('whitelist_user_list')->where($where_username)->find();


            //验证密码
            $where_pwd = "pid = ".$pid." and w_email = '".$w_email."' and w_pwd = '".$w_pwd."'";
            $ispwd = db('whitelist_user_list')->where($where_pwd)->find();

            Session::set('userid',$ispwd['id']);
            Session::set('usernames',$ispwd['w_name']);


            if(empty($isusername)){
                return  1; //用户名不存在
            }elseif(empty($ispwd)){
                return 2; //密码错误
            }else{
                return 3; //正确
            }

        }

    }


    //pc端微信扫码支付观看
    public function pcpaylive(){
        $id = input('ad_id');  //频道id
        $pinfo = Db::table('clt_payinfo')->where('cid',$id)->find();  //查找付费直播信息

        if(Request::instance()->isMobile()) { //手机端
            $this->redirect('MobileUserlogin/index', ['ad_id' => $id,'user_type'=>6]);
        }

        $this -> assign('cid',$id);   //直播频道id
        $this -> assign('pinfo',$pinfo);
        return $this -> fetch();
    }

    //pc端微信扫码支付付费观看,手机号验证是否已支付
    public function pcpayphone(){
        $cid = $_POST['cid'];   //频道id
        $phone = $_POST['phone'];  //手机号
        $wo_where = [
            'cid' => $cid,
            'pphone' => $phone
        ];
       $wpo_info = Db::table('clt_wxpayorder')->where($wo_where)->find();  //查找付费直播信息
        if(!empty($wpo_info)){
           $oinfo = Db::table('clt_pay_order')->where('id',$wpo_info['oid'])->find();
           if($oinfo['state'] == 0){
               echo 2;exit;  //该手机号在该频道未支付或者支付失败
           }else if($oinfo['state'] == 1){
               echo 1;exit;
           }
        }else{
            echo 2;exit;  //该手机号在该频道未支付或者支付失败
        }

    }


    //pc端微信扫码支付付费观看,手机号验证是否已支付
    public function pcpayisphone(){
        $cid = $_POST['cid'];   //频道id
        $phone = $_POST['phone'];  //手机号
        $wo_where = [
            'cid' => $cid,
            'pphone' => $phone
        ];
        $wpo_info = Db::table('clt_wxpayorder')->where($wo_where)->find();  //查找付费直播信息
        if(!empty($wpo_info)){
            $oinfo = Db::table('clt_pay_order')->where('id',$wpo_info['oid'])->find();
            if($oinfo['state'] == 0){
                echo 2;exit;  //该手机号在该频道未支付或者支付失败
            }else if($oinfo['state'] == 1){
                echo 1;exit;
            }
        }else{
            echo 2;exit;  //该手机号在该频道未支付或者支付失败
        }

    }

    public function index(){

        //获取code
        $code = $_GET['code'];
        //直播频道信息
        $id = input('ad_id');


        //存在白名单,且用户未登录
        $channel_user_list = db('whitelist_user_list')->where("pid =$id")->find();   //查询该频道是否有用户白名单

        if($id != 79){  //不是中金财富2
            if(!empty($channel_user_list)){    //存在白名单
                if(empty(Cookie::get('usernames'))){  //用户未登录
                    if(Request::instance()->isMobile()) { //手机端
                        $this->redirect('home/channeluserlist/mobiile_index', ['ad_id' => $id]);
                    }else{   //pc端
                        $this->redirect('home/channeluserlist/index', ['ad_id' => $id]);
                    }
                }
            }
        }





        //获取到聊天用户的ip地址
        $request = Request::instance();
        $ip = $request->ip();  //获取客户端ip
        $Index = new IpLocation();
        $ipres = $Index -> getlocation($ip); //根据查询地址地址

        $user_ip = $ipres['ip'];  //客户端 --- 最后登录ip





        //频道信息
        if(!empty($id)){
            $info = db('channel')->where("id = $id")->find();
            $this -> assign('info',$info);


            if(empty(Session::get('userid'))){  //当获取到的用户id为空时
                $userid = date("YmdHis");  //采用时间来生成id
            }else{
                $userid = Session::get('userid');
            }
            $this -> assign('userid',$userid);
            $notice_list = db('notice')->where('cid',$id)->order("id desc")->select();     //频道置顶消息
            $this -> assign('notice_list',$notice_list);     //置顶消息列表


            // 是否为手机访问
            if (Request::instance()->isMobile()) {

                if($id == 79){   //频道id为中金财富02

                    $this->redirect('Chatmobile/index', ['ad_id' => $id]);
                }else{
                    $this->redirect('MobileUserlogin/index', ['ad_id' => $id,'user_type'=>$info['user_type']]);
                }

            }else{
                if($id == 79){   //频道id为中金财富02

                    if(empty(Session::get('userid'))){  //当获取到的用户id为空时
                        $this->redirect('Index/zhojiner', ['ad_id' => $id]);
                    }
                }
            }

            $current_time = date("Y/m/d H:i:s");  //当前系统时间
            if($info['count_down'] <= $current_time){
                $ctype = 1;  //时间已到
            }else{
                $ctype = 2;  //时间未到
            }

            $this -> assign('current_time',$current_time);
            $this -> assign('server_time',time()*1000);
            $this -> assign('ctype',$ctype);


            //验证码观看
            if($info['user_type'] == 2){
                $yzm_info = db('code_view')->where("pid = $id")->find();
                $this -> assign('yzm_info',$yzm_info);
            }

            //添加到访问记录表
            $adata['cid'] = $id;    //关联频道id



            if(empty(Cookie::get('usernames'))){   //usernames为空时，采用ip地址区域加上"用户" = 做为用户名
                if($id == 99){   //思科直播频道没有登陆名称时
                    $this->redirect('http://qiandao.easylaa.com/webinar/cisco/login.aspx?b=30326');
                }
                $Index = new IpLocation();
                $ipres = $Index -> getlocation($ip); //根据查询地址地址

                if($ipres['city'] == ''){
                    $ipres = $Index -> getIpInfo($ip);
                }
                $usernames_access = $ipres['city']."网友";
            }else{
                $usernames_access = Cookie::get('usernames');
            }

            //用户地址
            $user_address = $ipres['city'];
            $adata['username'] = $usernames_access;  //logininfo username
            $adata['type_id'] = 3;    //访问类型
            $adata['ip'] = $user_ip;    //用户ip
            $adata['adate'] = date("Y-m-d h:i:s",time());  //访问直播时间
            db('access')->insert($adata);



            $this -> assign('user_ip',$user_ip); //用户ip
            $this -> assign('usernames',$usernames_access); //聊天框用户昵称

            if(empty(Session::get('userimgurl'))){   //用户头像为空时

                $userimgurl = "https://static.mudu.tv/index/avatar.png";
            }else{
                $userimgurl = Session::get('userimgurl');
            }

            $this -> assign('userimgurl',$userimgurl); //聊天框用户头像

            //查询聊天数
            $chatcount = db('chat')->where("cid = $id")->count();
            $this -> assign('chatcount',$chatcount);


            $chat_list = db('chat')->where("cid = $id")->order('chattopping_time desc,id desc')->limit(100)->select();
            $this -> assign('chat_list',$chat_list);


            //统计访问数
            $acount = db('access')->where("username is not null and cid = $id")->count();
            $this -> assign('acount',$acount);

            //预览、回放
            $playback_info = db('playback')->where("pid = $id")->find();
            if($playback_info['type'] == 1){  //判断是否开启
                //监听直播状态
                $live  =new Index();
                $live_type_info = $live -> describeLiveStreamsOnlineList("zhibo.meetv.com.cn","AppName");

                if($live_type_info['TotalNum'] == 0){
                    $live_type = 0;
                    $this -> assign("playurl",$playback_info['playback_url']);   //未直播
                }else{
                    $live_type = 1;   //直播
                    $this -> assign("playurl",$info['m3u8url']);  //直播
                }
                $this -> assign("livetype",$live_type);
            }else{   //未开启
                /*$this -> assign("playurl",$info['m3u8url']);   //未直播
                $this -> assign("playurl",$info['m3u8url']);  //直播*/
                $this -> assign("playurl",$info['m3u8url']);  //直播
            }


            //监听直播状态
            $live  =new Index();
            $live_type_info = $live -> describeLiveStreamsOnlineList("zhibo.meetv.com.cn","AppName");

            $live_type=0;

            foreach($live_type_info['OnlineInfo']['LiveStreamOnlineInfo'] as $k => $v){
                $p_stname = $v['StreamName']."/";
                if($p_stname == $info['streamname']){
                    $live_type = 1;   //直播
                }
            }

            if($live_type){
                $this -> assign("playurl",$info['m3u8url']);  //直播
            }else{
                $this -> assign("playurl",$playback_info['playback_url']);   //未直播
            }
            $this -> assign("livetype",$live_type);
            $this -> assign("streamname",$info['streamname']);

            //唯一标识
            $uuid = $live -> uuid();
            $playurl = $info['m3u8url']."&aliyun_uuid=".$uuid;

            $this -> assign("playurl",$playurl);  //直播
          $this -> assign("trail_notice_url",$playback_info['playback_url']);   //预告视频路径
          $this -> assign("live_url",$info['m3u8url']);   //直播视频路径

            $gfimg = "https://cdn13.mudu.tv/assets/upload/154466654253127.png";
            $this -> assign('gfimg',$gfimg); //官方图片路径

            //调用用户统计数据
            $index_fun = new index();
            $index_fun -> user_statistics($id,$userid,$user_ip,$usernames_access,$userimgurl,$user_address);




            return $this->fetch();
        }

    }


    public function user_statistics($id,$userid,$user_ip,$usernames_access,$userimgurl,$user_address){   //用户统计 频道id   用户id   用户ip   用户名称  用户头像  用户地址
        date_default_timezone_set('Asia/Shanghai');
        if (!Request::instance()->isMobile()) {   //判断是为电脑端
            $where_r = "cid = $id and userid = $userid";
            $user_record_info = Db::table('clt_user_record')->where($where_r)->find();
            $data = ['userid' => $userid,    //第三方id
                'username' => $usernames_access,  //观众名
                'address' => $user_address,   //地址
                'watchz_times' => '1',  //观看总时长
                'get_time' => date("Y-m-d H:i:s"),  //最后进入时间
                'leave_time' => '1',  //最后在线时间
                'watch_equipment' => '电脑端',  //最后观看设备
                'user_ip' => $user_ip,  //最后登录ip
                'cid' => $id,  //绑定直播频道id
            ];

            if (empty($user_record_info)) {  //不存在此用户,即添加
                //添加用访问数据
                Db::table('clt_user_record')->insert($data);

            } else {
                Db::table('clt_user_record')
                    ->where('id', $user_record_info['id'])
                    ->update($data);
            }
        }
    }


    //发送聊天信息
    public function chatsend(){
        $ctext = $_POST['ctext'];  //聊天内容
        $userid = $_POST['userid'];  //用户id

        $usernames = $_POST['usernames'];  //用户昵称
        $userimgurl = $_POST['userimgurl'];  //用户头像
        $userip = $_POST['userip'];  //用户头像
        $cid = $_POST['cid'];  //频道id


        $issh = db('chattype')->where("cid = $cid")->find();   //查询直播频道是否存在互动聊天审核
        if($userid != 999999){
            if(!empty($issh['cid'])) {  //存在审核
                $to_examine = 1;
            }else{
                $to_examine = '';
            }
        }else{
            $to_examine = '';
        }


        $ndate = date("Y-m-d H:i");  //聊天发送时间
        $add_data = [
            'content' => $ctext, //聊天内容
            'cid' => $cid,  //聊天频道id
            'chattime' => $ndate, //聊天发送时间
            'userid' => $userid,  //用户id,
            'username' => $usernames,  //用户姓名
            'userimgurl' => $userimgurl,  //用户头像
            'ctype' => 1,  //发送消息内容类型为1  客户端发送
            'to_examine' => $to_examine,
            'userip' => $userip
        ];
        Db::table('clt_chat')->insert($add_data);
        $nid = Db::name('clt_chat')->getLastInsID();

        $date = [
            'ctext' => $ctext, //聊天内容
            'cid' => $cid,  //聊天频道id
            'ndate' => $ndate, //聊天发送时间
            'userid' => $userid,  //用户id
            'username' => $usernames,  //用户姓名
            'userimgurl' => $userimgurl,  //用户头像
            'ctype' => 1,  //发送消息内容类型为1  客户端发送
            'nid' => $nid,
            'data' => 1
        ];
        echo json_encode($date);exit;
    }


    //阿里云直播状态
    public function describeLiveStreamsOnlineList($domainName,$appName){
        $apiParams = array(
            'Action'=>'DescribeLiveStreamsOnlineList',
            'DomainName'=>$domainName,
            'AppName'=>$appName,
        );
        $alylive = new Aliyun();

        return $alylive  -> aliApi($apiParams,$credential="GET", $domain="cdn.aliyuncs.com");
    }

    //生成唯一标识uuid
    public function  uuid()
    {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid = substr ( $chars, 0, 8 ) . '-'
            . substr ( $chars, 8, 4 ) . '-'
            . substr ( $chars, 12, 4 ) . '-'
            . substr ( $chars, 16, 4 ) . '-'
            . substr ( $chars, 20, 12 );
        return $uuid ;
    }

    /**
     * 查询在线人数
     */
    public function describeLiveStreamOnlineUserNum($domainName,$appName,$streamName){
        $apiParams = array(
            'Action'=>'DescribeLiveStreamOnlineUserNum',
            'DomainName'=>$domainName,
            'AppName'=>$appName,
            'StreamName'=>$streamName,
        );
        $alylive = new Aliyun();

        return $alylive -> aliApi($apiParams,$credential="GET", $domain="cdn.aliyuncs.com");
    }


    /*public function describeLiveStreamOnlineUserNum($domainName){
        $apiParams = array(
            'Action'=>'DescribeLiveDomainOnlineUserNum',
            'DomainName'=>$domainName,
        );
        $alylive = new Aliyun();
        return $alylive -> aliApi($apiParams,$credential="GET", $domain="cdn.aliyuncs.com");
    }*/

}