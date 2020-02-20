<?php
namespace Config;
/**
 * mysql配置
 * @author walkor
 */
class Db
{
    /**
     * 数据库的一个实例配置，则使用时像下面这样使用
     * $user_array = Db::instance('db1')->select('name,age')->from('users')->where('age>12')->query();
     * 等价于
     * $user_array = Db::instance('db1')->query('SELECT `name`,`age` FROM `users` WHERE `age`>12');
     * @var array
     */
    public static $db1 = array(
        'host'    => 'rm-bp19d3e41secoh6wa.mysql.rds.aliyuncs.com',
        'port'    => 3306,
        'user'    => 'zbcms',
        'password' => 'Cisco123!@#',
        'dbname'  => 'zbcms',
        'charset'    => 'utf8'
    );
}
