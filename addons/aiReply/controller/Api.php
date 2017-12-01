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
            '验证码'=>5,
            'code'=>5,
            );

    public function getKeyType( $txt )
    {
        $sys = self::$sys_keys;
        if( strlen($txt) < 2 || strlen($txt) > 15 ) {
            if( '?' === $txt )
                return 4;
            return 0;
        }
        foreach( $sys as $k => $v ) {
            if( stripos($txt,$k) !== false )
                return $v;
        }
        return 0;
    }

    public function genVerifyCode($len)
    {
        $chars_array = array(
            '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k',
            'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v',
            'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G',
            'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R',
            'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        );
        $charsLen = count($chars_array) - 1;

        $outputstr = '';
        for( $i=0; $i<$len; $i++ )
        {
            $outputstr .= $chars_array[mt_rand(0, $charsLen)];
        }

        return $outputstr;
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
        $sex = $usrinfo['sex'];
        $avatar = $usrinfo['headimgurl'];
        if (!empty($unionid)) {
            //查询 并更新用户昵称
            $res = $db->table('user')->where('weixinopenid', $unionid)->field('iduser,nickname,avatar')->find();
            if ($res) {
                $iduser = $res['iduser'];
                if( $res['nickname'] !== $nick || $res['avatar'] !== $avatar ) {
                    $db->table('user')->where('iduser', $iduser)->update(['nickname' => $nick, 'avatar' => $avatar, 'gender' => $sex]);
                }
            }
            // 生成新用户
            else {
                $data = ['nickname'=>$nick,
                        'gender'=>$sex,
                        'weixinopenid'=>$unionid,
                        'avatar'=>$avatar
                        ];
                $db->table('user')->insert($data);
            }
        }
        //
        if( 'event' === $msg['MsgType'] ){
            $keyword = $msg['EventKey'];
        }
        else if ('text' === $msg['MsgType']) {
            $keyword = $msg['Content'];
        }
        else {
            replyText('亲，暂不支持此类型消息哦...');
            return;
        }
        $books = array();
        if (is_numeric($keyword)) {
            $res = $db->table('novel')->where('idnovel', $keyword)->column('idnovel,name,cover_image');
        }
        else {
            $t = $this->getKeyType($keyword);
            if( 5 === $t ){
                $res = $db->table('user')->where('weixinopenid', $unionid)->field('iduser,name')->find();
                if ($res && $res['iduser']){
                    $iduser = $res['iduser'];
                    $rand = $this->genVerifyCode(6);
                    $code = substr(md5($iduser.$rand),8,16);
                    $send_time = date('Y-m-d H:i:s', time());
                    $data =['mobile_verify_state'=>3,
                            'mobile_verify_code'=>$code,
                            'mobile_verify_code_send_time'=>$send_time
                            ];
                    $db->table('user')->where('iduser',$iduser)->update($data);
                    replyText($code);
                }
                else {
                    replyText('验证码获取失败，请稍后再试。真是见了鬼了。');
                }
                return;
            }
            if( 1 === $t ){ //最近阅读
                $info = array (
                    'Title' => $keyword.'?',
                    'Description' => '点我直接进入最近阅读',
                    'PicUrl' => '',
                    'Url' => $domain.'/continueread'
                );
                array_push ( $books, $info );
            }
            else if( 2 === $t ){ // 充值
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