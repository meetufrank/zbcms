<?php
namespace app\common\logic;
class OprateLogic extends Logic {
    
    
    /*
     * 插入操作日志
     */
    public function insert($title='',$optateid){
        $adminid=session('aid');
        $aminname=session('username');
        $data=[
            'title'=>$title,
            'optateid'=>$optateid,
            'adminid'=>$adminid,
            'adminname'=>$aminname,
            'time'=>time(),
            'ip'=> request()->ip()
        ];
        db('opratelog')->insert($data);
    }
    
   

}
