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
// | Date: 2018/7/14
// +----------------------------------------------------------------------

namespace app\index\controller;
use app\index\model\Music;
use org\util\Ffmpeg;
use org\util\Qqckey;
class Play extends Base {
    public $Music;
    public $ApiToken;

    public function _initialize(){
        $this->Music= new Music();
    }

    public function index(){
        $content="【密码找回】这是密码找回邮件，请在2小时内完成 <a href='http://y.lmwljz.com'>http://y.lmwljz.com</a><br></hr> 如打不开此连接请复杂一下连接<br>http://mp3.app.com";
        $email = sendEmail("帐号密码找回", "1415336788@qq.com", $content);
        p($email);
    }

    public function ffmpeg(){
        $from  = '/Data/kaluli101.mkv';
        $output= '/Data/123456.mp3';
        //$data  = Ffmpeg::VideoToAudio($from,$output);
        //$data  = Ffmpeg::ZipAudio('/Data/003.mp3','/Data/003_128.mp3',128);
        $data  = Ffmpeg::ZipVideo($from,'/Data/kaluli_4096x2304_libx264_flv.mp4',2100,'4096x2304','flv');
        p($data,1);

    }

    //首页随机播放器
    public function dwyy(){
        $id    = input('id',8888,'intval');
        //cache('toplist23784',null);
        $list  = $this->Music->TopList($id);
        $media = [];
        if($list){
            shuffle($list);
            $media = $this->Music->MobileAudioInfo(false,$list['0']['h']);
            if($media){
                $media['media'] = ['title'=>$media['audio_name'],'img'=>$media['img'],'m4a'=>$media['mp3'],'poster'=>$media['img']];
            }
        }
        $config = config('kugou_top');
        $title  = '热门榜';
        if(isset($config['0']['list'][$id])){
            $title = $config['0']['list'][$id]['name'];
        }
        $this->assign('list',$list);
        $this->assign('media',$media);
        $this->assign('title',$title);
        $this->assign('token',self::HtmlToken());
        return $this->fetch('../template/pc/play/dwyy.html');
    }

    //歌曲信息
    public function info(){
        //验证请求是否合法
        $token = parent::Token_Header();
        if($token['code']==0){
            return json($token);
        }
        $data  = $this->Music->MobileAudioInfo();
        if($data){
            $info = ['m4a'=>$data['mp3'],'img'=>$data['img'],'song_name'=>$data['audio_name'],'hash'=>$data['hash'],'id'=>$data['id']];
            return json(['code'=> 1,'data'=>$info]);
        }
        $this->error('暂无法提供试听','',self::playerror());
    }

