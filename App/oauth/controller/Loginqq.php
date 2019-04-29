<?php
// +----------------------------------------------------------------------
// | QQ登录
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2999 http://lmwljz.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 林梦网络 <1415336788@qq.com>
// +----------------------------------------------------------------------

namespace app\oauth\controller;
use think\Controller;
//use think\Db;
class Loginqq extends  Controller {

    public function index(){
        $token= [];
        $code = input('code');
        //$ip = empty($_SERVER ["HTTP_X_FORWARDED_FOR"]) ? $_SERVER ["REMOTE_ADDR"] : $_SERVER ["HTTP_X_FORWARDED_FOR"];
        if($code){
            $qqconfig = ['appid'=>config('qq_appid'),'appkey'=>config('qq_appkey'),'redirect_uri'=>config('qq_redirect_uri')];
            //cache('qqlogin',request()->param());
            $url  = "https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&client_id={$qqconfig['appid']}&client_secret={$qqconfig['appkey']}&code={$code}&state=state&redirect_uri={$qqconfig['redirect_uri']}";
            $data = http_curl($url);
            //if(strpos($data, "callback") !== false){
            if($data){
                //授权成功
                $param = explode("&",$data);
                if(is_array($param)){
                    foreach ($param as $v){
                        $val = explode("=",$v);
                        $token[$val['0']] = $val['1'];
                        unset($val);
                    }
                }

                //获取openid
                if($token['access_token']){
					$this->redirect(url('Index/Login/qqlogin')."?access_token={$token['access_token']}&refresh_token={$token['refresh_token']}&expires_in={$token['expires_in']}");

                    /*if($ip == "220.168.16.206"){
                        $this->redirect(url('Index/Login/qqlogin')."?access_token={$token['access_token']}&refresh_token={$token['refresh_token']}&expires_in={$token['expires_in']}");
                    }
                    $this->redirect('/index1.php');*/
                }
            }
        }
        /*if($ip != "220.168.16.206"){
            $this->redirect('/index1.php');
        }*/
		$this->error('授权失败');
    }



    //End
}
