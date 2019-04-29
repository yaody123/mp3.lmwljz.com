<?php
// +----------------------------------------------------------------------
// | 首页
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2999 http://lmwljz.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 林梦网络 <1415336788@qq.com>
// +----------------------------------------------------------------------

namespace app\index\controller;
use app\index\model\Music;
use think\Db;
class Index extends  Base {

    //首页
    public function index(){
/*        $mid = 'qq$'.input('id');
        $base_en= base64_encode($mid);
        $base_de=base64_decode(str_replace('#','',$base_en));
        p($base_en);
        p($base_de);
        die;*/
        //p($_SERVER['HTTP_USER_AGENT']);die;
        if($this->isMobile){
            return $this->mindex();
        }
        $model = new Music();
        $list['mvlist']  = $model->TagMvList('neidi');    //推荐MV
        $list['toprank'] = $model->TopRank();     //热门榜单
        $list['hotsong'] = $model->IndexHotSong();//热门单曲
        $list['banner']  = model('BaseModel')->banner();//banner图
        if($list['mvlist']){
            $list['mvlist'] = array_slice($list['mvlist'],0,3); //只取前三个
        }

        $this->assign('list',$list);
        $this->assign('keyword','');
        return $this->fetch();

        //P($list);
    }

    //手机端首页
    public function mindex(){
        $id    = input('id',31308,'intval');
        $model = new Music();
        $list  = $model->MusicRank($id,50);
        $list['banner']  = model('BaseModel')->banner(2);//banner图

        $this->assign('list',$list);
        $this->assign('token',self::HtmlToken());
        return $this->fetch('index');
    }

    //榜单
    public function rank(){
        $id = input('id',0,'intval');
        if($this->isMobile){
            if($id){
                $this->redirect(url('listrank',['id'=>$id]));
            }
            $list = config('kugou_top');
            $this->assign('list',$list['0']['list']);
            $this->assign('title','排行榜');
            return $this->fetch();
        }
        $page = config('paginate.list_rows');//每页显示条数
        $model= new Music();
        $list = $model->MusicRank($id?:8888,$page);
        $this->assign('title',$list['param']['title']);
        $this->assign('list',$list['data']);
        $this->assign('param',$list['param']);
        return $this->fetch();
        //p($list);
    }

    //榜单列表详情手机端
    public function listrank(){
        $id    = input('id',8888,'intval');
        if(!$this->isMobile){
            $this->redirect(url('rank',['id'=>$id]));
        }
        $menu  = config('kugou_top');
        $title = $menu['0']['list'][$id]['name'];
        $model = new Music();
        $list  = $model->MusicRank($id,50);

        $token = self::HtmlToken();
        $list['param']['head']= $list['param']['menu']['0']['list'];
        //unset($list['param']['menu']);
        $this->assign('list',$list['data']);
        $this->assign('param',$list['param']);
        $this->assign('token',$token);
        $this->assign('title',$title);
        $this->assign('id',$id);
        return $this->fetch();
    }

    //搜索
    public function search(){
        $type    = input('type','qq');
        $keyword = input('keyword','');
        $page    = input('p',0,'intval');
        $rows    = config('paginate.list_rows');//每页显示条数
//        $url     = sprintf(config('kugou.search'),$keyword)."&pagesize={$rows}&page={$showp}";
//        $list    = http_curl($url);
//        $list['albumurl'] = config('kugou.albumurl');
        $keyword = trim($keyword);
        $model   = new Music();
        $list    = [];
        switch($type) {
            case 'qq':
                $list = $model->QqMusic($keyword,$page,$rows,128);
                break;
            case 'kugou';
                $list = $model->KugouMusic($keyword,$page,$rows,'mobile');
                break;
            case 'baidu';
                $list = $model->BaiduMusic($keyword,$rows,$page);
                break;
        }

        if($list){
            $list['page']  = $model->listpage(ceil($list['totalnum']/$rows));
        }
        $ApiType= array(
            'qq'   => 'QQ音乐',
            'kugou'=> '酷狗音乐',
            'baidu'=> '百度音乐'
        );

        /*if (!in_array($type, array_keys($ApiType), true)) {
            $this->error('(°ー°〃) 目前还不支持这个网站');
        }*/

        if($list['data']){
            $config = config('music_api');
            $song = $list['data'];
            foreach ($song as $k=>$v){
                $songmid = ($v['type']=='kugou')?$v['songmid']['CQ']:$v['songmid'];
                if(!isset($v['songimg']) || !$v['songimg']){
                    $list['data'][$k]['songimg'] = $config['songpic'];
                }
                if(!isset($v['albumimg']) || !$v['albumimg']){
                    $list['data'][$k]['albumimg'] = $config['albumpic'];
                }
                $list['data'][$k]['url'] = url('Play/'.$v['type'],array('mid'=>$songmid));
            }
        }

        /*if($list['data']){
            $song = $list['data'];
            foreach ($song as $k=>$v){
                //$host = 'http://'.$_SERVER['HTTP_HOST'];
                $geturl = url('Play/'.$v['type']).'?info='.(($v['type']=='kugou')?$v['songmid']['CQ']:$v['songmid']);
                if(!isset($v['url']) || !$v['url']){
                    $list['data'][$k]['url'] = $geturl.'-url';
                }
                if(!isset($v['songimg']) || !$v['songimg']){
                    $list['data'][$k]['songimg'] = $geturl.'-songimg';
                }
                if(!isset($v['lyrics']) || !$v['lyrics']){
                    $list['data'][$k]['lyrics'] = $geturl.'-lyrics';
                }
            }
        }*/
        $this->assign('list',$list);
        $this->assign('keyword',$keyword);
        $this->assign('title',$ApiType[$type].'搜索');

        $this->param['search']['type'] = $type;
        $this->assign('param', $this->param);
        //$this->assign('token',lm_base64_encode(self::HtmlToken()));
        $this->assign('token',self::HtmlToken());

        //p($list);
        return $this->fetch();
    }

