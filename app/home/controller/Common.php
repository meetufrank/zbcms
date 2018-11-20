<?php
namespace app\home\controller;
use think\Input;
use think\Db;
use clt\Leftnav;
use think\Request;
use think\Controller;
use think\Session;
class Common extends Controller{
    protected $pagesize;
    public function _initialize(){
        $sys = F('System');
        $this->assign('sys',$sys);
        //获取控制方法
        $request = Request::instance();
        $action = $request->action();
        $controller = $request->controller();
        $this->assign('action',($action));
        $this->assign('controller',strtolower($controller));
        define('MODULE_NAME',strtolower($controller));
        define('ACTION_NAME',strtolower($action));

        //导航
        $category = db('category');
        $thisCat = $category->where('id',input('catId'))->find();
        $this->assign('title',$thisCat['title']);
        $this->assign('keywords',$thisCat['keywords']);
        $this->assign('description',$thisCat['description']);
        define('DBNAME',strtolower($thisCat['module']));
        $this->pagesize = $thisCat['pagesize']>0 ? $thisCat['pagesize'] : '';
        // 获取缓存数据
        $cate = cache('cate');
        if(!$cate){
            $column_one = $category->where(['parentid'=>0,'ismenu'=>1])->order('listorder')->select();
            $column_two = $category->where(['ismenu'=>1])->order('listorder')->select();;
            $tree = new Leftnav ();
            $cate = $tree->index_top($column_one,$column_two);
            cache('cate', $cate, 3600);
        }
        $this->assign('category',$cate);
        //广告
        $adList = cache('adList');
        if(!$adList){
            $adList = db('ad')->where(['type_id'=>1,'open'=>1])->order('sort asc')->limit('4')->select();
            cache('adList', $adList, 3600);
        }
        $this->assign('adList', $adList);
        //友情链接
        $linkList = cache('linkList');
        if(!$linkList){
            $linkList = db('link')->where('open',1)->order('sort asc')->select();
            cache('linkList', $linkList, 3600);
        }
		$this->assign('linkList', $linkList);
    }
    public function _empty(){
        return $this->error('空操作，返回上次访问页面中...');
    }


    /**
    使用curl方式实现get或post请求
    @param $url 请求的url地址
    @param $data 发送的post数据 如果为空则为get方式请求
    return 请求后获取到的数据
     */
    function curlRequest($url,$data = ''){
        $ch = curl_init();
        $params[CURLOPT_URL] = $url;    //请求url地址
        $params[CURLOPT_HEADER] = false; //是否返回响应头信息
        $params[CURLOPT_RETURNTRANSFER] = true; //是否将结果返回
        $params[CURLOPT_FOLLOWLOCATION] = true; //是否重定向
        $params[CURLOPT_TIMEOUT] = 30; //超时时间
        if(!empty($data)){
            $params[CURLOPT_POST] = true;
            $params[CURLOPT_POSTFIELDS] = $data;
        }
        $params[CURLOPT_SSL_VERIFYPEER] = false;//请求https时设置,还有其他解决方案
        $params[CURLOPT_SSL_VERIFYHOST] = false;//请求https时,其他方案查看其他博文
        curl_setopt_array($ch, $params); //传入curl参数
        $content = curl_exec($ch); //执行
        curl_close($ch); //关闭连接
        return $content;
    }

    //根据ip,获取城市名称
    public function getIpInfo($ip){

        //get_client_ip()为tp自带函数，如没有，自己百度搜索。此处就不重复复制了
        if(empty($ip)) $ip=get_client_ip();
        $url='http://ip.taobao.com/service/getIpInfo.php?ip='.$ip;

        $result = $this->curlRequest($url);

        $result = json_decode($result,true);
        if($result['code']!==0 || !is_array($result['data'])) return false;

        return $result['data'];
    }



}