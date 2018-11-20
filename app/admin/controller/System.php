<?php
namespace app\admin\controller;
use think\Db;
use think\Request;
use clt\Leftnav;
use app\admin\model\System as SysModel;
use app\common\payapi;
use think\Loader;
use app\common\logic\PayLogic;
use app\common\logic\CommonLogic;
class System extends Common
{
    /********************************站点管理*******************************/
    //站点设置
    public function system($sys_id=1){
        $table = db('system');
        if(request()->isPost()) {
            $datas = input('post.');
            if($table->where('id',1)->update($datas)!==false) {
                savecache('System');
                return json(['code' => 1, 'msg' => '站点设置保存成功!', 'url' => url('system/system')]);
            } else {
                return json(array('code' => 0, 'msg' =>'站点设置保存失败！'));
            }
        }else{
            $system = $table->field('id,name,url,title,key,des,bah,copyright,ads,tel,email,logo')->find($sys_id);
            $this->assign('system', $system);
            return $this->fetch();
        }
    }
    public function email(){
        $table = db('config');
        if(request()->isPost()) {
            $datas = input('post.');
            foreach ($datas as $k=>$v){
                $table->where(['name'=>$k,'inc_type'=>'smtp'])->update(['value'=>$v]);
            }
            return json(['code' => 1, 'msg' => '邮箱设置成功!', 'url' => url('system/email')]);
        }else{
            $smtp = $table->where(['inc_type'=>'smtp'])->select();
            $info = convert_arr_kv($smtp,'name','value');
            $this->assign('info', $info);
            return $this->fetch();
        }
    }
    public function trySend(){
        $sender = input('email');
        //检查是否邮箱格式
        if (!is_email($sender)) {
            return json(['code' => -1, 'msg' => '测试邮箱码格式有误']);
        }
        $send = send_email($sender, '测试邮件', '您好！这是一封来自'.$this->system['name'].'的测试邮件！');
        if ($send) {
            return json(['code' => 1, 'msg' => '邮件发送成功！']);
        } else {
            return json(['code' => -1, 'msg' => '邮件发送失败！']);
        }
    }
    
    
    
    /********************************支付管理*******************************/
    /*
     * 支付配置列表
     */
      public function payconf(){
          $data=db('paylist')->where(['delete_time'=>0])->select();
          foreach ($data as $key => $value) {
              if(!empty($data[$key]['data'])){
              $data[$key]['data']=json_decode($data[$key]['data'],true); 
              }
          }
          
          $this->assign('info', $data);
        return $this->fetch();
     }
     /*
      * 支付配置修改与添加
      */
     public function payedit(){
         $table=db('paylist');
         if(request()->isPost()) {
         //接收参数
       $data=input('post.');
  
       //将转换成json
       if (isset($data['id'])){
           $id=$data['id'];
           //如果是支付宝
           if($id==1){
                $jsonarr=[
                     'app_id'=>$data['app_id'],
                     'merchant_private_key'=> CommonLogic::getInstance()->TrimStr($data['merchant_private_key']),
                     'alipay_public_key'=>$data['alipay_public_key']
                   ];
                $jsonstr= json_encode($jsonarr);
                $t=[
                    'data'=>$jsonstr,
                    'logo'=>$data['pic']
                ];
                $result=$table->where(['id'=>$id])->update($t);
                if($result){
                   return json(['code' => 1, 'msg' => '修改配置成功！']); 
                }else{
                    return json(['code' => -1, 'msg' => '修改配置失败！']);
                }
           }
           //如果是微信
           if($id==2){
                $jsonarr=[
                     'appid'=>$data['appid'],
                     'appsecret'=>$data['appsecret'],
                     'mchid'=>$data['mchid'],
                     'key'=>$data['key'],
                     'notify'=>$data['notify']
                   ];
                $jsonstr= json_encode($jsonarr);
                $t=[
                    'data'=>$jsonstr,
                    'logo'=>$data['pic']
                ];
                $result=$table->where(['id'=>$id])->update($t);
                if($result){
                   return json(['code' => 1, 'msg' => '修改配置成功！']); 
                }else{
                    return json(['code' => -1, 'msg' => '修改配置失败！']);
                }
           }
       }
   }
   
        
     }
     
     /*
      * 测试调用
      * 
      */
     public function payceshi(Request $request){
        
         $orderid=input('orderid');
         $orderid || $this->error("未接收到订单号");
         $order=db('pay_order')->where(['order_no'=>$orderid])->find();
         if(empty($order)){
             $this->error("该订单不存在");
         }
        $id = input('id');
        $order_no=$order['order_no'];
        if($id==1){  //支付宝
           
           
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
             //$api->wappay($params);   //手机端
         }
         if($id==2){  //微信
             
             Loader::import('wxpay.WxPayPubHelper');
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
         
         
      }
  
      //生成测试订单
    public function addorder(){
          $wxje = 0.01; //金额
          $out_trade_no = date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8); //订单号
          $result=db('pay_order')->insert(array(
                'order_no' => $out_trade_no,
                'order_money' => $wxje, //订单金额
                'state' => 0, //支付状态 0 未支付, 1已支付
                'uid' => 1, //用户uid
                'addtime' => time(), //下单时间
                'update_time' => 0 //支付时间
            ));
          if($result){
               return json(['code' => 1, 'msg' => '生成订单成功！','id'=>$out_trade_no]); 
          }else{
               return json(['code' => 0, 'msg' => '生成订单失败！']); 
          }
    }
      
       //查询自己的订单表 看看是否被支付 （主要使用到微信支付页面支付完毕后跳转）
    public function paylog() {
        $order_no = $_POST['out_trade_no'];

        /*
         * 这里写查询
         */
        if($order_no){
        $order_info = db('pay_order')->where("order_no='" . $order_no . "' AND state = 1")->find();
        }


        if ($order_info) {
            echo 1;
        } else {
            echo 0;
        }
    }


      
      
}
