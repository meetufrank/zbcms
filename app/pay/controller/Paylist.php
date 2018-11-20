<?php
namespace app\pay\controller;

use think\Controller;
use think\Loader;
use app\common\logic\PayLogic;
use think\db;
use think\Request;
use app\common\payapi;
class Paylist extends Controller {

    
    public function index($id=1) {
        
            switch ($id) {
                case 1:
                $this->redirect(url('wxwap'));  //跳转微信浏览器端 

                    break;
                case 2:
                $this->redirect(url('baowap'));  //跳转支付宝手机端支付 (除微信浏览器) 

                    break;
                case 3:
                $this->redirect(url('zfbweb'));  //使用电脑端支付宝支付

                    break;
                case 4:
                $this->redirect(url('wxweb'));  //跳转微信电脑端

                    break;

                default:
                $this->redirect(url('wxwap'));  //跳转微信浏览器端 
               break;
            }

            
           
    }
    
    //支付宝电脑端
    public function zfbweb(Request $request){
         $orderid= session('order_no');  //input('orderid')
         $orderid || $this->error("未接收到订单号");
         $order=db('pay_order')->where(['order_no'=>$orderid])->find();
         $order_no=$order['order_no'];
         if(empty($order)){
             $this->error("该订单不存在");
         }
        $api=new payapi\baoapi();
             //设置异步通知地址
             $api->setNotify($request->domain().url('pay/Pay/zfb'));
             //设置同步跳转地址
             $api->setReturn($request->domain().url('pay/Paylist/success'));
             $params=[
                 'subject'=>'测试',
                 'out_trade_no'=>$order_no,
                 'total_amount'=>0.01
             ];
            
             $api->webpay($params);   //后台测试使用电脑端支付
    }
    //微信电脑端
    public function wxweb(Request $request) {
          
        
              Loader::import('wxpay.WxPayPubHelper');
         $orderid= session('order_no');  //input('orderid')
         $orderid || $this->error("未接收到订单号");
         $order=db('pay_order')->where(['order_no'=>$orderid])->find();
         $order_no=$order['order_no'];
         if(empty($order)){
             $this->error("该订单不存在");
         }
            //将配置 数据提取出来
            $object=new \Common_util_pub();
  
             
             $wxje = 0.01; //金额
        if ($wxje) {
            $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("body", "测试"); //商品描述
            
            $unifiedOrder->setParameter("out_trade_no", "$order_no"); //商户订单号
          
            $unifiedOrder->setParameter("total_fee", $wxje*100); //总金额
            

            /*
             * 换成你的域名写死
             */
            $unifiedOrder->setParameter("notify_url", $request->domain().'/pay/Pay/wx.html'); //异步通知地址
           
            $unifiedOrder->setParameter("trade_type", "NATIVE"); //交易类型
            $unifiedOrderResult = $unifiedOrder->getResult();
            if ($unifiedOrderResult["return_code"] == "FAIL") {
                //商户自行增加处理流程
                echo "通信出错：" . $unifiedOrderResult['return_msg'] . "<br>";
            } elseif ($unifiedOrderResult["result_code"] == "FAIL") {
                //商户自行增加处理流程
                echo "错误代码：" . $unifiedOrderResult['err_code'] . "<br>";
                echo "错误代码描述：" . $unifiedOrderResult['err_code_des'] . "<br>";
            } elseif ($unifiedOrderResult["code_url"] != NULL) {
                //从统一支付接口获取到code_url
                $code_url = $unifiedOrderResult["code_url"];
                //商户自行增加处理流程
                /*
                  $data['u_id'] = session('uid');
                  $data['dh'] = $out_trade_no;
                  $data['total_fee'] = $wxje;
                  $data['dstatus'] = '0';
                  $data['dtime'] = time();
                  $data['jklx'] = '2';
                  session('wxdh', $out_trade_no);
                  $ord = M('Orderlist');
                  $ord->add($data);
                 */
            }
            $this->assign('out_trade_no', $order_no);
            $this->assign('code_url', $code_url);
            $this->assign('wxje', $wxje);
            $this->assign('unifiedOrderResult', $unifiedOrderResult);
            return $this->fetch('paywx');
        } else {
            $this->error("非法操作");
        }
    }
    
