<?php
use think\Model; 
use think\Request;
use think\Session;
class pc_weixin extends Model{
	//回调地址
	public $return_url;
	public $app_id;
	public $app_secret;
	public function __construct($config){
		$this->return_url = "http://".$_SERVER['HTTP_HOST']."/user/loginApi/callback/oauth/pc_weixin";
		$this->app_id = $config['app_id'];
		$this->app_secret = $config['app_secret'];
	}
	//构造要请求的参数数组，无需改动
	public function login(){
        $state = md5(uniqid(rand(), TRUE));
        session('state',$state);
		//拼接URL
		$dialog_url = $wxurl = "https://open.weixin.qq.com/connect/qrconnect?appid=" .
                $this->app_id . "&redirect_uri=".urlencode($this->return_url)."&response_type=code&scope=snsapi_login&state=".
                        $state."#wechat_redirect";
		echo("<script> top.location.href='" . $dialog_url . "'</script>");
	}

	public function respon(){
		if(input('state') == session('state'))
		{
			$code = input("code");
                      
			//拼接URL
			$token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.
                                $this->app_id.'&secret='.$this->app_secret.'&code='.$code.
                                '&grant_type=authorization_code';

			$response = $this->get_contents($token_url);
			$arr=json_decode($response,true);
                        //获取到 access_token
                        $access_token=$arr['access_token'];
			//获取到openid
			$openid = $arr['openid'];
			$userInfo = json_decode($this->get_user_info($access_token,$openid,$this->app_id),true);
			session('state',null);
			return array(
				'openid'=>$openid,//用户号
				'oauth'=>'pc_weixin',
				'sex'=>$userInfo['sex'],
				'username'=>$userInfo['nickname'],
				'avatar'=>$userInfo['headimgurl']
			);
		}else{
			return false;
		}
	}
	public function get_user_info($access_token,$openid,$app_id){
        
        $url= 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        $response =  curl_exec($ch);
        curl_close($ch);
        //-------请求为空
        if(empty($response)){
            exit("50001");
        }
        return $response;
	}


	public function get_contents($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_URL, $url);
		$response =  curl_exec($ch);
		curl_close($ch);
		//-------请求为空
		if(empty($response)){
			exit("50001");
		}
		return $response;
	}

}


?>
