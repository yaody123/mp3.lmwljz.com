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
// | Date: 2018/12/28
// +----------------------------------------------------------------------

namespace app\api\controller;
use think\Controller;
use think\Db;
class Base extends Controller {
    //渠道类别
    public $SongType = array(
        'qq'    => 'LmwlQq',    //QQ音乐
        'kugou' => 'LmwlKugou', //酷狗
        'baidu' => 'LmwlBaidu'  //百度
    );

    //资源类别
    public $ActionType = array(
        'lyric'=> '歌词',
        'pic'  => '专辑图片',
        'img'  => '歌手图片',
        'url'  => '播放地址'
    );

    public $referer = array('mp3.app.com','mp3.lmwljz.com','y.lmwljz.com','api.app.com','api.lmwljz.com');

    /**
     * 操作资源句柄
     * @var array
     * @type      类型  qq kugou  baidu
     * @songmid   歌曲ID
     * @action    获取资源类型  url 播放地址  img 封面 lyric 歌词
     */
    public $param = array();
    public $ApiMusic;
    public $lmwlata;

    public  function _initialize(){
        clientlog();
        liuliangtongji();
        $param = input('info');
        if(!$param){
            ApiReturnData('非法请求！QQ:1415336788,[www.lmwljz.com]',0);
        }

        $httpheader = request()->header();
        if(isset($httpheader['referer'])){
            $parse = parse_url($httpheader['referer']);
            if(empty($parse['host']) || !in_array($parse['host'],$this->referer)){
                ApiReturnData('请联系管理员授权，QQ:1415336788,[www.lmwljz.com]',0);
            }
        }

        $gets   = $param;
        $param  = explode(',',$gets);
		if(count($param) <= 1){
			$param  = explode("'",$gets);
		}
        $type   = $param['0'];                   //类型  QQ kugou  baidu
        $songmid= $param['1'];                   //歌曲ID
        $action = $param['2']?$param['2']:'url'; //获取资源类型  url 播放地址  img 封面 lyric 歌词
        $rate   = $param['3']?$param['3']:'96';  //音质 96  128 320 1600
        if(empty($songmid) || empty($type)){
            ApiReturnData('参数错误！QQ:1415336788,[www.lmwljz.com]',0);
        }

        if (!in_array($type, array_keys($this->SongType), true)) {
            ApiReturnData('(°ー°〃) 目前还不支持这个网站！QQ:1415336788,[www.lmwljz.com]',0);
        }

        if (!in_array($action, array_keys($this->ActionType), true)) {
            ApiReturnData('操作不支持！QQ:1415336788,[www.lmwljz.com]',0);
        }

        $mid_len = mb_strlen($songmid);
        if(!in_array($mid_len,[14,32,9,8])){
            ApiReturnData('无效的 songmid！QQ:1415336788,[www.lmwljz.com]',0);
        }

        $this->param = array(
            'type'   => $type,
            'songmid'=> $songmid,
            'action' => $action,
            'rate'   => $rate,
        );

        $token = ApiAccessToken();
        if(!$token['code']){
            ApiReturnData($token['msg'],0);
        }
        //$this->ApiMusic = new  \app\index\model\Music();
    }

    //QQ音乐
    public function LmwlQq(){
        $model = new \app\index\model\Music();
        $data  = $model->QqSongInfo($this->param['songmid'],$this->param['rate']);
        if(empty($data)){
            return $this->playerror('网络连接错误，请稍后再试');
            //$this->error('网络连接错误，请稍后再试');
        }
        $arr = array('pic'=>$data['albumimg'],'img'=>$data['songimg']);
        switch ($this->param['action']){
            case 'url':
            case 'lyric':
                $arr['lyric'] = $model->MusicLrc($this->param['songmid']);
                break;
            case 'pic':
            case 'img':
        }
        $this->lmwlata = array_merge($data,$arr);
        return $this->lmwlata;
    }

    //酷狗音乐
    public function LmwlKugou(){
        $model = new \app\index\model\Music();
        $data  = $model->KugouPlayUrl($this->param['songmid'],'mobile');
        if(!$data){
            return $this->playerror('网络连接错误，请稍后再试');
            //$this->error('网络繁忙，请稍候');
        }
        $arr = array('pic'=>$data['songimg'],'img'=>(isset($data['albumimg']) && $data['albumimg'])?$data['albumimg']:$data['songimg'],'lyric'=>$data['lyrics']);
        if(!isset($data['url']) || empty($data['url'])){
            $playerror   = $this->playerror();
            $data['url'] = $playerror['url'];
        }
        $this->lmwlata = array_merge($data,$arr);
        return $this->lmwlata;
    }

    //百度音乐
    public function LmwlBaidu(){
        $model = new \app\index\model\Music();
        $data  = $model->BaiduPlayUrl($this->param['songmid']);
        if(!$data){
            return $this->playerror('网络连接错误，请稍后再试');
        }
        $arr   = array('pic'=>$data['songimg'],'img'=>$data['songimg'],'lyric'=>$data['lyrics']);
        $this->lmwlata = array_merge($data,$arr);
        return $this->lmwlata;
    }

    //歌词
    public function LmwlLyric($songmid,$type='qq'){
        $model = new \app\index\model\Music();
        $data  = $model->MusicLrc($songmid,$type);
        return $data;
    }

