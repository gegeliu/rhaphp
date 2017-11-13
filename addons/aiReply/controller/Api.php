<?php
/**
 * Created by PhpStorm.
 * User: lazen
 * Date: 2017/11/8
 * Time: 12:40
 */
namespace addons\aiReply\controller;


use addons\aiReply\model\Aipack;
use think\Db;

class Api
{
    public static $sys_keys = array(
            '你好'=>1,'最近'=>1, '阅读'=>1,'投票'=>1,'刚才'=>1,
            '充值'=>2,
            '免费'=>3,
            '帮助'=>4,
            );

    public function getKeyType( $txt )
    {
        $sys = self::$sys_keys;
        if( strlen($txt) < 2 ) {
            return 0;
        }
        foreach( $sys as $k => $v ) {
            if( stripos($txt,$k) !== false )
                return $v;
        }
        return 0;
    }

    public function message($msg = [], $param = [])
    {
        $info = getAddonInfo();
        //json_encode($msg);
        //  replyText(json_encode($msg));
        /*
       {
         "openid":" OPENID",
         "nickname": NICKNAME,
         "sex":"1",
         "province":"PROVINCE"
         "city":"CITY",
         "country":"COUNTRY",
         "headimgurl":    "",
         "privilege":["PRIVILEGE1" "PRIVILEGE2"],
         "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
        }
        */
        $usrinfo = getFriendInfoForApi();
        $unionid = isset($usrinfo["unionid"]) ? $usrinfo["unionid"] : "";

        $domain = $info['mp_config']['domain'];
        $db = Db::connect($info['mp_config']['dsn']);
        if (!$db) {
            replyText('恍惚中，请稍后再试...');
        }

        $nick = $usrinfo['nickname'];
        if (!empty($unionid)) {
            //查询 并更新用户昵称
            $res = $db->table('user')->where('weixinopenid', $unionid)->count();
            if ($res) {

                $avatar = $usrinfo['headimgurl'];
                $db->table('user')->where('weixinopenid', $unionid)->update(['nickname' => $nick, 'avatar' => $avatar]);
            }
        }
        //
        if ('text' !== $msg['MsgType']) {
            replyText('亲，暂不支持此类型消息哦...');
            return;
        }
        $books = array();
        $keyword = $msg['Content'];
        if (is_numeric($keyword)) {
            $res = $db->table('novel')->where('idnovel', $keyword)->column('idnovel,name,cover_image');
        }
        else {
            $t = $this->getKeyType($keyword);
            if( 1 === $t ){ //最近阅读
                $info = array (
                    'Title' => $keyword.'?',
                    'Description' => '点我直接进入最近阅读',
                    'PicUrl' => '',
                    'Url' => $domain.'/continueread'
                );
                array_push ( $books, $info );
            }
            elseif( 2 === $t ){ // 充值
                $info = array ('Title' => $keyword.'?',
                    'Description' => '点我直接充值，感谢您的支持，我们一直在努力！',
                    'PicUrl' => '',
                    'Url' => $domain.'/charge'
                );
                array_push ( $books, $info );
            }
            if( 0 !== $t ) {
                $map['idnovel'] = array('in','237,116,181,292,295');
                $map['vip'] = array('eq','0');
                $res = $db->table('novel')->where($map)->field('idnovel,name,cover_image')
                    ->order('idnovel desc')->limit(5)->select();
            }
            else {
                $map['summary|name'] = array('like','%'.$keyword.'%');
                $map['vip'] = array('eq','0');
                $res = $db->table('novel')->where($map)
                    ->order('idnovel desc')->limit(5)->column('idnovel,name,cover_image');
            }
        }
        if( $res ) {
            foreach ($res as $book) {
                $info = array(
                    'Title' => $book['name'],
                    'Description' => $book['name'],
                    'PicUrl' => $domain . str_replace("../..", "", $book['cover_image']),
                    'Url' => $domain . '/bookshow/' . $book['idnovel'],
                );
                array_push($books, $info);
            }
        }
        if(count($books)>0)
            replyNews($books);
        else
            replyText('您好，' . $nick . '欢迎您的到来');
            //replyText('亲，没有查询到此书，为您推荐以下内容：');
    }
    //
}