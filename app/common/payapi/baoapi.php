<?php
namespace app\common\payapi;
use think\Db;
use alipay\Close;
use alipay\Datadownload;
use alipay\Notify;
use alipay\Pagepay;
use alipay\Query;
use alipay\Refund;
use alipay\RefundQuery;
use alipay\Wappay;

class baoapi {
    
   //应用ID,您的APPID。     
   private $app_id;
         //商户私钥
   private $merchant_private_key;
   //异步通知地址
   private $notify_url="";
   //同步跳转
   private $return_url="";
   //编码格式
   private $charset="UTF-8";
   //签名方式
   private $sign_type="RSA2";
    //支付宝网关
   private $gatewayUrl="https://openapi.alipay.com/gateway.do";
   //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
   private $alipay_public_key;
   private $config=[];
   public function __construct() {
       $apidata=db('paylist')->where(['id'=>1])->find();
                        //将配置 数据提取出来
        $config=json_decode($apidata['data'],true);
       $this->app_id=$config['app_id'];
       $this->merchant_private_key=$config['merchant_private_key'];
       $this->alipay_public_key=$config['alipay_public_key'];
       $this->config['app_id']= $this->app_id;
       $this->config['merchant_private_key']= $this->merchant_private_key;
//       $this->config['notify_url']= $this->notify_url;
//       $this->config['return_url']= $this->return_url;
       $this->config['charset']= $this->charset;
       $this->config['sign_type']= $this->sign_type;
       $this->config['gatewayUrl']= $this->gatewayUrl;
       $this->config['alipay_public_key']= $this->alipay_public_key;
   }
   /*
    * 设置异步通知地址
    */
   public function setNotify($url) {
        $this->notify_url=$url;
        $this->config['notify_url']= $this->notify_url;
   }
      /*
    * 设置回调地址
    */
   public function setReturn($url) {
       $this->return_url=$url;
       $this->config['return_url']= $this->return_url;
   }
   //支付宝电脑网站支付
   public function webpay($params) {
       $class=new Pagepay();
       $class->pay($params, $this->config);
   }
      //支付宝手机网站支付
   public function wappay($params) {
       $class=new Wappay();
       $class->pay($params, $this->config);
   }
   //异步通知验证
   public function notify($params) {
       $class=new Notify();
       $result=$class->checkSign($params, $this->config);
       return $result;
   }
   //如需其他业务自行添加

}
