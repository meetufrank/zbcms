<?php
namespace app\home\controller;
use think\Request;
use think\Cookie;
use think\Session;
use think\Db;

class Assistant extends Common{
    public function _initialize(){
        parent::_initialize();
    }

    public function index(){    //聊天互动助手

        $id = input('ad_id');    //直播频道id
        $info = db('channel')->where("id = $id")->find();   //查询出频道详情
        $this -> assign('info',$info);

        $issh = db('chattype')->where('cid',$id)->select();    //查询出是否开启了聊天审核
        if(!empty($issh)){
            $this -> assign('issh',1);
        }

        $gfimg = "https://cdn13.mudu.tv/assets/upload/154466654253127.png";
        $this -> assign('gfimg',$gfimg); //官方图片路径

        $zhiding_list = db('notice')->where('cid',$id)->select(); //查询出该频道置顶列表
        $this -> assign('zhiding_list',$zhiding_list);

        //互动聊天历史数据
        $chat_list = db('chat')->where('cid',$id)->order('chattopping_time desc,id desc')->select();
        //print_r($chat_list);exit;
        $this -> assign('chat_list',$chat_list);

        //用户白名单
        $channel_user_list = db('whitelist_user_list')->where("pid =$id")->find();   //查询该频道是否有用户白名单
        if(!empty($channel_user_list)){  //存在白名单
           $count_liust_bmd = db('whitelist_user_list')->where("pid =$id")->count();  //该频道白名单统计人数

            //查看用户登录数据

            //$bmd_login_user_list = db('access')->where("cid =$id")->group('username')->order('adate desc')->select();
            $bmd_channel_user_list = db('whitelist_user_list')->where("pid =$id")->select();   //查询该频道的用户

            //对比赋值状态
            $bmd_login_user_s = [];
            $bmd_user_login_count = 0;  //频道白名单登录统计初始化
            foreach($bmd_channel_user_list as $k => $v){
                $bmd_login_where = "cid = $id and username = '".$v['w_name']."'";
                $bmd_login_user_find = db('user_record')->where($bmd_login_where)->find();

                $bmd_login_user_s[$k]['username'] = $v['w_name'];
                if($bmd_login_user_find['leave_time'] == 1){   //存在更改其为在线状态
                    $bmd_login_user_s[$k]['type'] = $v['type'] = 1;  //上线
                    $bmd_user_login_count++;
                }else{
                    $bmd_login_user_s[$k]['type'] = $v['type'] = 2;  //未上线
                }
            }

            //排序  上线排在未上线前面
            $last_type= array_column($bmd_login_user_s,'type');   //抽取字段 type
            array_multisort($last_type,SORT_ASC,$bmd_login_user_s);    //对类型上线进行降序排序


            $this -> assign('bmd_login_user_s',$bmd_login_user_s);  //查看用户登录数据状态集
            $this -> assign('count_liust_bmd',$count_liust_bmd); //该频道白名单统计人数
           $this -> assign('bmd_user_login_count',$bmd_user_login_count);  //频道白名单登录统计
           $this -> assign('channel_user_list_type',1);    //存在白名单，赋值状态判断1
        }


        return $this -> fetch();
    }

