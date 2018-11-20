<?php
namespace app\admin\controller;
use think\Db;
use think\Request;
use think\Controller;
class PayOrder extends Common
{
    public function _initialize(){
        parent::_initialize();
    }
    //订单列表
    public function index(){
        if(request()->isPost()) {
            $key = input('post.key');
            $this->assign('testkey', $key);
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $map=[
                'po.order_no|po.title'=>['like','%'.$key.'%']
            ];
            $order='po.addtime desc';
            $list = Db::table(config('database.prefix') . 'pay_order')
                ->alias('po')
                ->join(config('database.prefix').'order_type ot','po.order_type = ot.id','left')
                ->field('po.*,ot.name as typename')
                ->where($map)
                ->order($order)
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->toArray();
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['addtime'] = date('Y-m-d H:s',$v['addtime']);
                $list['data'][$k]['update_time'] = date('Y-m-d H:s',$v['update_time']);
                $list['data'][$k]['tktime'] = date('Y-m-d H:s',$v['tktime']);
                $list['data'][$k]['order_data'] = json_decode(unserialize($v['order_data']),true);
            }
          
            return $result = ['code'=>0,'msg'=>'获取成功!','data'=>$list['data'],'count'=>$list['total'],'rel'=>1];
        }
        return $this->fetch('payorder/index');
    }

    public function edit(){
        if(request()->isPost()) {
            $data = input('post.');
            $list=db('pay_order')->where(array('id'=>$data['id']))->find();
            if($list['state']==0){
               $result['code'] = 0;
               $result['msg'] = '该订单不可操作';
           
                $result['url'] = url('index');
                return $result; 
            }
           $newdata['state']=$data['state'];
           if($data['state']==2){
              $newdata['tktime']=time(); 
           }
           
            db('pay_order')->where(['id'=>$data['id'],'state'=>1])->update($newdata);
            $result['code'] = 1;
            $result['msg'] = '订单修改成功!';
           
            $result['url'] = url('index');
            return $result;
        }else{
            $id= input('id');
            $adInfo=db('pay_order')->where(array('id'=>$id,'state'=>1))->find();
            if(empty($adInfo)){
                $this->error('该订单不可操作');
            }
            $this->assign('adtypeList',json_encode($adtypeList,true));
            $this->assign('info',json_encode($adInfo,true));
            $this->assign('title',lang('edit').lang('ad'));
            return $this->fetch('payorder/form');
        }
    }
  
//    public function adOrder(){
//        $ad=db('ad');
//        $data = input('post.');
//        if($ad->update($data)!==false){
//            cache('adList', NULL);
//            return $result = ['msg' => '操作成功！','url'=>url('index'), 'code' =>1];
//        }else{
//            return $result = ['code'=>0,'msg'=>'操作失败！'];
//        }
//    }
//    public function del(){
//        db('ad')->where(array('ad_id'=>input('ad_id')))->delete();
//        cache('adList', NULL);
//        return ['code'=>1,'msg'=>'删除成功！'];
//    }
//    public function delall(){
//        $map['ad_id'] =array('in',input('param.ids/a'));
//        db('ad')->where($map)->delete();
//        cache('adList', NULL);
//        $result['msg'] = '删除成功！';
//        $result['code'] = 1;
//        $result['url'] = url('index');
//        return $result;
//    }

   
}