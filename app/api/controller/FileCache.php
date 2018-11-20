<?php
namespace app\api\controller;



class FileCache{
    
    private $_dir;
    const EXT='.php';

    public function __construct() {
        
        $this->_dir= dirname(__FILE__);
    }
    /*
     * 获取或加入缓存数据
     * @param string $key 文件名
     * @param string $value 文件内容
     * @param string $path 文件路径
     * return string
     */
    public function cacheData($key,$value='',$path='') {
        $filename= $this->_dir.$path.$key.self::EXT;
        
        if(!$value!==''){   //将value写入缓存
            if(is_null($value)){
                return @unlink($filename);
            }
            $dir= dirname($filename);
            if(!is_dir($dir)){
                mkdir($dir,0777);
            }
            return file_put_contents($filename, json_encode($value));
        }
        if(!is_file($filename)){
            return false;
        }else{
            json_decode(file_get_contents($filename),true);
        }
    }
}

