<?php
namespace app\pay\controller;

use think\Controller;
use think\Loader;
use app\common\logic\PayLogic;
use think\db;
use app\common\payapi;
class Pay extends Controller {

    
 
//测试支付微信回调

    public function wx() {
        Loader::import('wxpay.WxPayPubHelper');
       //使用通用通知接口
        $notify = new \Notify_pub();

        //存储微信的回调
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $notify->saveData($xml);
        //var_dump($xml);
        //验证签名，并回应微信。
        //对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
        //微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
        //尽可能提高通知的成功率，但微信不保证通知最终能成功。
        if ($notify->checkSign() == FALSE) {
            $notify->setReturnParameter("return_code", "FAIL"); //返回状态码
            $notify->setReturnParameter("return_msg", "签名失败"); //返回信息
        } else {
            $notify->setReturnParameter("return_code", "SUCCESS"); //设置返回码
        }
        $returnXml = $notify->returnXml();
       file_put_contents("notify.txt", date("Y-m-d H:i:s").$notify->data["return_code"]);   
        //==商户根据实际情况设置相应的处理流程，此处仅作举例=======
        if ($notify->checkSign() == TRUE) {
            if ($notify->data["return_code"] == "FAIL") {
                //此处应该更新一下订单状态，商户自行增删操作
                $this->error("失败1");
            } elseif ($notify->data["result_code"] == "FAIL") {
                $this->error("失败2");
            } else {
                /*
                 * 这里写业务处理【数据库】
                 */
                   
                  $simple = json_decode(json_encode(simplexml_load_string($GLOBALS['HTTP_RAW_POST_DATA'], 'SimpleXMLElement', LIBXML_NOCDATA)), true);
     file_put_contents("notify.txt", json_encode($simple));

        $order_no = $simple['out_trade_no'];
        
            $order_info = db('pay_order')->where("order_no= '".$order_no."'")->find();
            //若是未付款则更新
            if ($order_info['state'] == 0) {
                $data['trade_no'] = $simple['trade_no'];
                $data['state'] = 1;
                $data['update_time'] = time();
                db('pay_order')->where("order_no='".$order_no."'")->update($data);
                //支付成功，则生成报名记录
                if(!empty($order_info['order_data'])){
                       $bmdata=json_decode(unserialize($order_info['order_data']),true);
                       if(is_array($bmdata)){
                          db('gameenlist')->insert($bmdata['data']); 
                        
                       }

                    }
            }
               // $this->success("支付成功！");
              echo exit('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>');  
            }
        }
    }
    
    
     //测试支付宝回调

    public function zfb() {
        header("Content-type: text/html; charset=utf-8");
        $api=new payapi\baoapi();
        //验签
        $result=$api->notify($_POST);
//        if($result){
//            file_put_contents("zfbnotify.txt", '1');
//        }else{
//            file_put_contents("zfbnotify.txt", '1');
//        }
//     
//        echo 'success';exit;
//        $alipay_config = C('alipay_config');
//        //计算得出通知验证结果
//        $alipayNotify = new \ AlipayNotify($alipay_config);
//        $verify_result = $alipayNotify->verifyNotify();
       if ($result) {
            //验证成功
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
            $order_no = $_POST['out_trade_no'];      //商户订单号
            $trade_no = $_POST['trade_no'];          //支付宝交易号
            $trade_status = $_POST['trade_status'];      //交易状态
            $total_fee = $_POST['total_fee'];         //交易金额
            $notify_id = $_POST['notify_id'];         //通知校验ID。
            $notify_time = $_POST['notify_time'];       //通知的发送时间。格式为yyyy-MM-dd HH:mm:ss。
            $buyer_email = $_POST['buyer_email'];       //买家支付宝帐号；
            if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                 $order_info = db('pay_order')->where("order_no= '".$order_no."'")->find();
            //若是未付款则更新
            if ($order_info['state'] == 0) {
                $data['trade_no'] =$trade_no;
                $data['state'] = 1;
                $data['update_time'] = time();
                db('pay_order')->where("order_no='".$order_no."'")->update($data);
                if(!empty($order_info['order_data'])){
                       $bmdata=json_decode(unserialize($order_info['order_data']),true);
                       if(is_array($bmdata)){
                          db('gameenlist')->insert($bmdata); 
                        
                       }

                    }





                   
            }
                /*
                 * 这里写业务处理【数据库】
                 */
                echo "success";        //请不要修改或删除
            } else {
                //验证失败
                echo "fail";
            }
       }
    }

}
