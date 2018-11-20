<?php
namespace app\wdcz\controller;
require('vendor/alywdzh/aliyun-openapi-php-sdk-master/alywdcz.php');
/**
 * Created by PhpStorm.
 * User: Amazing
 * Date: 2017/12/5
 * Time: 11:27
 */

use Aliyun\Wdcz as w;  //引入阿里云智能媒体管理,文件格式转换

class Wdcz
{


   public function index($filename,$outputpath){
       $wdcz = new \alywdcz();
       $wdcz -> index($filename,$outputpath);

   }


}

