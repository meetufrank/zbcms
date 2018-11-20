<?php
namespace app\api\controller;


use app\api\controller\Response;
class Index{
    
    
    public function index() {
        print_r();exit;
        $data=[
            'id'=>1,
            'name'=>'2222',
            'ddd'=>[1,2,3,3]
        ];
        Response::show(200, '测试', $data,'json');
    }
}

