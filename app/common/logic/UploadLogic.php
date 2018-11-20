<?php
namespace app\common\logic;

class UploadLogic extends Logic {
  

   
       /*
     * base64链接生成图片
     */
      public function uploadphoto($data){
        // print_r($_POST['data']);exit;
       
          $base64_image_content = $data;
          $size=file_get_contents($base64_image_content);
          $size=strlen($size)/1024;
          if($size>2048){
              echo json(['code'=>0,'msg'=>'上传大小不可大于2m']);
              exit;
          }
         
//匹配出图片的格式
if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)){
$type = $result[2];
$new_file = "public/uploads/photo/".date('Ymd',time())."/";
if(!file_exists($new_file))
{
//检查是否有该文件夹，如果没有就创建，并给予最高权限
mkdir($new_file, 0700);
}
$new_file = $new_file.time().".{$type}";
if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))){
    
return '/'.$new_file;
}else{
    exit;
}
}
//        $path='public/uploads/photo/';
//        $output_file = time().'.jpeg';
//        $path = $path.$output_file;
//        $base_img = str_replace('data:image/jpeg;base64,', '', $data);
//        $data=base64_decode($base_img);
//        $file=file_put_contents($path, base64_decode($base_img));
//       
//         
//        return '/'.$path;
    }
  
}
