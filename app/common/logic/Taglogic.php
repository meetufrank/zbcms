<?php
namespace app\common\logic;
class Taglogic extends Logic {
    
    protected $db='tag';
    /*
     * 根据条件查询数据(包含特殊条件)
     */
    public function valid_select($fields='',$where=[],$type=0){
        $query= db($this->db);
         $map=[
          0=>[
             'is_open'=>1,
             'status'=>1,
             'deletetime'=>0
          ],
          1=>[
             'is_open'=>1,
             'createtime'=>['elt',time()],
             'deletetime'=>0
          ]
        ];
         //加入额外条件
       if(is_array($where)&&!empty($where)){
             foreach ($map as $key => $value) {
                 foreach ($where as $k => $v) {
                     $map[$key][$k]= $v;
                 }
             }
         }
        define('WHEREARR', json_encode($map));
        
        if(!empty($fields)){
            $query=$query->field($fields);
        }
        $query=$query
        ->where(
            function($query){
              $query->where(json_decode(WHEREARR,true)[0]);
            }    
        )
        ->whereOr(
            function($query){
              $query->where(json_decode(WHEREARR,true)[1]);
            }    
        );
        
        if($type){
            return $query->find();
        }else{
            return $query->select();
        }
    }
   

}
