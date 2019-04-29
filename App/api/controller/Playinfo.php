<?php
// +----------------------------------------------------------------------
// | 音乐播放 API   Created by 老姚
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2999 http://lmwljz.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 林梦网络 <1415336788@qq.com>
// +----------------------------------------------------------------------
// | Date: 2018/12/14
// +----------------------------------------------------------------------

namespace app\api\controller;
use think\File;

class Playinfo extends Base {

    public function index(){
        $cation = $this->SongType[$this->param['type']];
        $data   = self::$cation();
        $field  = $this->param['action'];
        if(!$field){
            ApiReturnData('参数错误！QQ:1415336788,[api.lmwljz.com]',0);
        }

        if($field == 'lyric'){
           if(!isset($data[$field])){
               $data[$field] = $this->LmwlLyric($this->param['songmid'],$this->param['type']);
           }
        }

        if(isset($data[$field]) && $data[$field]){
            $regex = '/^(http|https):\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\’:+!]*([^<>\”])*$/';
            if(preg_match($regex,$data[$field])){
                $fileinfo = pathinfo($data[$field]);
                if(isset($fileinfo['extension']) && $fileinfo['extension']=='lrc'){
                    echo file_get_contents($data[$field])?:'[00:00.01] 暂无歌词，'.config('webname').'！';
                }else{
                    $this->redirect($data[$field]);
                    /*if($field == 'url'){
                        $this->smartReadFile($data[$field]);
                    }else{
                        $this->redirect($data[$field]);
                    }*/
                }
            } else {
                exit($data[$field]);
            }
        }else{
            ApiReturnData('暂不支持本次请求！QQ:1415336788,[api.lmwljz.com]',0);
        }
    }

    //End
}