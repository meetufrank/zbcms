<?php
namespace app\common\logic;
class CompanyLogic extends Logic {
    
    protected $db='company';
    /*
     * 根据条件查询数据(默认不包含特殊条件,不包含关联)
     */
    public function select($fields='',$where,$type=0){
        $query= db($this->db);
        if(!empty($fields)){
            $query=$query->field($fields);
        }
        if($type){
            return $query->where($where)->find();
        }else{
            return $query->where($where)->select();
        }
    }
   

}
