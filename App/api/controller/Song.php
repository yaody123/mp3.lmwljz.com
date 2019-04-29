<?php
// +----------------------------------------------------------------------
// | 音乐基本信息API   Created by 老姚
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2999 http://lmwljz.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 林梦网络 <1415336788@qq.com>
// +----------------------------------------------------------------------
// | Date: 2018/12/28
// +----------------------------------------------------------------------

namespace app\api\controller;
class Song extends Base {

    //获取音乐基本信息
    public function index(){
        if(!request()->isPost()){
            //$this->error('Use the post request! 更多帮助请查阅API文档','http://api.lmwljz.com');
        }
        $cation = $this->SongType[$this->param['type']];
        $data   = self::$cation();
        if(!$data){
            ApiReturnData('参数错误！QQ:1415336788,[www.lmwljz.com]',0);
        }
        unset($data['img']);
        unset($data['pic']);
        unset($data['lyrics']);
        ApiReturnData($data,1,'http://api.lmwljz.com');
    }
}