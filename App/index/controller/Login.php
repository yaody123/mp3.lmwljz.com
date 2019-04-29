<?php
// +----------------------------------------------------------------------
// | Created by 老姚
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2999 http://lmwljz.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 林梦网络 <1415336788@qq.com>
// +----------------------------------------------------------------------
// | Date: 2018/5/31
// +----------------------------------------------------------------------

namespace app\index\controller;
use app\common\model\User;
use think\Controller;

class Login extends Controller{

    //普通登录
    public function index(){
        if(request()->isPost()){
            $user_name = input('user_name');
            $user_pwd  = input('user_pwd');

            if(empty($user_name) || empty($user_pwd)){
                $this->error(lang('login_userpwd'));
            }
            $model = new User();
            $data  = $model->Mlogin(['user_name'=>$user_name]);
            if($data){
                if(!is_pwd($user_pwd,$data['password'])){
                    $this->error(lang('login_pwderror'));
                }
                if(!$data['status']){
                    $this->error(lang('login_status'));
                }
                session('user_id',$data['id']);
                unset($data['password']);
                $data['ip']      = $loginip = request()->ip();
                $data['logtime'] = $logtime = time();

                cache('user',$data,3600);
                $model->Mupdate(['id'=>$data['id']],['ip'=>$loginip,'logtime'=>$logtime]);
                $this->success(lang('login_success'),url('Index/index'));
                die;
            }
            $this->error(lang('login_nouser'));
        }
        if(session('user_id')){
            $url  = url('Index/index');
            $html = "<script>window.top.location.href='{$url}'</script>";
            echo $html;
            exit;
        }

        return $this->fetch();
    }

    //注册
    public function reguser(){
        $this->error('暂未开放注册','/index1.php');
        if(request()->isPost()){
            die;
        }

        $this->assign('title','注册会员');
        return $this->fetch();

    }

    //QQ登录
//    ret	返回码
//    msg	如果ret<0，会有相应的错误信息提示，返回数据全部用UTF-8编码。
//    nickname	用户在QQ空间的昵称。
//    figureurl	大小为30×30像素的QQ空间头像URL。
//    figureurl_1	大小为50×50像素的QQ空间头像URL。
//    figureurl_2	大小为100×100像素的QQ空间头像URL。
//    figureurl_qq_1	大小为40×40像素的QQ头像URL。
//    figureurl_qq_2	大小为100×100像素的QQ头像URL。需要注意，不是所有的用户都拥有QQ的100x100的头像，但40x40像素则是一定会有。
//    gender	性别。 如果获取不到则默认返回"男"
//    is_yellow_vip	标识用户是否为黄钻用户（0：不是；1：是）。
//    vip	标识用户是否为黄钻用户（0：不是；1：是）
//    yellow_vip_level	黄钻等级
//    level	黄钻等级
//    is_yellow_year_vip	标识是否为年费黄钻用户（0：不是； 1：是）
    public function qqlogin(){
        $token = input('get.');
        if(!$token['access_token']){
            $this->redirect(url('Index/Login/index'));
        }
        $url = "https://graph.qq.com/oauth2.0/me?access_token={$token['access_token']}";
        $getdata = http_curl($url);
        if(strpos($getdata, "callback") !==false){ //callback( {"client_id":"YOUR_APPID","openid":"YOUR_OPENID"} );
            $getdata = QQcalldata($getdata);
            if($getdata['openid']){
                $appid = config('qq_appid');
                $url = "https://graph.qq.com/user/get_user_info?access_token={$token['access_token']}&oauth_consumer_key={$appid}&openid={$getdata['openid']}";// 获取用户信息
                $qqdata= http_curl($url);

                //登录成功
                if($qqdata['ret'] == '0'){
                    $data = array(
                        'qqopenid'=> $getdata['openid'],
                        'nickname'=> $qqdata['nickname'],
                        'avatar'  => $qqdata['figureurl_qq_2']?:$qqdata['figureurl_qq_1'],
                        'sex'     => ($qqdata['gender']=='男')? 1:0,
                        'logip'   => request()->ip(),
                        'logtime' => time(),
                    );
                    $updata= false;
                    $model = new User();
                    $res   = $model->GetUser(['qqopenid'=>$data['qqopenid']],['id']);
                    if($res){
                        $data['id'] = $res['id'];
                        $updata = $model->Mupdate(['qqopenid'=>$data['qqopenid']],$data);
                    }else{
                        $data['addtime'] = $data['logtime'];
                        $updata = $model->Madd($data,1);

                    }
                    if($updata){
                        $data['id'] = $updata;
                        session('user_id',$data['id']);
                        //cache('user',$data,3600);
                    }
                    $this->redirect(url('Index/index'));
                }else{
                    $this->error($qqdata['msg']);
                }
            }else{
                $this->error('数据异常，请重试');
            }
        }else{
            $this->error('发生网络未知错误');
        }
    }

    //退出登陆
    public function logout(){
        session(null);
        cache('user',null);
        $this->redirect(url('Index/index'));
    }

    //微信登录
    public function wxlogin(){


    }



}