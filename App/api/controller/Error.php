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
// | Date: 2018/6/6
// +----------------------------------------------------------------------

namespace app\api\controller;
use think\Controller;
class Error extends Controller {

    //空控制器
    public function index(){
        $msg   = config('appset.msg');
        $code  = input('code','0','intval');
        $id    = input('id','0','intval');
        $title = $msg[$id];
        if(!$title){
            $title = '非法请求';
        }
        if(!$code){
            $this->error($title.'，详情关注公众号','/','',5);
        }
        return $this->fetch($code);
        //$setk =  cookie('testcookie','123123');//setcookie( "TestCookie",  "okol1111",  time() + 3600,  "/", "mp3.app.com", 1 );
    }

    //空方法
    public function _empty($name){
        //$this->request->header('referer');
        //return '操作'.$name . '不存在';
        return $this->fetch('../template/404.html');
    }

    //文件下载错误
    public function download(){
        $this->error('歌曲资源正在路上，请稍后！详情关注公众号','/','',5);
    }

    //播放器错误
    public function play(){
        $this->error('暂停提供试听服务，详情关注公众号','/','',5);
    }

    public function tongji(){
       $data =  liuliangtongji();
       echo "document.write('{$data}')";
    }

    //End
}