    //白名单登录列表
    public function chatuserbmd(){
        $cid = $_POST['cid'];   //频道id

        //用户白名单
        $channel_user_list = db('whitelist_user_list')->where("pid =$cid")->find();   //查询该频道是否有用户白名单
        if(!empty($channel_user_list)){  //存在白名单
            $count_liust_bmd = db('whitelist_user_list')->where("pid =$cid")->count();  //该频道白名单统计人数

            //查看用户登录数据
            $bmd_channel_user_list = db('whitelist_user_list')->where("pid =$cid")->select();   //查询该频道的用户

            //对比赋值状态
            $bmd_login_user_s = [];
            $bmd_user_login_count = 0;  //频道白名单登录统计初始化
            foreach($bmd_channel_user_list as $k => $v){
                $bmd_login_where = "cid = $cid and username = '".$v['w_name']."'";
                $bmd_login_user_find = db('user_record')->where($bmd_login_where)->find();

                $bmd_login_user_s[$k]['username'] = $v['w_name'];
                if($bmd_login_user_find['leave_time'] == 1){   //存在更改其为在线状态
                    $bmd_login_user_s[$k]['type'] = $v['type'] = '上线';  //上线
                    $bmd_user_login_count++;
                }else{
                    $bmd_login_user_s[$k]['type'] = $v['type'] = '未上线';  //未上线
                }
            }

            //排序  上线排在未上线前面
            $last_type= array_column($bmd_login_user_s,'type');   //抽取字段 type
            array_multisort($last_type,SORT_ASC,$bmd_login_user_s);    //对类型上线进行降序排序
            $data['bmd_login_user_s'] = $bmd_login_user_s;  //查看用户登录数据状态集
            $data['count_liust_bmd'] = $count_liust_bmd;  //该频道白名单统计人数
            $data['bmd_user_login_count'] = $bmd_user_login_count;  //频道白名单登录统计
            $data['channel_user_list_type'] = 1;  //存在白名单，赋值状态判断1
            echo json_encode($data);exit;


        }

    }


    public function addnotice(){   //新增置顶消息

        $ctext = $_POST['ctext'];  //置顶消息
        $cid = $_POST['cid'];   //频道id
        $ndate = date("Y-m-d H:i:s");
        $add_data = [
            'ctext' => $ctext,
            'cid' => $cid,
            'ndate' => $ndate
        ];
        Db::table('clt_notice')->insert($add_data);
        $nid = Db::name('clt_notice')->getLastInsID();

        $date = [
            'ctext' => $ctext,
            'cid' => $cid,
            'ndate' => $ndate,
            'nid' => $nid,
            'data' => 1
        ];
        echo json_encode($date);exit;

    }

    public function istoexamine(){  //聊天互动是否需要审核
        $sh_type = $_POST['sh_type'];  //审核类型
        $cid = $_POST['cid'];   //频道id


        $issh = db('chattype')->where("cid = $cid")->find();   //查询直播频道是否存在互动聊天审核

        if(!empty($issh['cid'])){  //存在审核
            if($sh_type == 2){  //关闭审核
                db('chattype')->where('cid',$cid)->delete();   //删除该频道互动聊天审核
            }
        }else{
            if($sh_type == 1){
                $data = [
                    'ctype' => $sh_type,
                    'cid' => $cid,
                    'ctype_date' => date("Y-m-d H:i:s")
                ];
                db('chattype')->insert($data);
            }
        }
        //$info = db('clt_chattype')->where("id = $id")->find();
    }



    //置顶聊天
    public function chattopping(){
        $chatid = $_POST['chatid'];  //聊天id
        $cid = $_POST['cid'];  //频道id
        $chattopping_time =  date("Y-m-d H:i:s"); //置顶聊天数据时间


        //更改chat类型为置顶
        $where = "cid = ".$cid." and id = ".$chatid;
        Db::table('clt_chat')->where($where)->update(['ctype' => '2','chattopping_time' => $chattopping_time]);  //1为普通消息   2为置顶消息

        $date = [
            'chatid' => $chatid, //聊天id
            'cid' => $cid,  //聊天频道id
            'data' => 1
        ];
        echo json_encode($date);exit;
    }



    //取消置顶聊天
    public function cancelchattopping(){
        $chatid = $_POST['chatid'];  //聊天id
        $cid = $_POST['cid'];  //频道id

        //更改chat类型为置顶
        $where = "cid = ".$cid." and id = ".$chatid;
        Db::table('clt_chat')->where($where)->update(['ctype' => '1','chattopping_time' => '']);  //1为普通消息   2为置顶消息

        $date = [
            'chatid' => $chatid, //聊天id
            'cid' => $cid,  //聊天频道id
            'data' => 1
        ];
        echo json_encode($date);exit;
    }



}