    //获取IK123试听地址
    public function MusicIk123($url){
        //$url  = "http://www.ik123.com/mp3-dj/ik123_{$djmp3['ik_id']}.html";
        if(!$url){
            $this->error('url：为必填项');
        }
        //$pattern = '\'/(http:\/\/)|(https:\/\/)/i';
        $pattern = '/[(http:\/\/www)|(https:\/\/www)]*\.(html)$/i';
        $url     = urldecode(trims($url));
        if(!preg_match($pattern, $url)){
            ApiReturnData('url地址不正确！',0);
        }
        $data = array();
        $list = http_curl($url);
        if($list){
            $pattern = '/furl=(.*?)[.]flv"/si';
            $mp4name = preg_match_all($pattern,$list,$htmls);
            if($mp4name){
                $mp4name = str_replace('furl="',"",$htmls['0']['0']);
                $mp4name = str_replace('.flv"',"",$mp4name);
            }

            //获取标题
            $gettitle = preg_match_all('/<title>(.*)<\/title>/i',$list,$title);
            $title = isset($title['1']['0'])?iconv('GBK','UTF-8',$title['1']['0']):'VIP 免费获取试听资源 - 懂我音乐';
            $title = explode(",",$title);
            $vsid = $this->ik123_vsid();
            if($vsid){
                $domain = 'http://mp4.ik123.com/Dj_www.ik123.com/2010/';//播放地址域名+路径
                $vsid   = str_replace('varVW_VSID="','',trims($vsid));
                $vsid   = str_replace('";','',trims($vsid));
                $data['url']= $domain.$mp4name.".mp4?vsid={$vsid}&name=www.ik123.com";
                $data['img']= config('view_replace_str.__PUBLIC__').'/images/play/logo.png';
                $data['authors']= '懂我音乐';
                $data['title']= $title['0'];
                $this->success('ok','',$data);
            }
        }else{
            ApiReturnData('请求失败或源地址已失效',0);
        }
    }

    /**
     * 系统动态配置
     * @param bool $name 配置名称
     * @return mixed
     */
    public function sysconfig($name=false){
        $data = cache('sysconfig');
        if(!$data){
            $list = Db::name('sysconfig')->field(['name','value'])->where(['status'=>1])->order('id asc')->select();
            if($list){
                foreach($list as $v){
                    $data[$v['name']]= $v['value'];
                }
                cache('sysconfig',$data,86400);
            }
        }
        if($name){
            if(array_key_exists($name,$data)){
                $data = $data[$name];
            }else{
                $data = false;
            }
        }
        return $data;
    }

    //第三方播放key
    public function ik123_vsid(){
        //cache('sysconfig',null);
        return $this->sysconfig('vsid');
        $vsid = http_curl('http://fv.ik123.com/_sys_vw.vhtml?js=yes','',['Referer:http://www.ik123.com']);
        if($vsid){
            $vsid = str_replace('varVW_VSID="','',trims($vsid));
            $vsid = str_replace('";','',trims($vsid));
        }else{
            $vsid = false;
        }
        return $vsid;
    }

    //播放错误
    public function playerror($str='非常抱歉!您播放的音乐正在赶来的路上,请稍候.',$per=4){
        $name  = input('name');
        $domain= request()->domain();
        $mp3   = $domain.baidu_audio_tts($str,$per);
        $img   = $domain.'/static/index/images/play/logo.png';
        $data  = ['url'=>$mp3,'title'=>$str,'pic'=>$img,'img'=>$img,'song_name'=>$name?:'音乐正在赶来的路上','author'=>'懂我音乐','lyric'=>'[00:00.95] 懂我音乐'];
        $this->lmwlata = $data;
        return $data;
    }

    //文件内容输出
    public function smartReadFile($url, $filename='test.mp3'){
        if(preg_match('/^http.*/',$url)){
            $Path = $url;
            $fsize= Getfilesize($url);
        }else{
            $Path = '.'.$url;
            if(!is_file($Path)){
                $error = self::playerror();
                $Path  = '.'.$error['mp3'];
                ApiReturnData('暂无法提供试听','0');
            }
            $fsize= filesize($Path);
        }
        $pathinfo= pathinfo($Path);
        $ext     = strtolower($pathinfo["extension"]);
        $ext     = explode('?',$ext);
        $ext     = $ext['0'];
        switch ($ext) {
            case "mp3":$ctype = "audio/mpeg";break;
            case "m4a":$ctype = "audio/x-m4a";break;
            case "gif":$ctype = "image/gif";break;
            case "png":$ctype = "image/png";break;
            case "jpeg":
            case "jpg":$ctype = "image/jpg";break;
            default: $ctype = "application/force-download";//$ctype = "audio/mpeg";
        }

        ob_start();
        header("Pragma: public"); // required
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        //header("Cache-Control: private", false); // required for certain browsers
        header("Content-Type: $ctype");
        //header("Content-Disposition: attachment; filename=\"" . $filename. '.' . $ext . "\";");//下载
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".$fsize);
        header("Accept-Ranges: bytes");
        header("Host: api.lmwljz.com");
        ob_clean();
        //readfile($Path);
        echo file_get_contents($Path);
        flush();
    }

    //输出图片
    public function showImg($img){
        $info = getimagesize($img);
        $imgExt = image_type_to_extension($info[2], false); //获取文件后缀
        $fun = "imagecreatefrom{$imgExt}";
        $imgInfo = $fun($img);
        $mime = $info['mime'];
        //$mime = image_type_to_mime_type(exif_imagetype($img)); //获取图片的 MIME 类型
        header('Content-Type:'.$mime);
        $quality = 100;
        if($imgExt == 'png') $quality = 9;   //输出质量,JPEG格式(0-100),PNG格式(0-9)
        $getImgInfo = "image{$imgExt}";
        $getImgInfo($imgInfo, null, $quality); //将图像输出到浏览器或文件。如: imagepng ( resource $image )
        imagedestroy($imgInfo);
        exit();
    }
    //End
}