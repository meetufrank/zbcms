<?php
namespace app\home\controller;
use think\Session;
use think\Request;
use Ipaddresscity\Iplocation;
use Think\Db;
class Userlogin extends Common{


    //用户登录
    public function index(){

        $userlogin = new Userlogin();

        $pid = $_GET['ad_id'];  //频道id
        $type = $_GET['user_type'];  //用户登录类型

        $pinfo = db('payinfo')->where("cid = $pid")->find();
        if($pinfo['pend_time']>date("Y-m-d")){
            //付费直播类型
            if(Request::instance()->isMobile()) { //手机端
                    $this->redirect('MobileUserlogin/index', ['ad_id' => $pid,'user_type'=>6]);
            }else{ //pc端
                    $this->redirect('Home/Index/pcpaylive', ['ad_id' => $pid]);
            }
        }

        //用户白名单
        $channel_user_list = db('whitelist_user_list')->where("pid =$pid")->find();   //查询该频道是否有用户白名单
        if($pid != 79){
            if(!empty($channel_user_list)){   //该频道存在用户白名单

                if(Request::instance()->isMobile()) { //手机端
                    $this->redirect('home/channeluserlist/mobiile_index', ['ad_id' => $pid]);
                }else{   //pc端
                    $this->redirect('home/channeluserlist/index', ['ad_id' => $pid]);
                }
            }
        }



        //获取到聊天用户的ip地址
        $request = Request::instance();
        $ip = $request->ip();  //获取客户端ip
        $Index = new IpLocation();
        $ipres = $Index -> getlocation($ip); //根据查询地址地址

        $user_ip = $ipres['ip'];  //客户端 --- 最后登录ip



        if($pid == 99 and $type == 0){  //频道id为思科
            $this->redirect('http://qiandao.easylaa.com/webinar/cisco/login.aspx?b=30326');
        }

        if($type == 4){  //客户网页授权
            $key = $_POST['key'];  //验证appkey
            $user_id = $_POST['user_id'];  //用户id
            $user_name = $_POST['user_name'];  //用户姓名
            $user_img = $_POST['user_img'];  //用户姓名

            $user_shoquan_info = db('user_shoquan')->where("cid = $pid")->find();     //查询对应的用户授权信息




            $user_address = $ipres['country'].$ipres['province'].$ipres['city'];   //客户端 --- 地址
            $user_access_source = $userlogin -> getFromPage();//客户端 --- 访问来源
            $user_isMobile= $userlogin -> isMobile();  //客户端 --- 最好观看方式

            if($pid == $user_shoquan_info['cid'])  //频道id
                if(empty($key)){
                    $date['code'] = 1;
                    $date['errmsg'] = '频道key值有误!!!';
                    echo json_encode($date);exit;
                }else if($key != $user_shoquan_info['key']){
                    $date['code'] = 1;
                    $date['errmsg'] = '频道key值有误!!!';
                    echo json_encode($date);exit;
                }else{
                    if(empty($user_id)){
                        $date['code'] = 2;
                        $date['errmsg'] = '用户id有误!!!';
                        echo json_encode($date);exit;
                    }else if(empty($user_name)){
                        $date['code'] = 3;
                        $date['errmsg'] = '用户姓名有误!!!';
                        echo json_encode($date);exit;
                    }else{
                        Session::set('userid',$user_id);  //第三方用户id
                        Session::set('pid',$pid);  //频道id
                        Session::set('usernames',$user_name);
                        Session::set('userip',$user_ip);
                        if($user_img == ''){
                            $user_img = 'https://static.mudu.tv/index/avatar.png';
                        }
                        Session::set('userimgurl',$user_img);  //用户头像

                        // 是否为手机访问
                        if (Request::instance()->isMobile()) {


                            $where_r = "cid = $pid and userid = $user_id";
                            $user_record_info = Db::table('clt_user_record')->where($where_r)->find();
                            $data = ['userid' => $user_id,    //第三方id
                                'username' => $user_name,  //观众名
                                'address' => $user_address,   //地址
                                'watchz_times' => '1',  //观看总时长
                                'get_time' => date("Y-m-d H:i:s"),  //最后进入时间
                                'leave_time' => '1',  //最后在线时间
                                'watch_equipment' => '手机端',  //最后观看设备
                                'user_ip' => $ip,  //最后登录ip
                                'cid' => $pid,  //绑定直播频道id
                            ];
                            if(empty($user_record_info)){  //不存在此用户,即添加
                                //添加用访问数据
                                Db::table('clt_user_record')->insert($data);
                            }else{
                                Db::table('clt_user_record')
                                    ->where('id',$user_record_info['id'] )
                                    ->update($data);
                            }



                            $this->redirect('MobileUserlogin/index', ['ad_id' => $pid,'user_type'=>5]);
                        }else{
                            $where_r = "cid = $pid and userid = $user_id";
                            $user_record_info = Db::table('clt_user_record')->where($where_r)->find();
                            //添加用访问数据
                            $data = ['userid' => $user_id,    //第三方id
                                     'username' => $user_name,  //观众名
                                     'address' => $user_address,   //地址
                                     'watchz_times' => '1',  //观看总时长
                                     'get_time' => date("Y-m-d H:i:s"),  //最后进入时间
                                     'leave_time' => '1',  //最后在线时间
                                     'watch_equipment' => '电脑端',  //最后观看设备
                                     'user_ip' => $user_ip,  //最后登录ip
                                     'cid' => $pid,  //绑定直播频道id
                            ];
                            if(empty($user_record_info)){  //不存在此用户,即添加
                                //添加用访问数据
                                Db::table('clt_user_record')->insert($data);
                            }else{  //存在该用户,即修改
                                Db::table('clt_user_record')
                                    ->where('id',$user_record_info['id'] )
                                    ->update($data);
                            }

                            $this->redirect('Home/Index/index', ['ad_id' => $pid]);
                        }

                    }
                }
            }





        //频道信息
        $info = db('channel')->where("id = $pid")->find();
        $this -> assign('info',$info);

        //获取到聊天用户的ip地址
        $request = Request::instance();
        $ip = $request->ip();  //获取客户端ip
        $Index = new IpLocation();
        $ipres = $Index -> getlocation($ip); //根据查询地址地址

        $user_id = $ipres['ip'];  //客户端 --- 最后登录ip
        $user_address = $ipres['country'].$ipres['area'].$ipres['province'];   //客户端 --- 地址
        $user_access_source = $userlogin -> getFromPage();//客户端 --- 访问来源
        $user_isMobile= $userlogin -> isMobile();  //客户端 --- 最好观看方式


        //0.游客登录模式
        if($type == 0){
            //$ipres = $Index -> getIpInfo("211.161.194.117" ); //根据ip获取城市地址 $ipres['city']
            if($ipres['city'] == ''){
                $ipres = $Index -> getIpInfo($ip);
            }
            $usernames = $ipres['city']."网友";

            Session::set('usernames',$usernames);
            Session::set('userimgurl','https://static.mudu.tv/index/avatar.png');  //用户昵称

            $this->redirect('Home/Index/index', ['ad_id' => $pid]);
        }else if($type == 1){     //1.报名问卷
            return $this -> fetch("index/logininfo");   //问卷页面
        }else if($type == 2){   //验证码观看
            $this->redirect('Home/Index/index', ['ad_id' => $pid]);
        }else if($type == 3){  //用户导入白名单
            $this->redirect('Home/Index/index', ['ad_id' => $pid]);
        }else if($type == 5){       //4.微信登录
            //获取code
            $code = $_GET['code'];
            //微信模式登录  nickname  headimgurl
            $wx_user_info = $this -> weLogin($code);
            //Session::set('usernames',$wx_user_info->nickname);  //用户昵称
            Session::set('userimgurl',$wx_user_info->headimgurl);  //用户昵称
            $this->redirect('Home/Index/index', ['ad_id' => $pid]);
        }



        //3.用户白名单



    }

