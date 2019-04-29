<?php
// +----------------------------------------------------------------------
// | 资源下载
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2999 http://lmwljz.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 林梦网络 <1415336788@qq.com>
// +----------------------------------------------------------------------


namespace app\index\controller;
use app\index\model\Music;
class Download extends Base {
    public function _initialize(){
        if(!session('user_id')){
            $url  = url('Login/index');
            if(isset($_SERVER["HTTP_REFERER"])){
                session('url',$_SERVER["HTTP_REFERER"]);
            }
            //exit("<script>window.top.location.href='{$url}'</script>");
        }
    }

    public function index(){
        $mid   = input('mid');
        $type  = input('type','qq');
        if(request()->isPost()){
            $key   = input('key');
            if(!$mid || !$key){
                $this->redirect('Error/download');
            }

            $lmtoken = session('lmtoken');
            if(lm_base64_decode($lmtoken) != $key){
                ApiReturnData('网络连接错误，请刷新页面',0);
            }

            $model = new Music();
            $music = false;
            switch($type){
                case 'qq':
                    $music = $model->QqSongInfo($mid,128);
                    if($music){
                        $playurl = $model->QqMusicUrlWeixin($mid,true);
                        foreach($playurl as $k=>$v){
                            $headers = get_headers($v,1);
                            if(preg_match('/200/',$headers[0])){
                                $music['url'] = $v;
                                break;
                            }
                            unset($headers);
                        }
                    }
                    break;
                case 'kugou':
                    $music = $model->KugouPlayUrl($mid);
                    break;
                case 'baidu':
                    $music = $model->BaiduPlayUrl($mid);;
                    break;
                case 'ik123':
                    $music = $model->Ik123ToInfo($mid);
					$music['url'] = str_replace('#vsid#',token_ik123(false,true),$music['url']);
                    break;
            }

            if($music && $music['url']){
                $id   = substr(uniqid(), 7, 13);
                $music['author'] = $music['author']?$music['author']:'Mp3.lmwljz.com';
                $data = array('id'=>$id,'name'=>'[懂我音乐] '.$music['title'].' - '.$music['author'],'url'=>$music['url'],'mid'=>$music['songmid'],'type'=>$type);
                
				if(!cache('down:'.$id)){
					cache('down:'.$id,$data,120);
				}

                $info = ['url'=>url('savefile').'?id='.$id];
                ApiReturnData($info);
            }else{
                ApiReturnData('暂不提供下载服务，更多关注[林梦网络]',0);
            }
        }
        if(!$mid){
            $this->redirect('Error/index');
        }
        $token   = self::HtmlToken();
        $this->assign('token',$token);
        return $this->fetch();
    }

    //下载信息页
    public function getinfo(){
        if(request()->isPost()){
            $id = input('download',0,'intval');
            if(!$id){
                $this->redirect('Error/index');
            }
            $model = new Music();
            $list = $model->AudiolistFind(['id'=>$id],['id','hash','title','img']);
            $this->assign('list',$list);
            return $this->fetch();
        }else{
            $this->redirect('Index/index');
        }
    }

    //保存到本地文件
    public function savefile(){
        $id = input('id');
        if(empty($id)){
            $this->redirect('Error/play');
        }

        if(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')){
            $this->error('暂不支持IOS系统请使用电脑下载本歌曲');
        }
        $music = cache('down:'.$id);
        if($music ==  false){
            $this->redirect('Error/download');
        }

        if(preg_match('/^[http|https].*/',$music['url'])){
			
            $Path = $url = $music['url'];
            $fsize= Getfilesize($music['url']);
        }else{
            $Path = '.'.$music['url'];
            if(!is_file($Path))$this->redirect('Error/play');
            $fsize= filesize($Path);
        }
        $pathinfo= pathinfo($Path);
        $ext     = strtolower($pathinfo["extension"]?substr($pathinfo["extension"],0,3):'mp3');
        if(isset($music['type']) && $music['type']=='ik123'){
            $ext = 'mp3';
        }

		$this->downhtml($music['url'],$music['name'].'.'.$ext);die;

        ob_start();
        header('Content-language: en'); //文档语言
        header('Content-Transfer-Encoding: binary');
		header("Content-Type: application/x-www-form-urlencoded");
        //header("Content-Type: application/force-download");
        header("Accept-Ranges: bytes");
        header("Content-Length: ".$fsize);
        header("Host: www.lmwljz.com");
		header("Content-Disposition: attachment; filename={$music['name']}.{$ext}");
        header('Connection: close');
        ob_clean();
        flush();
        readfile($Path);
		//header('HTTP/1.1 404 Not Found');
		die;
    }

    /*******************第二版 下载************************/
	public function downhtml($url,$saveName){
		$html = <<<eof
<script type="text/javascript">
//* 通用的打开下载对话框方法，没有测试过具体兼容性
//* @param url 下载地址，也可以是一个blob对象，必选
//* @param saveName 保存文件名，可选
function openDownloadDialog(url, saveName){
    if(typeof url == 'object' && url instanceof Blob){
        url = URL.createObjectURL(url); // 创建blob地址
    }
    var aLink = document.createElement('a');
    aLink.href = url;
    aLink.download = saveName || ''; // HTML5新增的属性，指定保存文件名，可以不要后缀，注意，file:///模式下不会生效
    var event;
    if(window.MouseEvent){ 
		event = new MouseEvent('click');
    }else{
        event = document.createEvent('MouseEvents');
        event.initMouseEvent('click', true, false, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
    }
    aLink.dispatchEvent(event);
	setTimeout(function(){
		window.close();
	},3000);
}
openDownloadDialog('$url','$saveName');
</script>

eof;
	echo $html;
	}


}