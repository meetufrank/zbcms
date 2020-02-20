<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/1
 * Time: 16:39
 */
namespace app\home\controller;
use org\wechat\Jssdk;
use think\Session;
use think\Request;
use Ipaddresscity\Iplocation;
use think\Db;
class Chatmobile extends Common{

    public function _initialize(){
        parent::_initialize();
    }

    // cURL函数简单封装
    public function https_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }





    //直播频道网页授权登录信息
    public function channelwxinfo($id,$info){
        //获取到聊天用户的ip地址
        $request = Request::instance();
        $ip = $request->ip();  //获取客户端ip
        $Index = new IpLocation();
        $ipres = $Index -> getlocation($ip); //根据查询地址地址

        $user_ip = $ipres['ip'];  //客户端 --- 最后登录ip
        $data['cid'] = $id;    //关联频道id
        $data['openid'] = $info['openid'];  //微信id
        $data['nickname'] = $info['nickname'];  //微信昵称
        $data['headimgurl'] = $info['headimgurl'];  //微信头像
        $data['unionid'] = $info['unionid'];  //绑定微信公众平台与微信开放平台id
        $data['datatime'] = date("Y-m-d h:i:s",time());  //微信授权用户时间

        db('channelwxusers')->insert($data);

        //添加到访问记录表
        $adata['cid'] = $id;    //关联频道id
        $adata['username'] = $info['nickname'];  //微信昵称
        $adata['type_id'] = 1;    //访问类型
        $adata['adate'] = date("Y-m-d h:i:s",time());  //访问直播时间
        $adata['ip'] = $user_ip;    //用户ip
        db('access')->insert($adata);

    }

        public function http_tkrequest($url, $data = null)
        {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            if (!empty($data)){
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
            $output = curl_exec($curl);
            curl_close($curl);
            return $output;
        }

    public function wxshoquan($id){

        $wxuserinfos = Session::get('wxuserinfo');
        if(is_array($wxuserinfos)){
            return  $wxuserinfos;
            exit;
        }


        //微信网页授权,获取用户信息
        //第一步：获取code
        //appid公众号的唯一标识(开发者ID)
        $appid = "wxb5aec13c030a530b";

        //appsecret 开发者密码
        $appsecret = "291c699b7ec8ec8d1835b171cda9b3f0";

        //redirect_uri授权后重定向的回调链接地址,请使用 urlEncode 对链接进行处理

        $redirect_uri = "http://easycast.cloud/home/chatmobile/index.html?ad_id=".$id;
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect";

        //没有请求就附加url
        if(!$_GET['code']){
            header('Location:'.$url);
            exit;
        }

        //获取到code
        $code = $_GET['code'];


        //第二步：通过code换取网页授权access_token
        $urler = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$appsecret}&code={$code}&grant_type=authorization_code";

        $arrs = new Chatmobile();
        $arr = $arrs ->https_request($urler);

        $res = json_decode($arr,true);

        //获取到access_token
        $access_token = $res['access_token'];

        $openid = $res['openid'];


        //第三步:获取用户的详细信息
        $urlsan = "https://api.weixin.qq.com/sns/userinfo?access_token={$access_token}&openid={$openid}&lang=zh_CN";
        $arrer = new Chatmobile();
        $ressan = $arrer ->https_request($urlsan);
        $wxuserinfo = json_decode($ressan,true);

        return $wxuserinfo;

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

    //微信端微信扫码支付观看
    public function wxdpaylive(){
        $id = input('ad_id');  //频道id
        $jsApiParameters = input('jsApiParameters');
        if($jsApiParameters !=''){
            $this -> assign('jsApiParameters',$jsApiParameters);

        }

        $pinfo = Db::table('clt_payinfo')->where('cid',$id)->find();  //查找付费直播信息
        $this -> assign('cid',$id);   //直播频道id
        $this -> assign('pinfo',$pinfo);
        return $this -> fetch();
    }


    public function index(){


        //微信分享
        $jssdkObj = new Jssdk("wxb5aec13c030a530b", "9f742d59501e69ee099a2013aa17eb1d");
        $res = $jssdkObj->getSignPackage();
        $appId = $res['appId'];
        $timestamp = $res['timestamp'];
        $nonceStr = $res['nonceStr'];
        $signature = $res['signature'];

        $this -> assign(
            array(
                'appId' => $appId,
                'timestamp' => $timestamp,
                'nonceStr' => $nonceStr,
                'signature' => $signature,
            )
        );


        //直播频道信息
        $id = input('ad_id');
        $user_type = input('user_type');  //频道访问类型

        if($id == 79){   //频道id为中金财富02

            if(empty(Session::get('userid'))){  //当获取到的用户id为空时
                $this->redirect('Chatmobile/zhojiner', ['ad_id' => $id]);
            }
        }

        //置顶消息
        $chat_list = db('chat')->where("cid = $id")->order('chattopping_time desc,id desc')->limit(100)->select();
        $this -> assign('chat_list',$chat_list);



//获取到聊天用户的ip地址
        $request = Request::instance();
        $ip = $request->ip();  //获取客户端ip
        $Index = new IpLocation();
        $ipres = $Index -> getlocation($ip); //根据查询地址地址
        $user_ip = $ipres['ip'];  //客户端 --- 最后登录ip





//添加到访问记录表
        $adata['cid'] = $id;    //关联频道id

        $adata['type_id'] = 1;    //访问类型
        $adata['adate'] = date("Y-m-d h:i:s",time());  //访问直播时间
        $adata['ip'] = $user_ip;    //用户ip
        db('access')->insert($adata);


        $perPage=21; //由于layim框架的显示问题，这里需要多一条数据，用户看到的是20条数据
        //查询该用户是否可以查询该群组聊天记录
        $count=db('chat')->where("cid = $id")->count();
        $chatlogs=[];
        if($count){
            $result = db('chat')->where("cid = $id")
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


        $user_type = input('user_type');  //用户登录类型
        $info = db('channel')->where("id = $id")->find();
        $current_time = date("Y/m/d H:i:s");  //当前系统时间
        if($info['count_down'] <= $current_time){
            $ctype = 1;  //时间已到
        }else{
            $ctype = 2;  //时间未到
        }
        $this -> assign('server_time',time()*1000);
        $this -> assign('current_time',$current_time);
        $this -> assign('ctype',$ctype);
        $this -> assign('info',$info);
        if(empty(Session::get('userid'))){  //当获取到的用户id为空时
            $userid = date("YmdHis");  //采用时间来生成id
        }else{
            $userid = Session::get('userid');
        }
        $this -> assign('userid',$userid);


        //置顶消息第一条

        $notice_list = db('notice')->where('cid',$id)->order("id desc")->select();

        $count_notice = db('notice')->where("cid = $id")->count();

        $this -> assign('count_notice',$count_notice);  //统计置顶消息个数


        $this -> assign('notice_list',$notice_list);     //置顶消息列表



        //验证码观看
        if($info['user_type'] == 2){

            $yzm_info = db('code_view')->where("pid = $id")->find();
            $this -> assign('yzm_info',$yzm_info);
        }

        if($info['user_type'] == 4){ //微信登陆模式
            //获取微信用户信息
            $shoquan = new Chatmobile();
            $wxuserinfo = $shoquan -> wxshoquan($id);
            Session::set('wxuserinfo',$wxuserinfo);
            Session::set('username',$wxuserinfo['nickname']);      //用户名称
            Session::set('headimgurl',$wxuserinfo['headimgurl']);  //用户头像



            //保存频道id和微信用户信息
            $cwinfo = new Chatmobile();
            $cwinfo -> channelwxinfo($id,$wxuserinfo);

        }else if($info['user_type'] == 1){
            $pid = $_POST['pid'];
            $name = $_POST['name'];
            $phone = $_POST['phone'];

            Session::set('username',$name);
            Session::set('headimgurl','https://static.mudu.tv/index/avatar.png');  //用户昵称
        }





        //获取到聊天用户的ip地址
        $request = Request::instance();
        $ip = $request->ip();  //获取ip

        //获取微信用户信息
        $ua = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($ua, 'MicroMessenger') == false && strpos($ua, 'Windows Phone') == false) {
            //不是微信端
        }else {
            //微信浏览器
            $shoquan = new Chatmobile();
            $wxuserinfo = $shoquan -> wxshoquan($id);
        }





        //聊天头像
        if($wxuserinfo['headimgurl'] != ''){
            $userimgurl = $wxuserinfo['headimgurl'];
        }else if(Session::get('headimgurl') == ''){
            $userimgurl = "https://static.mudu.tv/index/avatar.png";
        }else{
            $userimgurl = Session::get('headimgurl');
        }


        if($id == 79){   //中金
            $usernames_access =  Session::get('usernames');
        }else if($id == 99){   //思科
             if(Session::get('usernames') == ''){
                 $this->redirect('http://qiandao.easylaa.com/webinar/cisco/login.aspx?b=30326');
             }else{
                 $usernames_access = Session::get('usernames');
             }
            $ipres = $Index -> getlocation($ip); //根据查询地址地址

            if($ipres['city'] == ''){
                $ipres = $Index -> getIpInfo($ip);
            }
            $user_address = $ipres['city'];  //用户地址
        }else{  //非中金

            $user_name_wx = $wxuserinfo['nickname'];
            Session::set('usernames',$user_name_wx);

            if(empty(Session::get('usernames'))){   //usernames为空时，采用ip地址区域加上"用户" = 做为用户名

                $Index = new IpLocation();
                $ipres = $Index -> getlocation($ip); //根据查询地址地址

                if($ipres['city'] == ''){
                    $ipres = $Index -> getIpInfo($ip);
                }
                $usernames_access = $ipres['city']."网友";
            }else{

                $usernames_access = Session::get('usernames');
            }
            $adata['username'] = $usernames_access;  //logininfo username
            $user_address = $ipres['city'];  //用户地址
        }



        //聊天初始化数据
        $return = array(

            //我的信息
            'mine' => array(
                'username' => $usernames_access,     //聊天人名称
                //'username' => $usernames,
                'id' => 1,                //聊天人id
                'status' => 'online',     //在线状态 online：在线、hide：隐身
                'sign' => '',             //签名
                'avatar' => $userimgurl,  //头像
                //'avatar' => ''
            ),

            //群租信息
            'group' => array(
                'groupname' => '12342345452',    //群组名
                'id' => $id,                       //群租id
                'avatar' => 'http://oqi1zfida.bkt.clouddn.com/upload/jpeg/0086ea21bacae354a4efe292b4ed7227.jpeg'   //群租头像
            )

        );



            $this -> assign('usernames',$usernames_access); //聊天框用户昵称
            $this -> assign('userimgurl',$userimgurl); //聊天框用户昵称




        //频道账号信息
        //$data['cu_name'] = $return['mine']['username'];   //频道用户名称
        $data['cu_name'] = $usernames_access;
        $data['cu_img'] = $return['mine']['avatar'];   //频道用户头像
        $data['cu_date'] = date("Y-m-d H:i:s") ;   //频道用户进入频道时间
        $data['cid'] = $id;    //频道用户进入时间
        db('channels_users')->insert($data);  //添加数据到频道用户表

        $return_json = json_encode($return);
        //print_r($return_json);exit;
        $this -> assign('return_json',$return_json);


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
           /* $this -> assign("playurl",$info['m3u8url']);   //未直播
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
            //唯一标识
            $uuid = $live -> uuid();
            $playurl = $info['m3u8url']."&aliyun_uuid=".$uuid;
            $this -> assign("playurl",$playurl);  //直播
        }else{
            $this -> assign("playurl",$playback_info['playback_url']);   //未直播
        }
        $this -> assign("livetype",$live_type);
        $this -> assign("streamname",$info['streamname']);



        $this -> assign("trail_notice_url",$playback_info['playback_url']);   //预告视频路径
        $this -> assign("live_url",$info['m3u8url']);   //直播视频路径

        $gfimg = "https://cdn13.mudu.tv/assets/upload/154466654253127.png";
        $this -> assign('gfimg',$gfimg); //官方图片路径



        //调用用户统计数据
        $index_fun = new Chatmobile();
        $index_fun -> user_statistics($id,$userid,$user_ip,$usernames_access,$userimgurl,$user_address);
        return $this->fetch();
    }



    public function user_statistics($id,$userid,$user_ip,$usernames_access,$userimgurl,$user_address){   //用户统计 频道id   用户id   用户ip   用户名称  用户头像  用户地址
        date_default_timezone_set('Asia/Shanghai');
        if (Request::instance()->isMobile()) {   //判断是为电脑端
            $where_r = "cid = $id and userid = $userid";
            $user_record_info = Db::table('clt_user_record')->where($where_r)->find();
            $data = ['userid' => $userid,    //第三方id
                'username' => $usernames_access,  //观众名
                'address' => $user_address,   //地址
                'watchz_times' => '1',  //观看总时长
                'get_time' => date("Y-m-d H:i:s"),  //最后进入时间
                'leave_time' => '1',  //最后在线时间
                'watch_equipment' => '手机端',  //最后观看设备
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



    public function describeLiveStreamsOnlineList($domainName,$appName){
        $apiParams = array(
            'Action'=>'DescribeLiveStreamsOnlineList',
            'DomainName'=>$domainName,
            'AppName'=>$appName,
        );
        $alylive = new Aliyun();

        return $alylive  -> aliApi($apiParams,$credential="GET", $domain="cdn.aliyuncs.com");
    }
}