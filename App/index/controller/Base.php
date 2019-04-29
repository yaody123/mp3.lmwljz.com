<?php
// +----------------------------------------------------------------------
// | 公共控制器
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2999 http://lmwljz.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 林梦网络 <1415336788@qq.com>
// +----------------------------------------------------------------------
namespace app\index\controller;
use app\common\model\User;
use think\Controller;
use think\Request;
use think\Db;
class Base extends Controller{

    public $user_info = '';
    public $uid       = '';
    public $isMobile  = false;
    public $param     = array('search'=>array('type'=>'qq'));
    public $ApiToken;

    public  function __construct(Request $request){

        //手机模版主题
        if($request->isMobile()){
            config('template.view_path',APP_DIR.'template/mobile/');
            $this->isMobile = true;
        }

        if(!config('web_status')){
            $this->redirect('Error/notice');
        }
        parent::__construct($request);

        $user_info = '';
        $uid       = session('user_id');
        if($uid){
          $user_info = self::login_user_info();
          $this->user_info = $user_info;

		  //重定向
		  $url = session('url');
		  if($url){
			session('url',null);
			$this->redirect($url);
		  }
        }else{
            $uid = cookie('playlist');
            if(!$uid){
                $uid = 'uid'.rand(0000,9999);
                cookie('playlist',$uid,2592000);
            }
        }
        $param = array();
        $this->uid      = $uid;
        $this->ApiToken = config('musictoken');
        $this->assign('user_info',$user_info);
        $this->assign('action',request()->action());
        $this->assign('model',request()->controller());
        $this->assign('param',$param);
        $this->assign('apikey',$this->ApiToken);
        $this->assign('token',self::HtmlToken());
    }

    //token
    public function  HtmlToken($type=false){
        $key   = config('app_musickey');
        $token = md5($key.$this->uid);
        if($type==false){
            if(!session('lmtoken')){
                session('lmtoken',lm_base64_encode($token));
            }
        }
        return $token;
    }

    //验证是否合法请求
    public function Token_Header(){
        $data   = ['code'=>0,'data'=>[],'msg'=>'Not a valid request'];
        $header = request()->header();
        if($header['lmtoken']){
            $token = self::HtmlToken(true);
            if($header['lmtoken'] == $token){
                $data['code'] = 1;
                $data['msg']  = 'success';
            }else{
                $data['msg'] = 'This key is wrong';
            }
        }
        return $data;
    }

    //用户信息
    public function userinfo($where,$field=[]){
        $model = new User();
        $user  = $model->GetUser($where,$field);
        return $user;
    }

    //登陆信息
    public function login_user_info(){
        $user = cache('user');
        if($user==false ){
            $user_id = session('user_id');
            if($user_id){
                $user = self::userinfo(['id'=>$user_id],true);
                if($user){
                    cache('user',$user,3600);
                }
            }
        }
        return $user;
    }

    //播放错误
    public function playerror($str='非常抱歉!您播放的音乐正在赶来的路上,请稍候.',$per=4){
        return OnPlayError($str,$per);
    }

    //空方法
    public function _empty($name){
        $this->redirect('Error/index',['code'=>404],404);
    }

}