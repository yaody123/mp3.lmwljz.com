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
// | Date: 2018/6/13
// +----------------------------------------------------------------------

namespace app\index\controller;
use app\index\model\Music;
class Musicinfo extends  Base {

    public function index(){
        //p($_SERVER['HTTP_USER_AGENT']);die;
        $id  = input('id');
        $key = input('key');
        if(!$id || !$key){
            return json(['code'=>0,'msg'=>'Resource expiration or permanent transfer']);
            //$this->error('Resource expiration or permanent transfer','/');//资源过期或永久转移
        }
        $empkey = explode('.',$key);
        $key    = $empkey['0'];
        $model  = new Music();
        $appkey = config('app_musickey');
        $token  = $model->AccessToken(['id'=>$id,'app_musickey'=>$appkey]);
        if($token != $key){
            return json(['code'=>0,'msg'=>'invalid key']);
        }

        /*if(request()->isMobile()){
            $list = $model->MobileAudioInfo(true);
        }else{
            $list = $model->AudioInfo(true);
        }*/

        $list = $model->MobileAudioInfo(true);
        if(!$list){
            return json(['code'=>0,'msg'=>'网络出现错误']);
        }
        unset($list['lyrics']);
        //$content = file_get_contents('.'.$list['mp3']);
        $url = $list['mp3'];
        if(preg_match('/^http.*/',$list['mp3'])){
            $Path = $list['mp3'];
            $fsize= Getfilesize($list['mp3']);
        }else{
            $Path = '.'.$list['mp3'];
            if(!is_file($Path)){
                $error = self::playerror();
                $Path  = '.'.$error['mp3'];
                //$this->error('暂无法提供试听','',self::playerror());
            }
            _weixin($url);
            $fsize= filesize($Path);
        }

        $pathinfo= pathinfo($Path);
        $ext     = strtolower($pathinfo["extension"]);

        switch ($ext) {
            case "mp3":
                $ctype = "audio/mpeg";
                break;
            case "m4a":
                $ctype = "audio/x-m4a";
                break;
            default:
                $ctype = "application/force-download";
        }

        ob_start();
        header("Pragma: public"); // required
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        //header("Cache-Control: private", false); // required for certain browsers
        header("Content-Type: $ctype");
        //header("Content-Disposition: attachment; filename=\"" . $list['audio_name'] . '.' . $ext . "\";");
        header("Content-Transfer-Encoding: binary");

        header("Content-Length: ".$fsize);
        header("Accept-Ranges: bytes");
        header("Host: www.lmwljz.com");
        ob_clean();
        readfile($Path);
        //echo file_get_contents($Path);
        flush();

        /*$fsize = filesize($Path);
        $path_parts = pathinfo($Path);
        $ext = strtolower($path_parts["extension"]);
        switch ($ext) {
            case "pdf":
                $ctype = "application/pdf";
                break;
            case "exe":
                $ctype = "application/octet-stream";
                break;
            case "zip":
                $ctype = "application/zip";
                break;
            case "doc":
                $ctype = "application/msword";
                break;
            case "xls":
                $ctype = "application/vnd.ms-excel";
                break;
            case "ppt":
                $ctype = "application/vnd.ms-powerpoint";
                break;
            case "gif":
                $ctype = "image/gif";
                break;
            case "png":
                $ctype = "image/png";
                break;
            case "jpeg":
            case "jpg":
                $ctype = "image/jpg";
                break;
            case "mp3":
                $ctype = "audio/mpeg";
                break;
            default:
                $ctype = "application/force-download";
        }
        $suffix = pathinfo($Path, PATHINFO_EXTENSION);
        header("Pragma: public"); // required
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false); // required for certain browsers
        header("Content-Type: $ctype");
        header("Content-Disposition: attachment; filename=\"" . $list['title'] . '.' . $suffix . "\";");
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".$fsize);
        ob_clean();
        flush();
        readfile($Path);

        //return file_get_contents($Path);*/
    }
    //End
}