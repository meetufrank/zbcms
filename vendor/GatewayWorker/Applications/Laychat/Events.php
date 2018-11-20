<?php
use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\Db;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    /**
     * 当客户端发来消息时触发
     * @param int $client_id 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($client_id, $data)
    {

        $message = json_decode($data, true);

        $message_type = $message['type'];
        switch ($message_type) {
            case 'init':
                $message = json_decode($data, true);
                $cids = $message['data']['cid'];


                // 绑定到直播间id
                Gateway::joinGroup($client_id, $cids);

                /* $message = [
                                'message_type' => 'logMessage',
                                'data' => [
                                    'username' => '王晋',
                                    'avatar' => '',
                                    'id' => 1,
                                    'type' => 'group',
                                    'content' => 'asdsad',
                                    'timestamp' => time() * 1000,
                                ]
                            ];
                 return Gateway::sendToGroup(1, json_encode($message), $client_id); */


                return;
            case 'chatMessage':

                $message = json_decode($data, true);
                $cid = $message['data']['to']['id'];

                $message = [
                    'message_type' => 'chatMessage',
                    'data' => [
                        'username' => $message['data']['mine']['username'],
                        'avatar' => $message['data']['mine']['avatar'],
                        'id' => $cid,
                        'type' => 'group',
                        'content' => $message['data']['mine']['content'],
                        'timestamp' => time() * 1000,
                    ]


                ];

                //保存聊天信息
                $db1 = Db::instance('db1');  //数据库链接
                $content = $message['data']['content'];   //聊天内容
                $username = $message['data']['username'];   //聊天用户名姓名
                $avatar = $message['data']['avatar'];   //聊天用户名姓名
                $cid = $message['data']['id'];   //聊天频道id
                $chattime = date("Y-m-d H:i:s");   //聊天用户发送消息时间

          
                $db1->query("insert into `clt_chat` (`content`,username,avatar,cid,chattime) value('$content','$username','$avatar',$cid,'$chattime')");

                return Gateway::sendToGroup($cid, json_encode($message), $client_id);

            default:
                return;
            //echo $data;
        }
    }

    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id)
    {
        //维持连接的worker退出才触发
        if(!empty($_SESSION['id'])){
            $db1 = Db::instance('db1');  //数据库链接
            //通知该用户的好友，该用户下线
            $friends = $db1->query("select `user_id` from `nd_cases_friends` where `friend_id` = " . $_SESSION['id']);
            if (!empty($friends)) {
                foreach ($friends as $key => $vo) {
                    $user_client_id = Gateway::getClientIdByUid($vo['user_id']);
                    if (!empty($user_client_id)) {
                        $online_message = [
                            'message_type' => 'logout',
                            'id' => $_SESSION['id'],
                        ];
                        Gateway::sendToClient($user_client_id['0'], json_encode($online_message));
                    }
                }
            }
            //设置用户为退出状态
            $db1->query("update `nd_cases_chatuser` set `status` = 0 where id = " . $_SESSION['id']);
        }
    }
}
