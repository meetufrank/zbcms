<?php
namespace app\api\controller;
class Response{
    
    const JSON='json';
    
    /*
     * 按综合方式输出通信数据
     * @param integer $code 状态码
     * @param string $message 提示信息
     * @param array $data 数据
     * @param string $type 数据类型
     * return string
     */
    public static function show($code,$message='',$data=array(),$type=self::JSON){
        if(!is_numeric($code)){
            return '';
        }
        $type= isset($_GET['format'])?$_GET['format']: self::JSON;
        $result=[
            'code'=>$code,
            'message'=>$message,
            'data'=>$data
        ];
        $type= strtolower($type);   //统一小写
        if($type=='json'){
            self::json($code,$message,$data);
            exit;
        }elseif($type=='array'){    //调试功能专用
            var_dump($result);
            exit;
        }elseif($type=='xml'){
            self::xmlEncode($code,$message,$data);
            exit;
        }else{
            //TODO
        }
    }
    /*
     * 按json方式输出通信数据
     * @param integer $code 状态码
     * @param string $message 提示信息
     * @param array $data 数据
     * return string
     */
    public static function json($code,$message='',$data=array()) {
        if(!is_numeric($code)){
            return '';
        }
        $result=[
            'code'=>$code,
            'message'=>$message,
            'data'=>$data
        ];
        echo json_encode($result);
        exit;
    }
    
    /*
     * 按xml方式输出通信数据
     * @param integer $code 状态码
     * @param string $message 提示信息
     * @param array $data 数据
     * return string
     */
    public static function xmlEncode($code,$message='',$data=array()) {
        if(!is_numeric($code)){
            return '';
        }
        $result=[
            'code'=>$code,
            'message'=>$message,
            'data'=>$data
        ];
        header("Content-Type:text/xml");
        $xml="<?xml version='1.0' encoding='UTF-8'?>\n";
        $xml.="<root>\n";
        $xml.= self::xmlToEncode($result);
        $xml.="</root>\n";
        
        echo $xml;
        exit;
    }
    
    /*
     * 将数组数据转换为xml标签
     * @param array $data 数据
     * return string
     */
    public static function xmlToEncode($data) {
        $xml=$attr='';
        foreach ($data as $key => $value) {
            if(is_numeric($key)){
                $attr=" id='{$key}'";
                $key='item';
            }
            $xml.="<{$key}{$attr}>\n";
            $xml.= is_array($value)?self::xmlToEncode($value):$value;
            $xml.="</{$key}>\n";
        }
        
        return $xml;
    }
}