    //播放页
    public function  play(){
        $id  = input('id');
        $type= input('type',0,'intval');
        $url = config('kugou.info');
        $list= http_curl($url.$id);

        if($list['status']){
            $data = mp3info(1,$list);
            $res  = Db::name('musiclist')->where(['hash|320hash|sqhash'=>$id])->update($data);
            //p(Db::name('musiclist')->getLastSql());
            $list = $list['data'];
            $list['filesize'] = round($list['filesize']/1024/1024,2).'Mb';

            //歌词
            $list['lyrics']   = explode("[",$list['lyrics']);
            unset($list['lyrics']['0']);
            $lyrics = [];
            foreach($list['lyrics'] as $val){
                $lyrics[] = explode("]",$val);
            }
            $list['lyrics'] = $lyrics;

            //歌曲时长
            $list['times'] = Duration($list['timelength']/1000);

            //MV地址
            $list['mvurl'] = "http://www.kugou.com/mvweb/html/mv_{$list['video_id']}.html";
        }

        $this->assign('title',$list['audio_name'].' - 在线播放');
        $this->assign('list',$list);
        $view = $type?'play':'play_mp3';
        return $this->fetch($view);
    }



    //播放已采集的曲目 ik123资源
    public function playik123(){
        $id   = input('id',0,'intval');
        if(!$id){
            $this->redirect('Error/download');
        }
        $djmp3= Db::name('djmp3')->field(['id','ik_id','play_url','title'])->where(['id'=>$id])->find();
        if(!$djmp3){
            $this->error('歌曲不存在，或者已删除');
        }
        $play = 'http://mp4.ik123.com/Dj_www.ik123.com/2010/';
        $vsid = $this->ik123_vsid();
        if($djmp3['play_url']){
            $djmp3['play_url'] = $play.$djmp3['play_url'].".mp4?vsid={$vsid}&name=www.ik123.com";
        }else{
            $url  = "http://www.ik123.com/mp3-dj/ik123_{$djmp3['ik_id']}.html";
            $list = http_curl($url);
            if($list){
                $pattern = '/furl=(.*?)[.]flv"/si';
                $mp4name = preg_match_all($pattern,$list,$htmls);
                if($mp4name){
                    $mp4name = str_replace('furl="',"",$htmls['0']['0']);
                    $mp4name = str_replace('.flv"',"",$mp4name);
                }
                if($vsid){
                    $vsid = str_replace('varVW_VSID="','',trims($vsid));
                    $vsid = str_replace('";','',trims($vsid));
                    $djmp3['play_url']=$play.$mp4name.".mp4?vsid={$vsid}&name=www.ik123.com";
                    Db::name('djmp3')->where(['id'=>$id])->update(['play_url'=>$mp4name]);
                }
            }
        }

        $this->assign('title','在线播放');
        //$this->assign('list',['zurl'=>'http://www.ik123.com/js/a.js','title'=>'试听','url'=>"{$play}.mp4?vsid={$vsid}&name=www.ik123.com"]);
        $this->assign('list',$djmp3);
        return $this->fetch();
    }

    //第三方音乐api
    public function qqyy(){
        $keyword= input('w');
        $limit  = input('n',20,'intval');
        $page   = input('p',1,'intval');
        $type   = input('type','qq');
        $list   = ['status'=>0,'msg'=>'参数错误'];

        $ApiType= array(
            'qq'   => 'QQ音乐',
            'kugou'=> '酷狗',
            'baidu'=> '百度'
        );
        if (!in_array($type, array_keys($ApiType), true)) {
            $this->error('(°ー°〃) 目前还不支持这个网站');
        }

        switch($type) {
            case 'qq':
                $list = Music::QqMusic($keyword,$page,$limit,320);
                break;
            case 'kugou';
                $list = Music::KugouMusic($keyword,$page,$limit);
                break;
            case 'baidu';
                $list = Music::BaiduMusic($keyword,$limit,$page);
                break;
        }
		$this->assign('list',$list);
        return $this->fetch('aplayer');
        //return json($list);
    }