    //微信浏览器端
     public function wxwap(Request $request) {
        Loader::import('wxpay.WxPayPubHelper');
         $orderid= session('order_no');  //input('orderid')
         $orderid || $this->error("未接收到订单号");
         $order=db('pay_order')->where(['order_no'=>$orderid,'state'=>0])->find();
         if(empty($order)){
             $this->error("该订单不存在或已经支付");
         }
        //获取微信配置
        //调用微信接口
        $wxje = $order['order_money']; //金额
         $order_no=$order['order_no'];
         $order_title=$order['title'];
         //获取用户openid
         $tools=new \JsApi_pub();
         if(!isset($_GET['code'])){
              $redirectUrl=$request->url(true);
             $tools->getCode($redirectUrl);   //获取code
         }else{
             $tools->setCode($_GET['code']);   //获取openid
            $opendid=$tools->getOpenid(); 
         }
         
        
        $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("body", $order_title); //商品描述
            
            $unifiedOrder->setParameter("out_trade_no", "$order_no"); //商户订单号
          
            $unifiedOrder->setParameter("total_fee", $wxje*100); //总金额
            

            /*
             * 换成你的域名写死
             */
            $unifiedOrder->setParameter("notify_url", $request->domain().'/pay/Pay/wx.html'); //回调地址
           
            $unifiedOrder->setParameter("trade_type", "JSAPI"); //交易类型
            //设置openid
            $unifiedOrder->setParameter("openid", "$opendid"); //交易类型
            $unifiedOrderResult = $unifiedOrder->getResult();
            if ($unifiedOrderResult["return_code"] == "FAIL") {
                //商户自行增加处理流程
                echo "通信出错：" . $unifiedOrderResult['return_msg'] . "<br>";
            } elseif ($unifiedOrderResult["result_code"] == "FAIL") {
                //商户自行增加处理流程
                echo "错误代码：" . $unifiedOrderResult['err_code'] . "<br>";
                echo "错误代码描述：" . $unifiedOrderResult['err_code_des'] . "<br>";
            } elseif ($unifiedOrderResult["prepay_id"] != NULL) {
                
                //获取参数
                $tools->setPrepayId($unifiedOrderResult["prepay_id"]);
                $jsApiParameters=$tools->getParameters();
              
                //商户自行增加处理流程
                /*
                  $data['u_id'] = session('uid');
                  $data['dh'] = $out_trade_no;
                  $data['total_fee'] = $wxje;
                  $data['dstatus'] = '0';
                  $data['dtime'] = time();
                  $data['jklx'] = '2';
                  session('wxdh', $out_trade_no);
                  $ord = M('Orderlist');
                  $ord->add($data);
                 */
            }
            $this->assign('out_trade_no', $order_no);
            $this->assign('jsApiParameters', $jsApiParameters);
            $this->assign('wxje', $wxje);
            $this->assign('unifiedOrderResult', $unifiedOrderResult);
            return $this->fetch();
    }
    
    
    
    /*
     * 手机端除微信端支付宝支付
     */
    public function baowap(Request $request) {
        
         $orderid= session('order_no');  //input('orderid')
         $orderid || $this->error("未接收到订单号");
         $order=db('pay_order')->where(['order_no'=>$orderid])->find();
         $order_no=$order['order_no'];
         if(empty($order)){
             $this->error("该订单不存在");
         }
        $api=new payapi\baoapi();
             //设置异步通知地址
             $api->setNotify($request->domain().url('pay/Pay/zfb'));
             //设置同步跳转地址
             $api->setReturn($request->domain().url('pay/Paylist/success'));
             $params=[
                 'subject'=>'测试',
                 'out_trade_no'=>$order_no,
                 'total_amount'=>0.01
             ];
            
             $api->wappay($params);   //手机端除微信端支付宝支付
    }
    
    //支付成功页面
    public function success() {
        echo '支付成功';
    }

}