    //首页随机播放列表
    public function lmwljzlist(){
        //cache('toplist24306',null);
        $list = $this->Music->TopList(['cate_id'=>23784]);
        if($list){
            shuffle($list);
            $list = "var playlist = ".json_encode($list).";";
            $list .= "function ajaxurldata(hash){
    var playdata;
    $.ajax({type:'post',timeout:30000,headers:{'lmtoken':access_token},url: '/index/play/info',data:{'id':hash},async:false,
        success:function(data){
            if(data){
                playdata = data.data;
                $('#songName,.singerContent .audioName').html(playdata.song_name); 
                $('.jp-type-playlist .bar-albumImg>a,.singerContent .albumImg>a').find('img').attr('src',playdata.img); 
                $('#jquery_jplayer_1').jPlayer('setMedia',{'title':playdata.audio_name,'m4a':playdata.m4a,'id':playdata.id,'hash':playdata.hash});
                $('input[name=download]').val(playdata.id);
            }else{alert('试听歌曲资源正在路上');}
        },error:function(){alert('貌似网络开小差了');}
    });}

function mp_download(id){
    if(!id){return false;}
    $.ajax({type:'post',timeout: 30000,url: '/index/download',data:{'id':id,'key':".session('lmtoken')."},dataType:'json',
        success:function(data){
            if(data.code==1){window.open(data.data.url,'_self');return false;}else{alert(data.msg);}
        },error:function(){alert('貌似网络开小差了');}
    });
}";
        }
        return $list;
    }

    //第三方歌曲详细信息
    public function playinfo(){
        $key    = input('key');
        $info   = input('info');
        $lodes  = explode(',',$info);
        $type   = $lodes['0'];
        $songmid= $lodes['1'];
        $action = isset($lodes['2'])?$lodes['2']:'url';
		$rate   = isset($lodes['3'])?$lodes['3']:'96';
        $list   = [];
        if($key != $this->ApiToken){
            //exception('异常消息', 404); abort(404,'页面不存在');
            ApiReturnData('无效的密钥',0);
        }
        if(!$songmid){
            ApiReturnData('无效的 songmid！QQ:1415336788,[mp3.lmwljz.com]',0);
        }
        switch($type) {
            case 'qq':
                if($action=='url'){
                    $list['url'] = $this->Music->QqMusicUrlWeixin($songmid,$rate);
                }
                /*if(in_array($action,['url','pic','img'])){
                    $list = $this->Music->QqSongInfo($songmid,96);
                }*/
                if($action=='lyric'){
                    $list['lyric'] = $this->Music->MusicLrc($songmid);
                }
                break;
            case 'baidu':
                if(in_array($action,['url','pic','img'])){
                    $list = $this->Music->BaiduPlayUrl($songmid);
                }
                if($action=='lyric'){
                    $list['lyric'] = $this->Music->MusicLrc($songmid,'baidu');
                }
                break;
            case 'kugou';
                if(in_array($action,['url','pic','img'])){
                    $list = $this->Music->KugouPlayUrl($songmid,'mobile');
                }
                if($action=='lyric'){
                    $list['lyric'] = $this->Music->MusicLrc($songmid,'kugou');
                }
                break;
        }

        if(isset($list[$action]) && $list[$action]){
            $regex = '/^(http|https):\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\’:+!]*([^<>\”])*$/';
            if(preg_match($regex,$list[$action]) || $action=='url'){
                $fileinfo = @pathinfo($list[$action]);
                if(isset($fileinfo['extension']) && $fileinfo['extension']=='lrc'){
                    echo file_get_contents($list[$action])?:'[00:00.01] 暂无歌词，'.config('webname').'！';
                }else{
                    $this->redirect($list[$action]);
                }
            }else{
                exit($list[$action]);
            }
        }else{
            ApiReturnData('暂不支持本次请求！QQ:1415336788,['.url('/','','true',true).']',0);
        }

    }

    //播放页QQ音乐
    public function qq(){
        $songid = input('mid');
        $model  = new Music();
        $list   = $model->QqSongInfo($songid);
        if($list){
            $list['lyrics'] = $model->MusicLrc($songid);
            //$url = '/api/playinfo.html?info=qq,'.$list['songmid'];//http://mp3.app.com/api/playinfo.html?info=qq,003cI52o4daJJL&key=lmwljz
            $url = url('playinfo').'?info=qq,'.$list['songmid'];//http://mp3.app.com/api/playinfo.html?info=qq,003cI52o4daJJL&key=lmwljz
            $playlist = array(
                'title'  => $list['title'],
                'author' => $list['author'],
                'timelength'=>$list['time'],
                'type'   => 'qq',
                'songmid'=> $list['songmid'],
                'm4a'    => $url.'&key='.$this->ApiToken,
            );
            $list['playlist'] = $this->UserPlayList($playlist);
            if(!isset($list['lyrics'])){
                $list['lyrics']   = $model->MusicLrc($songid);
            }

        }
        $this->assign('list',$list);
        $this->assign('token',self::HtmlToken());
        return $this->fetch('open_play');
        //p($list);
    }

    //播放页酷狗音乐
    public function kugou(){
        $songid = input('mid');
        $model  = new Music();
        $list   = $model->KugouPlayUrl($songid,'mobile');
        if($list){
            $url = url('playinfo').'?info=kugou,'.$list['songmid'].'&key='.$this->ApiToken;
            $playlist = array(
                'title' =>$list['title'],
                'author'=>$list['author'],
                'timelength'=>$list['time'],
                'type'  =>'kugou',
                'songmid'=>$list['songmid'],
                'm4a'   => $url,
            );
            $list['playlist'] = $this->UserPlayList($playlist);
            if(!isset($list['lyrics'])){
                $list['lyrics']   = $model->MusicLrc($songid,'kugou');
            }
            if(!isset($list['albumimg'])){
                $list['albumimg'] = $list['songimg'];
            }
        }
        $this->assign('list',$list);
        $this->assign('token',self::HtmlToken());
        return $this->fetch('open_play');
        //p($list);
    }

    //播放页百度音乐
    public function baidu(){
        $songid = input('mid');
        $model  = new Music();
        $list   = $model->BaiduPlayUrl($songid);

        if($list){
            $url = url('playinfo').'?info=baidu,'.$list['songmid'].'&key='.$this->ApiToken;
            $playlist = array(
                'title'=>$list['title'],
                'author'=>$list['author'],
                'timelength'=>$list['time'],
                'type'=>'baidu',
                'songmid'=>$list['songmid'],
                'm4a'=>$url
            );
            $list['playlist'] = $this->UserPlayList($playlist);
            $list['lyrics']   = $list['lyrics']?file_get_contents($list['lyrics']):'[00:00.01] 暂无歌词，'.config('webname').'！';

        }
        if(!isset($list['albumimg'])){
            $list['albumimg'] = $list['songimg'];
        }

        $this->assign('list',$list);
        $this->assign('token',self::HtmlToken());
        return $this->fetch('open_play');
    }

    //腾讯视频播放
    public function video(){
        $isMobile = $this->isMobile;
        $id    = input('id');
        $img   = input('img');
        $type  = input('type','qq');
        cache($type.'mv:'.$id,null);
        $list  = cache($type.'mv:'.$id);
        $model = new Music();
        switch ($type){
            case 'qq':
                if(!$list){
                    $albumm= $model->QqAlbummInfo($id);
                    $mvid  = $albumm['mvid']?:$id;
                    $video['playurl'] = '';
                    $ismp4 = false;
                    if(!$video['playurl'] || !strpos($video['playurl'],'.m3u8?ver')){
                        $GteQqMV = $model->GteQqMV($mvid);
                        if(isset($GteQqMV['fhd'])){
                            $video['playurl'] = $GteQqMV['fhd']['url'];
                        }elseif(isset($GteQqMV['sph'])){
                            $video['playurl'] = $GteQqMV['sph']['url'];
                        }else{
                            $video['playurl'] = $GteQqMV['hd']['url'];
                        }
                        $ismp4 = true;
                    }
                    $list  = array_merge($albumm,$video);
                    $list['mvid']     = $mvid;
                    $list['albumimg'] = $img?:$albumm['albumimg'];
                    if(!isset($list['playurl'])){
                        $list['playurl'] = $video['url2']?$video['url2']:$video['url1'];
                    }
                    cache('qqmv:'.$id,$list,300);
                }
                break;
            case 'kugou':
                if(!$list){
                    $list = $model->KugouMvPlayUrl($id);
                    if($list){
                        foreach ($list['url'] as $v){
                            $v['level'] = (int)$v['level'];
                            $playurl[$v['level']] = $v['url'];
                            $list['playurl']  =$v['url'];
                            $list['albumimg'] = $v['img'];
                        }
                    }
                }
                break;
            case 'baidu':

                break;
        }

        $list['ismobile'] = $isMobile;
        $this->assign('list',$list);
        $this->assign('ismp4',$ismp4);
        return $this->fetch('../template/pc/play/video_'.$type.'.html');
    }

	//用户当前试听的列表记录
    public function UserPlayList($add=false){
		$key  = $this->uid;
		$data = cache($key);
		$index= false;
		if($add){
			$songmid = true;
			if($data){
				foreach($data as $k=>$v){
					if($v['songmid'] == $add['songmid']){
						$songmid = false;
						break;
					}
				}
			}
			if($songmid){
				$data[] = $add;
				$data  = array_reverse(array_merge($data,array()));
				cache($key,$data,config('kugou.maxsave'));
			}
			$column  = array_column($data,'songmid');
            $index   = array_search($add['songmid'],$column);//重复下标
		}
		
		if($data){
			$tmp = $data;
			$key = 'key='.$this->ApiToken;
			foreach($tmp as $k=>$v){
				$lodes = explode('&',$v['m4a']);
				if($lodes['1'] != $key){
					$data[$k]['m4a'] = $lodes['0'].'&'.$key;
				}
			}
		}

		return array('data'=>$data,'index'=>$index);
    }

	//删除播放列表中的歌曲
	public function delplaylist(){
		$mid  = input('mid');
		$list = $this->UserPlayList();
		if($list){
		
		}
	}


    //播放页MV
    public function mv(){
        $vid  = input('vid');
        $type = input('type','qq');
        $list = false;
        if(empty($vid)){
            $this->redirect('/');
        }
        $model= new Music();
        switch($type){
            case 'kugou':
                $list = $model->KugouMvPlayUrl($vid);
                if($list){
                    $list['comment'] = []; //评论
                    $list['other']   = []; //推荐
                }
                break;
            default:
                $list = $model->yqqmv($vid);
                if($list){
                    $video = $model->GteQqMV($vid);
                    if(isset($video['fhd'])){
                        $list['playurl'] = $video['fhd']['url'];
                    }elseif(isset($video['sph'])){
                        $list['playurl'] = $video['sph']['url'];
                    }else{
                        $list['playurl'] = $video['hd']['url'];
                    }
                    $list['comment'] = $model->yqqcomment($vid,$list['video_switch']); //评论
                    if(isset($list['other']) && count($list['other']) >= 5){
                        $list['other'] = array_slice($list['other'],0,5);
                    }
                    $list['url'] = $video;
                }
                //p($list);die;
        }
        if($list == false){
            $this->redirect('/');
        }
        $this->assign('title',$list['title'].' - '.$list['author']);
        $this->assign('list',$list);
        return $this->fetch();
        //p($list);
    }

    public function getinfo(){

		$params = array(
			'charge'=>0,
			'vid'=>'u0029vu1e9l',
			'defaultfmt'=>'auto',
			'otype'=>'json',
			'guid'=>'e868d816de712d494543790e8f1cebd7', //发觉有问题用不了就要换，抓包看
			'platform'=>'plt',
			'defnpayver'=>1,
			'appVer'=>'3.0.83',
			'sdtfrom'=>'std',
			'host'=>'v.qq.com',
			'ehost'=>urlencode('https://v.qq.com/x/cover/u0029vu1e9l.html'),
			'defn'=>'mp4',
			'fhdswitch'=>0,
			'show1080p'=>1,
			'isHLS'=>0,
			'newplatform'=>'v1010',
			'defsrc'=>1,
			'_0'=>'undefined',
			'_1'=>'undefined',
			'_2'=>'undefined',
			'_'=> GetTime(),
			'callback'=>'jsonpCallback', //返回json的前缀	
		);

        $url = 'https://h5vv.video.qq.com/getinfo';
        $res = http_curl($url,$params);
		$data=jsonp_decode($res,true);
		//p($data);
		$ui = $data['vl']['vi'][0]['ul']['ui'];
		p($ui[0]['url']);
		$fielname = $data['vl']['vi'][0]['lnk'];
		p($fielname);
    }
	
	public function getkey(){
		$time = GetTime();
		$params = array(
        'charge'=> 0,
        'vid'=> 'u0029vu1e9l', //视频vid
        'format'=>2,
        'otype'=> 'json',
        'guid'=> 'e868d816de712d494543790e8f1cebd7',
        'platform'=> 10901,
        'defnpayver'=> 0,
        'appVer'=> '3.0.83',
        'vt'=>0,
        'sdtfrom'=>'v1010',
        //'_rnd'=>rmt['t'], //时间戳重要，没有直接20k速度
        //'_qv_rmt'=> rmt['u1'], //限速算法，重要，没有直接20k速度
        //'_qv_rmt2'=> rmt['u2'], //同上
		'_rnd'=>$time, //时间戳重要，没有直接20k速度
        '_qv_rmt'=> 'http://220.169.153.25/music.qqvideo.tc.qq.com/AaevDT3jnTdJ9pPTLm3bQGde-nNYkf6EbJDxFgiq0Dco/', //限速算法，重要，没有直接20k速度
        '_qv_rmt2'=> 'http://220.169.153.25/music.qqvideo.tc.qq.com/AaevDT3jnTdJ9pPTLm3bQGde-nNYkf6EbJDxFgiq0Dco/', //同上
        'ui_host'=> 2,
        'filename'=>'u0029vu1e9l.mp4',
        'callback'=>'jsonpCallback',
        '_'=>$time, //13位时间戳，我测没有会卡顿
		);
		$url = 'https://h5vv.video.qq.com/getkey';
		$res = http_curl($url,$params);
		$data=jsonp_decode($res,true);
		p($data);

	}

    //End
}