    //获取用户观看设备
    public function isMobile(){
        if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
            return true;
        }
    }

    //获取网站来源
    public function getFromPage(){
        return $_SERVER['HTTP_REFERER'];
    }


    //验证码观看
    public function yzmgk(){

            $pid = $_GET['ad_id'];  //频道id
            $codevar = $_GET['codevar'];
            $yzmwhere = "pid = ".$pid." and codevar ="."'".$codevar."'";
            $yzmdata = db('code_view')->where($yzmwhere)->find();
            if($yzmdata['codevar'] == ''){
                echo 1;
            }else{
                echo 2;
            }
    }

    //用户白名单观看
    public  function bmd(){
        $pid = $_GET['ad_id'];  //频道id
        $w_name = $_GET['w_name'];
        $w_pwd = $_GET['w_pwd'];

        //验证用户
        $where_username = "pid = ".$pid." and w_name = '".$w_name."'";
        $isusername = db('whitelist_user_list')->where($where_username)->find();

        //验证密码
        $where_pwd = "pid = ".$pid." and w_name = '".$w_name."' and w_pwd = '".$w_pwd."'";
        $ispwd = db('whitelist_user_list')->where($where_pwd)->find();

        if(empty($isusername)){
            return  1; //用户名不存在
        }elseif(empty($ispwd)){
            return 2; //密码错误
        }else{
            return 3; //正确
        }

    }


    public function userlogin(){
        $pid = $_POST['pid'];
        $name = $_POST['name'];
        $phone = $_POST['phone'];


        Session::set('usernames',$name);
        Session::set('userimgurl','https://static.mudu.tv/index/avatar.png');  //用户昵称
        $this->redirect('Home/Index/index', ['ad_id' => $pid]);

    }

    //微信登录
    public function weLogin($code){

        //根据coke获取access_token
        $get_ac_url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=wx7eee3208b7b59ea1&secret=0e8eeac406fedfbd96029280127a4229&code=$code&grant_type=authorization_code";
        $res_data = $this->curlRequest($get_ac_url);
        $res_arr = json_decode($res_data,true);

        //根据access_token获取用户信息
        $get_user_url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$res_arr['access_token'].'&openid=wx7eee3208b7b59ea1&lang=zh_CN';
        $user_data = $this->curlRequest($get_user_url);
        $user_info = json_decode($user_data);

        return $user_info;
    }







}