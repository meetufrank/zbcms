<?php
namespace app\admin\controller;
use think\Db;
use think\Request;
use think\Controller;
use think\session;
class Ischanneladmin extends Common
{
    public function index(){
        if(request()->isPost()){

            $ca_user = input("ca_user");
            $ca_pwd = input("ca_pwd");

            print_r("adf");exit;

            $data = [
                'ca_user' => $ca_user,
                'ca_pwd' => $ca_pwd,
            ];

            $is = Db::name('channel_admin')->where($data)->find();
            if(!empty($is)){
                Session::set('ca_id',$is['id']);
                return $this -> fecth("Index/index");
            }else{
                $this->error('请输入正确的用户名和密码!','ischanneladmin/index',3);exit;
            }

        }else{
            return $this -> fetch();
        }
    }


}