	public function aplayer(){
        $keyword= input('w');
        $type   = input('type','qq');
        $page   = input('n',20,'intval');
        $list   = array('w'=>$keyword?:'dj','type'=>$type,'n'=>$page);
		$this->assign('list',$list);
        return $this->fetch('aplayer1');
	}

    public function api(){
        $keyword= input('w');
        $limit  = input('n',20,'intval');
        $page   = input('p',1,'intval');
        $type   = input('type','qq');
        $data   = ['status'=>0,'msg'=>'参数错误'];

        $ApiType= array(
            'qq'   => 'QQ音乐',
            'kugou'=> '酷狗',
            'baidu'=> '百度'
        );
        if (!in_array($type, array_keys($ApiType), true)) {
            $this->error('(°ー°〃) 目前还不支持这个网站');
        }
        $list = false;
        switch($type) {
            case 'qq':
                $list = Music::QqMusic($keyword,$page,$limit,128);
                break;
            case 'kugou';
                $list = Music::KugouMusic($keyword,$page,$limit);
                break;
            case 'baidu';
                $list = Music::BaiduMusic($keyword,$limit,$page);
                break;
        }
        if($list['data']){
            foreach ($list['data'] as $k=>$v){
                $geturl = url('getplay',array('id'=>($v['type']=='kugou')?$v['songmid']['CQ']:$v['songmid']),'').'/s/'.$v['type'];
                if(!isset($v['url']) || !$v['url']){
                    $list['data'][$k]['url'] = $geturl.'-url';
                }
                if(!isset($v['songimg']) || !$v['songimg']){
                    $list['data'][$k]['songimg'] = $geturl.'-songimg';
                }
                if(!isset($v['lyrics']) || !$v['lyrics']){
                    $list['data'][$k]['lyrics'] = $geturl.'-lyrics';
                }
            }
            $data['status']  = 1;
            $data['data']    = $list['data'];
            $data['totalnum']= $list['totalnum'];
            $data['msg']     = 'succeed';
        }
        //p($data);die;
        return json($data);
    }

    //歌曲详细信息
    public function getplay(){
        $id     = input('id');
        $gets   = input('s');
        $lodes  = explode('-',$gets);
        $type   = $lodes['0'];
        $action = $lodes['1'];
        $list = [];
        switch($type) {
            case 'qq':
                if($action=='url'){
                    $list['url'] = Music::QqMusicUrlWeixin($id);
                }
                if($action=='lyrics'){
                    $list['lyrics'] = Music::MusicLrc($id);
                }
                break;
            case 'baidu':
                $list = Music::BaiduPlayUrl($id);
                if($action=='lyrics'){
                    $list['lyrics'] = Music::MusicLrc($id,'baidu');
                }
                break;
            case 'kugou';
                $list = Music::KugouPlayUrl($id);
                break;
        }
        //p($list);die;
        if($action == 'lyrics'){
            return $list[$action];
        }
        if($list[$action]){
            $this->redirect($list[$action]);
        }
        return '';
    }

    public function pic(){
        $id   = input('id',10209,'intval');
        $type = input('type','qq');
        $url  = 'http://vv.video.qq.com/getinfo?vids=n00292rkaz0&platform='.$id.'&charge=0&otype=json';
        p($url);
        $data = http_curl($url);
        $data = ltrim($data,'QZOutputJson=');
        $data = rtrim($data,';');
        $data = json_decode($data,true);

        p($data);

    }


    public function wwww(){
        p('asds');
        smartReadFile('Data/123.mp4', '123.mp4');
    }


    public function kugouyy(){
        $keyword= input('w');
        $limit  = input('n',20,'intval');
        $page   = input('p',1,'intval');
        $list   = Music::KugouMusic($keyword,$page,$limit,'mobile');
        return json($list);
    }

    public function mv(){
        $mvid = input('id');
        $list = Music::KugouMvPlayUrl($mvid);
        return json($list);
    }


	
	public function ffmpeg(){
		/*p(APP_PATH);
        p(APP_DIR);
        p(BAES_DIR);

        die;*/
		//$exec = exec('dir');
        shell_exec('set SDL_AUDIODRIVER=directsound');
        //$exec = shell_exec('ffplay  C:\Users\Administrator\Desktop\MV\kaluli101.mkv');
        //$exec = shell_exec('ffmpeg -i C:\Users\Administrator\Desktop\MV\kaluli101.mkv -c:v libx264 -strict -2 C:\Users\Administrator\Desktop\MV\kaluli_libx264.mp4');//视频格式转换

        $cmd = 'ffmpeg -i '.BAES_DIR.'/Data/kaluli101.mkv -f mp3 -vn '.BAES_DIR.'/Data/kaluli1.mp3';

        $exec = shell_exec($cmd);//视频格式转换
		p($exec,1);
		//ffplay  C:\Users\Administrator\Desktop\MV\kaluli101.mkv
	}


    //统计代码
    public function statistics(){
        $js = sysconfig('tongji');
        //$js = '';
        return $js;
    }


    //End
}
