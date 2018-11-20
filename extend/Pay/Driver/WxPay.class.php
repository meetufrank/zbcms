<?php
/**
 * 
 * 接口访问类，包含所有微信支付API列表的封装，类中方法为static方法，
 * 每个接口有默认超时时间（除提交被扫支付为10s，上报超时时间为1s外，其他均为6s）
 * @author widyhu
 *
 */
namespace Think\Pay\Driver;
class WxPay
{
	const APPID = "wx6ec6304d5eea4b7e";
	const MCHID = "1339299601";
	const PRIVATEKEY = "abcdefghijklmnopqrstuvwxyz123456";
	public $money   = "";
	public $out_trade_no   = "";
	public $parameters = array();
	public function __construct($money,$out_trade_no){
		$this -> money = $money;
		$this -> out_trade_no = $out_trade_no;
	}

	//统一支付接口
	public function unifiedOrder(){
		$url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
		$xml = $this -> createXml();
		$data = $this -> postXmlCurl($xml,$url);
		$res = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
		$res = json_decode(json_encode($res),TRUE);
		return $res;
	}


	/**
	 * 生成接口参数xml
	 */
	function createXml(){
		$this -> setParameters();
		$this -> setSign();
		//dump($this->parameters);
		return  $this->arrayToXml($this->parameters);
	}


	//赋值
	public function setParameters(){
		//dump($_SERVER);
		//公众账号ID
		$this -> setParameter("appid",self::APPID);
		//商户号
		$this -> setParameter("mch_id",self::MCHID);
		//随机字符串
		$this -> setParameter("nonce_str",$this->createNoncestr());
		//商品描述
		$this -> setParameter("body","充值");
		//商品订单
		$this -> setParameter("out_trade_no",$this -> out_trade_no);
		//支付类型
		$this -> setParameter("trade_type","NATIVE");
		//商品id
		$this -> setParameter("product_id","123");
		//付款金额
		$this -> setParameter("total_fee",$this -> money);
		//终端ip
		$this -> setParameter("spbill_create_ip",$_SERVER['REMOTE_ADDR']);
		//支付回调url
		//$this -> setParameter("notify_url","http://www.1t1sky.com/wxpay/callback.php");
                $this -> setParameter("notify_url","http://www.1t1sky.com/Public/wx_notify");
	}

	/**
	 * [setSign 设置签名]
	 */
	public function setSign(){
		$sign = $this -> getSign($this -> parameters);
		$this -> setParameter("sign",$sign);
	}

	/**
	 * 	作用：设置请求参数
	 */
	public function setParameter($parameter, $parameterValue){
		$this->parameters[$parameter] = $parameterValue;
	}

	/**
	 * 	作用：格式化参数，签名过程需要使用
	 */
	function formatBizQueryParaMap($paraMap, $urlencode){
		$buff = "";
		ksort($paraMap);
		foreach ($paraMap as $k => $v){
		    if($urlencode){
			   $v = urlencode($v);
			}
			//$buff .= strtolower($k) . "=" . $v . "&";
			$buff .= $k . "=" . $v . "&";
		}
		$reqPar;
		if (strlen($buff) > 0){
			$reqPar = substr($buff, 0, strlen($buff)-1);
		}
		return $reqPar;
	}


	/**
	 * 	作用：生成签名
	 */
	public function getSign($Obj){
		foreach ($Obj as $k => $v){
			$Parameters[$k] = $v;
		}
		//签名步骤一：按字典序排序参数
		ksort($Parameters);
		//return $Parameters;
		$String = $this->formatBizQueryParaMap($Parameters, false);
		//echo '【string1】'.$String.'</br>';
		//签名步骤二：在string后加入KEY
		$String = $String."&key=".self::PRIVATEKEY;
		//return $String;
		//echo "【string2】".$String."</br>";
		//签名步骤三：MD5加密
		$String = md5($String);
		//echo "【string3】 ".$String."</br>";
		//签名步骤四：所有字符转为大写
		$result_ = strtoupper($String);
		//echo "【result】 ".$result_."</br>";
		return $result_;
	}
	/**
	 * 	作用：array转xml
	 */
	function arrayToXml($arr){
		$xml = "<xml>";
		foreach ($arr as $key=>$val){
			if (is_numeric($val)){
				$xml.="<".$key.">".$val."</".$key.">"; 
			}else 
				$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";  
		}
		$xml.="</xml>";
		return $xml; 
	}
	/**
	 * 获取毫秒级别的时间戳
	 */
	private static function getMillisecond()
	{
		//获取毫秒的时间戳
		$time = explode ( " ", microtime () );
		$time = $time[1] . ($time[0] * 1000);
		$time2 = explode( ".", $time );
		$time = $time2[0];
		return $time;
	}

	/**
	 * 以post方式提交xml到对应的接口url
	 * 
	 * @param string $xml  需要post的xml数据
	 * @param string $url  url
	 * @param bool $useCert 是否需要证书，默认不需要
	 * @param int $second   url执行超时时间，默认30s
	 * @throws WxPayException
	 */
	private static function postXmlCurl($xml, $url, $useCert = false, $second = 30)
	{		
		$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOPT_TIMEOUT, $second);
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
		//设置header
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		//要求结果为字符串且输出到屏幕上
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		//post提交方式
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		//运行curl
		$data = curl_exec($ch);
		return $data;
	}

	/**
	 * 	作用：产生随机字符串，不长于32位
	 */
	public function createNoncestr( $length = 32 ){
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {  
			$str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
		}  
		return $str;
	}

	public function createNums($length = 10 ){
		$chars = "0123456789";  
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {  
			$str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
		}  
		return $str;
	}
	
}

