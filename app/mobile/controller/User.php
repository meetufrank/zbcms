<?php
namespace app\mobile\controller;

use app\common\logic\KjLogic;

class User extends Common{
    public function _initialize(){
        parent::_initialize();
    }
   
    /*
     * 我的订单
     */
    public function userorder() {
        $map=[
            'uid'=> session('user.id')
        ];
        $order=KjLogic::getInstance()->getorder($map);
        $noarr=[];
        $yesarr=[];
        if(!empty($order)){
            foreach ($order as $key => $value) {
                $order_data=[];
                if(!empty($value['order_data'])){
                    $order_data=json_decode(unserialize($value['order_data']),true);
                    if(!empty($order_data)){
                        
                        $order[$key]['order_data']=$order_data;
                    }else{
                        $order[$key]['order_data']=[];
                    }
                    
                }else{
                    $order[$key]['order_data']=[];
                }
               if($value['state']==0){
                   
                   $noarr[$key]=$order[$key];
                }elseif($value['state']==1){
                    $yesarr[$key]=$order[$key];
                }
           }
        }
        
        $this->assign('order',$order); //全部订单
        $this->assign('noarr',$noarr); //未支付订单
        $this->assign('yesarr',$yesarr); //已支付订单
       
        return $this->fetch();
    }
    
    
    
    
    /*
     * 支付 ，存储需要支付订单号
     */
    public function saveorderno() {
        $order_no= input('post.order_no');
        
        session('order_no',$order_no);
        
        $msg['code']=1;
        return json($msg);
    }
}