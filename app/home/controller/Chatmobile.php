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
        $appsecret = "9f742d59501e69ee099a2013aa17eb1d";

        //redirect_uri授权后重定向的回调链接地址,请使用 urlEncode 对链接进行处理

        $redirect_uri = "http://www.rflinker.com/zhibo/home/chatmobile/index.html?ad_id=".$id;
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

        $user_type = input('user_type');  //用户登录类型
        $info = db('channel')->where("id = $id")->find();
        $this -> assign('info',$info);

        //验证码观看
        if($info['user_type'] == 2){

            $yzm_info = db('code_view')->where("pid = $id")->find();
            $this -> assign('yzm_info',$yzm_info);
        }

        if($info['user_type'] == 0){
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

        $Chatmobile = new Chatmobile();
        $ipres = $Chatmobile -> getIpInfo($ip); //根据ip获取城市地址 $ipres['city']
        $usernames = $ipres['city']."网友";

        //聊天初始化数据
        $return = array(

            //我的信息
            'mine' => array(
                'username' => Session::get('username'),     //聊天人名称
                //'username' => $usernames,
                'id' => 1,                //聊天人id
                'status' => 'online',     //在线状态 online：在线、hide：隐身
                'sign' => '',             //签名
                'avatar' => Session::get('headimgurl'),  //头像
                //'avatar' => ''
            ),

            //群租信息
            'group' => array(
                'groupname' => '12342345452',    //群组名
                'id' => $id,                       //群租id
                'avatar' => 'http://oqi1zfida.bkt.clouddn.com/upload/jpeg/0086ea21bacae354a4efe292b4ed7227.jpeg'   //群租头像
            )

        );

        //频道账号信息
        //$data['cu_name'] = $return['mine']['username'];   //频道用户名称
        $data['cu_name'] = $usernames;
        $data['cu_img'] = $return['mine']['avatar'];   //频道用户头像
        $data['cu_date'] = date("Y-m-d H:i:s") ;   //频道用户进入频道时间
        $data['cid'] = $id;    //频道用户进入时间
        db('channels_users')->insert($data);  //添加数据到频道用户表

        $return_json = json_encode($return);
        //print_r($return_json);exit;
        $this -> assign('return_json',$return_json);

        return $this->fetch();
    }
}