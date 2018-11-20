<?php
namespace app\common\logic;
use think\db;
class PayLogic extends Logic {
    

//获取微信配置信息 
    public function payconfig($id) {
        
       $apidata=db('paylist')->where(['id'=>$id])->find();
        //将配置 数据提取出来
            $data=json_decode($apidata['data'],true);
       return $data;
    }
}
