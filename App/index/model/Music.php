<?php
// +----------------------------------------------------------------------
// | Music 基础类
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2999 http://lmwljz.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 林梦网络 <1415336788@qq.com>
// +----------------------------------------------------------------------

namespace app\index\model;
use think\Model;
class Music extends Model{

    protected $prefix = 'lm_';//表前缀

    /**
     * 热门 MV
     * @param string $type      三方来源(如 qq kugou)
     * @param string $listtype  资源类型 all=总榜,mainland=内地榜,jp=日本榜,hktw=港台榜,kr=韩国榜
     * @return array
     */
    public function QqMvList($type='qq',$listtype='all'){
        $config = config('music_api');
        $header = array(
            'user-agent:'.$config['user-agent'],
            'referer:'.$config['qq']['user-agent'],
        );
        $url    = sprintf($config['qq']['mvtoplist'],$listtype);

        $res    = http_curl($url,[],$header);
    }

    /**
     * QQ音乐 推荐MV
     * @param string $lan all=全部,neidi=内地,korea=韩国,gangtai=港台,oumei=欧美,janpan=日本
     * @limt   int   个数
     * @return array
     */
    public function TagMvList($lan='all',$limt=false){
        $data   = cache('tagmvlist:'.$lan);
        if($data == false){
            $config = config('music_api');
            $header = array(
                'user-agent:'.$config['user-agent'],
                'referer:'.$config['qq']['user-agent'],
            );
            $url    = sprintf($config['qq']['mv_index'],$lan);
            $res    = http_curl($url,[],$header);
            $data   = [];
            if(isset($res['code']) && $res['code']==0 && $res['data']){
                foreach($res['data']['mvlist'] as $v){
                    $sum = $v['listennum'];
                    if($sum > 10000){
                        $listennum = $v['listennum']/10000;
                        if($listennum > 1){
                            $sum = round($listennum,1).'万';
                        }
                    }

                    $data[] = array(
                        'singerlist' => $v['singers'],    //所有歌手
                        'singer_name'=>$v['singer_name'], //主歌手
                        'singer_mid' =>$v['singer_mid'],  //歌手ID
                        'singer_id'  =>$v['singer_id'],
                        'mvdesc'     => $v['mvdesc'],
                        'title'      => $v['mvtitle'],
                        'pic'        => $v['picurl'],
                        'date'       => $v['publictime'],
                        'vid'        => $v['vid'],
                        'sum'        => $sum,
                    );
                    unset($sum);
                }
                cache('tagmvlist:'.$lan,$data,3600);
            }
        }
        if($data && is_numeric($limt) && $limt>0){
            $data = array_slice($data,0,$limt);
        }
        return $data;
    }

    public function QqSingers($authors){
        $data = '未知';
        if(!$authors){
            return $data;
        }
        if(is_array($authors)){
            $data = [];
            foreach ($authors as $v){
                $data[] = $v['name'];
            }
            $data = implode('、',$data);
        }else{
            $data = $authors;
        }
        return $data;
    }

    //首页播放列表
    public function TopList($id=8888){
        $ck = 'toplist'.$id;
        $data =  cache($ck);
        if(!$data){
            $list = self:: MusicRank($id,200);
            if($list['data']){
                foreach ($list['data'] as $val){
                    $artist = explode("-",$val['filename']);
                    $data[] = array('h'=>$val['hash'],'title'=>$val['filename'],'artist'=>$artist['0'],'timelength'=>$val['duration']);
                    unset($artist);
                }
                cache($ck,$data,43200);
            }
        }

        return $data;
    }

    //榜单列表
    public function MusicRank($id='8888',$page=30){
        $page = $page?:config('paginate.list_rows');//每页显示条数
        $param= ['id'=>$id,'menu'=>config('kugou_top'),'p'=>input('p',1,'intval'),'page'=>0];
        $url  = config('kugou.top500');
        $url  = sprintf($url,$param['id'],$param['p'],$page);

        $list = http_curl($url);
        $is_add   = true;
        $is_update= false;
        $updata   = [];

        if($list['status']){
            $total= $list['data']['total'];//总数
            $list = $list['data']['info'];
            $param['page']= ceil($total/$page);//总页数

            $updata  = ['type'=>0,'cate_id'=>$param['id'],'page'=>$param['p']];
            $is_add  = $this->name('update')->field(['id','dates'])->where($updata)->find();
            $updata['addtime'] = time();
            $updata['dates']   = date('Ymd',strtotime($list['0']['addtime']));
            if($is_add){
                if($is_add['dates'] != $updata['dates']){
                    $is_update = $this->name('update')->where(['id'=>$is_add['id']])->update(['dates'=>$updata['dates'],'addtime'=>$updata['addtiem']]);
                    $is_add = false;
                }
            }

            $param['pagelist'] = self::listpage($param['page']);
        }else{
            $list = [];
        }

        if($is_add == false){
            $data = [];
            foreach($list as $v){
                $res = $this->name('musiclist')->field(['id'])->where(['hash|320hash|sqhash'=>$v['hash']])->find();
                if(!$res){
                    $data[] = array(
                        'cate_id'    => $param['id'],
                        'audio_name' => $v['filename'],//标题
                        'mvhash'     => $v['mvhash']?:0,//MV
                        'hash'       => $v['hash'],
                        '320hash'    => $v['320hash']?:0,
                        'sqhash'     => $v['sqhash']?:0,
                        'filesize'   => mp3size($v['filesize']),//文件大小
                        'sqfilesize' => mp3size($v['sqfilesize']),
                        '320filesize'=> mp3size($v['320filesize']),
                        'duration'   => Duration($v['duration']),//曲目时长
                        'remark'     => $v['remark'],
                        'addtime'    => strtotime($v['addtime']),//添加时间
                        'song_name'  => $v['filename'],//歌曲名
                        'audio_id'   => $v['audio_id'],
                    );
                }
            }
            if($data){
                $this->name('musiclist')->insertAll($data);
            }
            if($is_update == false){
                $this->name('update')->insert($updata);
            }

        }
        $param['title'] =  $param['menu']['0']['list'][$param['id']]['name'];
        //unset($param['menu']);
        return array('param'=>$param,'data'=>$list,'url'=>$url);

    }

    //热门榜单
    public function TopRank(){
        $list = cache('toprank');
        if (!$list) {
            $name= config('kugou_top');
            $name= $name['0']['list'];
            $ourl = config('kugou.top500');
            $url['1'] = sprintf($ourl, 6666, 1, 2);
            $url['2'] = sprintf($ourl, 8888, 1, 2);
            $url['3'] = sprintf($ourl, 23784, 1, 2);
            $rank = array(
                '6666' => http_curl($url['1']),
                '8888' => http_curl($url['2']),
                '23784'=> http_curl($url['3']),
            );
            $list = [];
            if($rank['6666']['status']){
                $data  = $rank['6666']['data']['info'];
                $list[]=['id'=>6666,'title'=>$name['6666']['name'],'data'=>[$data['0']['filename'],$data['1']['filename']],'img'=>'http://imge.kugou.com/v2/rank_cover/T1M4h4BKKj1RCvBVdK.jpg_240x240.jpg','hash'=>$data['0']['hash']];
            }
            if($rank['8888']['status']){
                $data  = $rank['8888']['data']['info'];
                $list[]=['id'=>8888,'title'=>$name['8888']['name'],'data'=>[$data['0']['filename'],$data['1']['filename']],'img'=>'http://imge.kugou.com/v2/rank_cover/T1fHd4BXd_1RCvBVdK.jpg_240x240.jpg','hash'=>$data['0']['hash']];
            }
            if($rank['23784']['status']){
                $data  = $rank['23784']['data']['info'];
                $list[]=['id'=>23784,'title'=>$name['23784']['name'],'data'=>[$data['0']['filename'],$data['1']['filename']],'img'=>'http://imge.kugou.com/v2/rank_cover/T1Fpd4BKbg1RCvBVdK.jpg_240x240.jpg','hash'=>$data['0']['hash']];
            }
            if($list && is_array($list)){
                cache('toprank',$list,3600*24*3);
            }
        }
        return $list;
    }

    /**
     * 首页热门单曲
     * @param int $row  条数
     * @return array
     */
    public function IndexHotSong($row=24){
        $data = cache('hotsong');
        if(!$data){
            $data=[
                ['id'=>31308,'name'=>'新歌','data'=>self::HotSong(31308,$row)],
                ['id'=>23784,'name'=>'网络红歌','data'=>self::HotSong(23784,$row)]
            ];
            cache('hotsong',$data,86400);
        }
        return $data;
    }

    /**
     * 热门单曲
     * @param int/array $where   条件
     * @param int $row           条数
     * @return array
     */
    public function HotSong($where,$row=5){
        if(is_int($where)){
            $where =['cate_id'=>$where];
        }
        $field = ['audio_name','duration','hash','mvhash'];
        $list  = $this->name('musiclist')->field($field)->where($where)->order('addtime desc')->limit($row)->select();
        if($list){
            $list = objToArray($list);
        }
        return $list;
    }

    /**
     * 音频本地化
     * @param $file  文件信息
     * @param string $type 类型  0.歌曲 1.视频
     * @return bool
     */
    public function audiodisk($file,$type='0'){
        $data   = 0;
        if(!$file['play_url']){
            return false;
        }
        $list = $this->AudiolistFind(['hash'=>$file['hash']]);

        if($list){
            $data = $list['id'];
        }else{
            $data  = $this->MusicDisk($file,$type);
        }
        return $data;
    }

    //歌曲信息 mp3 格式
    public function AudioInfo($play=false){
        $data = false;
        if(request()->param()) {
            $id   = input('id', '');
            $from = input('from', '0','intval');
            if(!$id){return $data;}
            if($from){
                return self::MobileAudioInfo($play);
            }
            $field = ['id as author_id', 'title','song_name', 'authors', 'img', 'lyrics', 'timelength', 'album_name', 'playurl as play_url','hash'];
            $list  = self::AudiolistFind(['hash'=>$id,'type'=>0],$field);
            $res   = $list;

            if($list && $list['play_url']) {
                $list['lyrics'] = json_decode($list['lyrics']);
            }else{
                $url = config('kugou.info');
                $list = http_curl($url . $id);
                if ($list['status'] && $list['data']['play_url']) {
                    $list = $list['data'];
                    if($res){
                        //更新
                        $list['author_id'] = $res['author_id'];
                        $updata = ['lyrics'=>$list['lyrics'],'playurl'=>$list['play_url']];
                        if($res['lyrics']){
                            unset($updata['lyrics']);
                        }
                        self::MusicUpdateAudio(['id'=>$res['author_id']],$updata);
                    }else{
                       $list['author_id'] = self::MusicDisk($list);//新增
                    }

                    $list['title']     = $list['song_name'];
                    $list['authors']   = authors($list['authors']);
                    $list['lyrics']    = AudioToLyrics($list['lyrics'], true);
                    $list['timelength']= Duration($list['timelength'] / 1000);
                }else{
                    $list = false;
                }
            }

            if($list){
                $key  = self::AccessToken(['id'=>$list['hash']]);
                $data = array(
                    'id'        => $list['author_id'],
                    'title'     => $list['title']. ' - ' . config('webname') . ' - 在线播放',//标题
                    'mp3'       => url('Index/musicinfo/index',['key'=>$key],'mp3').'?id='.$list['hash'],    
                    'authors'   => $list['authors'],
                    'img'       => str_replace('{size}','400',$list['img']),
                    'song_name' => $list['song_name'], //歌名
                    'album_name'=> $list['album_name'],//专辑
                    'timelength'=> $list['timelength'],
                    'lyrics'    => $list['lyrics'],
                    'hash'      => $list['hash'],
                );
                if($play){
                    $data['mp3'] = $list['play_url'];
                }
            }

        }
        return $data;
    }

    //歌曲信息 手机端 m4a格式
    public function MobileAudioInfo($play=false,$hash=''){
        if($hash){
            $id = $hash;
        }else{
            $id = input('id');
        }

        //$from = input('from', '0','intval');
        if($id){
            $field = ['id', 'title as audio_name','song_name', 'authors', 'img', 'lyrics', 'timelength', 'album_name', 'url','hash','album_img'];
            $list  = self::AudiolistFind(['hash' => $id,'type'=>0],$field);
            //p($list,1);die;
            $res   = $list;
            if($list && $list['url']){
                //$list['lyrics'] = json_decode($list['lyrics']);
            }else{
                $url  = config('kugou.playInfo');
                $url .= $id.'&from=mkugou';
                $list = http_curl($url,'',['Host: m.kugou.com','Referer: http://m.kugou.com/','Cookie: kg_mid='.md5($id).'; musicwo17=kugou','Connection: keep-alive']);
                if($list['status'] && $list['url']){
                    $lyrics = GetLyrics($list['fileName'],$list['hash'],$list['timeLength']);
                    $list = array(
                        'hash'     => $list['hash'],
                        'audio_name'=> $list['fileName'], //歌曲全名
                        'song_name'=> $list['songName'],  //歌名
                        'authors'  => $list['choricSinger'],//$list['singerName'],//歌手
                        'filesize' => $list['fileSize'],
                        'timelength'=>$list['timeLength']*1000,//时长
                        'img'      => $list['imgUrl']?:$list['album_img'],   //歌手头像
                        'album_img'=> $list['album_img'],//专辑封面
                        'url'      => $list['url'],
                        'bitrate'  => $list['bitRate'],
                        'lyrics'   => $lyrics,
                    );

                    if($res){
                        //更新
                        $list['id'] = $res['id'];
                        $updata = ['lyrics'=>$list['lyrics'],'url'=>$list['url'],'album_img'=>$list['album_img']];
                        if($res['lyrics']){
                            unset($updata['lyrics']);
                        }
                        if($list['authors']){
                            $updata['authors'] = $list['authors'];
                        }
                        self::MusicUpdateAudio(['id'=>$res['id']],$updata);
                    }else{
                        $list['id'] = self::MusicDisk($list);//新增
                    }

                    $list['timelength'] = Duration($list['timelength'] / 1000);
                    //$list['lyrics']     = AudioToLyrics($list['lyrics'], true);
                    unset($list['filesize']);
                    unset($list['bitrate']);
                    unset($list['is_file']);
                }else{
                    $list = false;
                }
            }

            if($list){
                $key  = self::AccessToken(['id'=>$list['hash']]);
                $list['title']     = $list['audio_name']. ' - ' . config('webname') . ' - 在线播放';//标题
                $list['mp3']       = url('index/musicinfo/index',['key'=>$key],'m4a').'?id='.$list['hash'];
                $list['img']       = str_replace('{size}','400',$list['img']);
                $list['album_img'] = str_replace('{size}','400',$list['album_img']);
                if($play){
                    $list['mp3']   = $list['url'];
                }
                unset($list['url']);
            }
            return $list;
        }else{
            return false;
        }
    }

    //歌曲信息
    public function AudiolistFind($where,$field=['id']){
       $data = $this->name('audiolist')->field($field)->where($where)->find();
       if($data){
           $data = $data->toArray();
       }
       return $data;
    }

    //播放列表
    public function PlayList($uid){
        $list = cache($uid);
        if(!$list){
            return false;
        }
        return $list;
    }

    //添加播放列表
    public function AddList($uid,$data=[]){
        $isadd= true;
        $re   = false;
        $data = $data ? $data : request()->param();
        if($data){
            $list = self::PlayList($uid);
            if($list){
                foreach ($list as $k=>$val){
                    if($val['title'] == $data['title']){//排除重复 或设置为前排
                        $isadd = false;
                        $re    = $data;
                        unset($list[$k]);
                        break;
                    }
                }
                if($isadd || $re){
                    $list[] = $data;
                }
            }else{
                $list[] = $data;
            }

            //重新排序
            krsort($list);
            $lists  = $list;
            $list   = [];
            $maxpage= config('kugou.maxpage');
            $counts = 0;
            foreach ($lists as $v){
                $counts++;
                if($counts>$maxpage){
                    break;
                }
                $list[] = $v;
            }

            cache($uid,$list,config('kugou.maxsave'));
        }else{
            $list = cache($uid);
        }
        return $list?json_encode($list,JSON_UNESCAPED_UNICODE):false;
    }

    //存储到本地
    public function MusicDisk($file,$type=0){
        $update= false;
        $data = 0;
        $mp3url = MediaToDisk(isset($file['play_url'])?$file['play_url']:$file['url']);
        if($mp3url){
            $data =array(
                'hash'      => $file['hash'],
                'title'     => $file['audio_name'],//歌曲全名
                'song_name' => $file['song_name'], //歌曲缩写
                'img'       => $file['img'],
                'authors'   => authors($file['authors']),//歌手
                'lyrics'    => $file['lyrics'],//歌词
                'bitrate'   => $file['bitrate'], //音频质量
                'filesize'  => round($file['filesize']/1024/1024,2).'Mb',//文件大小
                'timelength'=> Duration($file['timelength']/1000),//时长
                'type'      => $type,
                'addtime'   => time(),
            );
			
			//专辑
			if(isset($file['album_name']) && $file['album_name']){
				if($data['album_name'] != '未知专辑'){
					$data['album_name'] = $file['album_name'];
				}
			}

            //路径
            if(isset($file['play_url']) && $file['play_url']){
                $data['playurl'] = $mp3url;
            }elseif($file['url']){
                $data['url'] = $mp3url;
            }

            //专辑封面
            if($file['album_img']){
                $data['album_img']  = $file['album_img'];
            }
			            
            $data = $this->name('audiolist')->insertGetId($data);
        }
        return $data;
    }

    /**
     * 更新播放资源
     * @param $where  条件
     * @param $data   字段内容
     * @return $this|bool
     */
    public function MusicUpdateAudio($where=[],$data=[]){
        if(empty($where) || empty($data)){
            return false;
        }
        if($data['playurl'] || $data['url']){
            $mp3url = MediaToDisk($data['playurl']?:$data['url']);
            if($mp3url){
                //歌词
                if($data['lyrics']){
                    $data['lyrics'] = AudioToLyrics($data['lyrics']);
                }

                //路径
                if($data['playurl']){
                    $data['playurl'] = $mp3url;
                }elseif($data['url']){
                    $data['url'] = $mp3url;
                }
            }
        }
        return $this->name('audiolist')->where($where)->update($data);
    }

    //分页码
    public function listpage($total){
        if(!$total || $total <= 1){ return false;}
        $request = request();
        $controller= $request->controller();
        $action  = $request->action();
        $param   = $request->param();
        $params  = '?';
        if(!isset($param['p'])){
            $param['p'] = 1;
        }
        $page = $param['p'];
        if($page>$total){ return false;}

        if($param){
            foreach ($param as $k=>$v){
                if($k !='p'){
                    $params .="{$k}={$v}&";
                }
            }
        }
        $url  = url("{$controller}/$action").$params;
        $next = 5;
        $data = "<a  id='page_first' title='首页' class='direct btnPage' href='{$url}p=1'>首页</a>";
        if($page>1){
            $data .= "<span  class='PrePageSpan'><a id='page_pre_' href='{$url}p=".($page-1)."' title='上一页' class='direct btnPage'>上一页</a></span>";
        }
        if($total > 4 && $page>4){//数据加载第5页时
            $page = $page-3;
            $next = $page;
            $y = 1;
            for($i=0;$i<3;$i++){
                if($page==$param['p']){
                    $data .= "<a id='page_{$y}' href='javascript:void(0)' class='current'>$page</a>";
                }else{
                    $data .= "<a id='page_{$y}' href='{$url}p={$page}'>{$page}</a>";
                }

                $page++;
                $y++;
            }
            for($i=0;$i<3;$i++){
                if($page <= $total){
                    if($page==$param['p']){
                        $data .= "<a id='page_{$y}' href='javascript:void(0)' class='current'>$page</a>";
                    }else{
                        $data .= "<a id='page_{$y}' href='{$url}p={$page}'>{$page}</a>";
                    }
                }
                $page++;
                $y++;
            }
        }elseif($total > 5){ //页码数少于第5页时
            $next = 5;
            $page = 1;
            $y = 1;
            if($total < $next){//控制总数 防止超出
                $next = $total;
            }
            for($i=$page;$i<=$next;$i++){
                if($page==$param['p']){
                    $data .= "<a id='page_{$y}' href='javascript:void(0)' class='current'>$page</a>";
                }else{
                    $data .= "<a id='page_{$y}' href='{$url}p={$page}'>{$page}</a>";
                }
                $page++;
                $y++;
            }
        }else{//页码不足 5 页
            $y = 0;
            $page=0;
            for($i=0;$i<$total;$i++){
                $page++;
                $y++;
                $data .= "<a id='page_{$y}' href='{$url}p={$page}'>{$page}</a>";
            }
        }

        if($total>$param['p']){
            //$page++;
            $data .= "<span class='NextPageSpan' style='border:0px;padding:0px;'><a id='page_next_2' href='{$url}p=".($param['p']+1)."' title='下一页' class='direct btnPage'>下一页</a></span>";
            $data .= "<a id='page_last_23' title='尾页' href='{$url}p={$total}' class='direct btnPage'>尾页</a>";
        }
        return $data;

/*        <a style="visibility:hidden" id="page_first" title="首页" class="direct btnPage" href="javascript:void(0)">首页</a>
        <span style="visibility:hidden" class="PrePageSpan"><a id="page_pre_" href="javascript:void(0)" title="上一页" class="direct btnPage" return="" false;"="">上一页</a></span>
        <span id="page_1" class="current">1</span>
        <a id="page_2" href="javascript:void(0)">2</a>
        <a id="page_3" href="javascript:void(0)">3</a>
        <a id="page_4" href="javascript:void(0)">4</a>
        <a id="page_5" href="javascript:void(0)">5</a>
        <span class="NextPageSpan" style="border:0px;padding:0px;"><a id="page_next_2" href="javascript:void(0)" title="下一页" class="direct btnPage">下一页</a></span>
        <a id="page_last_23" title="尾页" href="{:url('index',array('id'=>$param['id'],'p'=>$param['page']))}" class="direct btnPage">尾页</a>*/
    }

    public function AccessToken($info){
        $info['app_musickey'] = config('app_musickey');
		if(!isset($info['time'])){
			$info['time'] = date('Ymdh');
		}
        ksort($info);
        $sign = '';
        foreach ($info as $k=>$v){
            $sign .="{$k}={$v}";
        }

        $sign = hash('md5',md5($sign));
        return 'lmwljz_'.substr($sign,5,12);
    }

    /**
     * 增加下载量、播放浏览量
     * @param $id
     * @param string $field  playcount downcount
     */
    public function PlayDownCount($id,$field='playcount'){
        $datainc = ['playcount','downcount'];
        if(in_array($field,$datainc)){
            $this->name('audiolist')->where(['id'=>$id])->setInc($field);
        }
    }

    /**
     * 酷狗播放地址及歌词  PC端歌词有效
     * @param $hash [歌曲id]
     * @param $type [手机或PC端]
     * @return array
     */
    public static function KugouPlayUrl($hash,$type='pc'){
        $config = config('music_api.kugou');
        $header = ['referer:http://m.kugou.com/play/info/'.$hash,'user-agent:'.$config['user-agent']];
        $url    = $config[$type]['play'].$hash;
        $data   = cache('kugou:'.$hash.$type);
        if(!$data){
            $res    = http_curl($url,'',$header);
            if(isset($res['status'])){
                switch ($type) {
                    case 'mobile':
                        $data = array(
                            'songmid' => $hash,
                            'songimg' => str_replace('{size}', '400', $res['imgUrl']),
                            'albumimg'=> str_replace('{size}', '400',$res['album_img']),
                            'author'  => $res['choricSinger'],
                            'title'   => $res['songName'],
                            'lyrics'  => self::MusicLrc($hash,'kugou'),
                            'url'     => $res['url'],
                            'time'    => Duration($res['timeLength'])
                        );
                        break;
                    default:
                        if($res['status'] == 1 && $res['data']){
                            $data = array(
                                'songmid'=> $hash,
                                'songimg'=> $res['data']['img'],
                                'albumimg'=>$res['data']['img'],
                                'author' => $res['data']['author_name'],
                                'title'  => $res['data']['song_name'],
                                'lyrics' => $res['data']['lyrics'],
                                'url'    => $res['data']['play_url'],
                                'time'   => Duration($res['data']['timelength']/1000)
                            );
                        }
                }
            }
        }

        if($data){
            $tem = $data;
            unset($tem['lyrics']);
            cache('kugou:'.$hash.$type,$tem,7200);
            if(empty($data['url'])){
                $playerror = OnPlayError();
                //$data['url'] = url('/','','',true).$playerror['m4a'];
                //$data['url'] = config('domain').$playerror['m4a'];
                $data['url'] = $playerror['m4a'];
            }
        }
        return $data;
    }

    /**
     * 酷狗音乐歌曲搜索
     * @param $keyword   [关键词]
     * @param int $page  [页码]
     * @param int $limit [条数]
     * @return array
     */
    public static function KugouMusic($keyword,$page=1,$limit=10,$type='pc'){
        $config =  config('music_api');
        $header = ['referer:'.$config['kugou'][$type]['referer'],'user-agent:'.$config['user-agent']];
        $url    = $config['kugou'][$type]['url'].urlencode($keyword).'&pagesize='.$limit.'&page='.$page;
        $res    = http_curl($url,'',$header);
        $data   = false;
        if($res['status'] != 1){
            return $data;
        }
        $list =  ($type=='pc') ? $res['data']['lists'] : $res['data']['info'];
        if(empty($list) || !is_array($list)){
            return $data;
        }
        //p($res);die;
        switch ($type) {
            case 'mobile':
                foreach($list as $v){
                    $songmid = [];
                    $info    = [];
                    if(!empty($v['hash'])){
                        $songmid['CQ']=$v['hash'];
                    }
                    if(!empty($v['320hash'])){
                        $songmid['SQ']=$v['320hash'];
                    }
                    if(!empty($v['sqhash'])){
                        $songmid['HQ']=$v['sqhash'];
                    }
                    if(!$config['kugou']['status']){
                        $info   = self::KugouPlayUrl($v['hash'],$type);
                    }
                    $song[] = array(
                        'type'   => 'kugou',
                        'title'  => $v['songname'],
                        'author' => $v['singername'],
                        'albumid'=>$v['album_id'],
                        'albumname'=>$v['album_name'],
                        'link'   => $config['kugou']['link'].$v['hash'],
                        'songmid'=> $songmid,
                        'songimg'=> $info?$info['songimg']:'',
                        'url'    => $info?$info['url']:'',
                        'lyrics' => $info?$info['lyrics']:'',
                        'vid'    => $v['mvhash'],
                        'time'   => Duration($v['duration']),
                    );
                }
                $data = array(
                    'totalnum'=> $res['data']['total'],
                    'data'    => $song,
                );
                break;
            default:
                foreach($list as $v){
                    $songmid = $info =[];
                    if(!empty($v['FileHash'])){
                        $songmid['CQ']=$v['FileHash'];
                    }
                    if(!empty($v['SQFileHash'])){
                        $songmid['SQ']=$v['SQFileHash'];
                    }
                    if(!empty($v['HQFileHash'])){
                        $songmid['HQ']=$v['HQFileHash'];
                    }
                    if(!$config['kugou']['status']){
                        $info   = self::KugouPlayUrl($v['FileHash'],$type);
                    }
                    $song[] = array(
                        'type'   => 'kugou',
                        'title'  => $v['SongName'],
                        'author' => $v['SingerName'],
                        'albumname'=> $v['AlbumName'],
                        'albumid'=> $v['AlbumID'],
                        'link'   => $config['kugou']['link'].$v['FileHash'],
                        'songmid'=> $songmid,
                        'vid'    => $v['MvHash'],
                        'songimg'=> $info?$info['songimg']:'',
                        'url'    => $info?$info['url']:'',
                        'lyrics' => $info?$info['lyrics']:'',
                        'time'   =>Duration($v['Duration']),
                    );
                }
                $data = array(
                    'totalnum'=> $res['data']['total'],
                    'data'    => $song,
                );
        }
        return $data;
    }

    /**
     * 百度音乐搜索
     * @param $keyword     [歌曲或歌手]
     * @param int $limit   [显示条数]
     * @param int $page    [页码]
     * @return array|bool
     */
    public static function BaiduMusic($keyword,$limit=10,$page=1,$rate='m4a,mp3'){
        $config = config('music_api');
        $apibaes= $config['baidu'];
        $header = ['user-agent:'.$config['user-agent'],'referer:'.$apibaes['referer']];
        $url    = $apibaes['url'];
        $param  = [
            'method'   =>'baidu.ting.search.common',
            //'format'   =>'json',
            'query'    => urlencode(trim($keyword)),
            'page_size'=> $limit,
            'page_no'  => $page
        ];
        $param  = array_merge($apibaes['param'],$param);
        $res    = http_curl($url,$param,$header);
        $data   = false;
        if(!$res['song_list']){
            return $data;
        }
        //p($res);die;
        $songmid = $list =[];
        foreach($res['song_list'] as $v){
            $list[$v['song_id']] = array(
                'type'   => 'baidu',
                'title'  => str_replace(['<em>','</em>'],'',$v['title']),
                'author' => str_replace(['<em>','</em>'],'',$v['author']),
                'songmid'=> $v['song_id'],
                'albumid'=> $v['album_id'],
                'albumname'=> $v['album_title'],
                'all_rate'=>$v['all_rate'],
                'link'   => $apibaes['link'].$v['song_id'],

            );
            $songmid[] = $v['song_id'];
        }
        if($songmid){
            if($apibaes['status']){
                $playurl = self::BaiduPlayUrl($songmid,$rate);//文件详细信息 播放、图片
                if($playurl){
                    foreach($list as $k=>$v){
                        $arr = array(
                            'songimg'=> $playurl[$k]['songimg'],
                            'lyrics' => $playurl[$k]['lyrics'],
                            'bitrate'=> $playurl[$k]['bitrate'],
                            'link'   => $apibaes['link'].$v['songmid'],
                            'size'   => $playurl[$k]['size'],
                            'time'   => $playurl[$k]['time'],
                            'url'    => $playurl[$k]['url'],
                        );
                        $data[]      = array_merge($v,$arr);
                        unset($arr);
                    }
                }
            }else{
                $data = array_merge(array(),$list);
            }
            $data = array(
                'totalnum'=> $res['pages']['total'],
                'data'    => $data,
            );
        }
        return $data;
    }

    /**
     * 获取百度音乐播放url 封面图
     * @param $id     [歌曲id 数组或者字符串]
     * @param $type   [音质 flac,mp3 | m4a,mp3]
     * @return bool
     */
    public static function BaiduPlayUrl($songmid,$type='m4a,mp3'){
        $data = [];
        if(!$songmid){
            return $data;
        }

        //$songmid = '22808619';
        $config = config('music_api');
        $apibaes= $config['baidu'];
        $header = ['user-agent:'.$config['user-agent'],'referer:'.$apibaes['link'].$songmid];
        if(is_array($songmid)){
            $songmid = implode(',',$songmid);
        }
        $url    = $apibaes['play']; //.'?songIds='.$songmid.'&rate=320&type='.$type;
        $param  = array(
            'songIds'=> $songmid,
            'rate'   => '320',
            'type'   => $type,
        );
        $res    = http_curl($url,$param,$header);
        if($res['errorCode'] == '22000' && $res['data']['songList']){
            foreach($res['data']['songList'] as $v){
                $data[$v['songId']] = array(
                    'url'    => str_replace(array('yinyueshiting.baidu.com','zhangmenshiting.baidu.com','zhangmenshiting.qianqian.com'),'gss0.bdstatic.com/y0s1hSulBw92lNKgpU_Z2jR7b2w6buu',$v['songLink']),
                    'showurl'=> $v['showLink'],
                    'name'   => $v['songName'],
                    'songimg'=> $v['songPicRadio'],
                    //'songimg'=> $v['songPicBig'],
                    'author' => $v['artistName'],
                    'lyrics' => $v['lrcLink'],
                    'time'   => $v['songLink']?Duration($v['time']):'',
                    'bitrate'=> $v['rate'],
                    'size'   => $v['songLink']?round(($v['size']/1024/1024),2).'M':'',
                    'id'     => $v['songId'],
                    'title'  => $v['songName'],
                    'songmid'=> $songmid,
                );
            }
            if(count($data)==1){
                $data = $data[$songmid];
                if(empty($data['url'])){
                    $playerror = OnPlayError();
                    $data['url'] = $playerror['m4a'];
                }
            }
        }
        return $data;
    }

    /**
     * 酷狗MV播放地址
     * @param $mvid   [MVid]
     * @return  array
     */
    public static function  KugouMvPlayUrl($mvid){
        if(empty($mvid)){
            return false;
        }
        $data = cache('kugoumv:'.$mvid);
        if($data == false){
            $config = config('music_api');
            $header = ['Referer:http://m.kugou.com','user-agent:'.$config['user-agent']];
            $url    = $config['kugou']['mvapi'].$mvid;
            $res    = http_curl($url,'',$header);
            if($res['status'] && $res['mvdata']){
                if($res['play_count'] > 10000){
                    $listennum = $res['play_count']/10000;
                    if($listennum > 1){
                        $res['play_count'] = round($listennum,1).'万';
                    }
                }
                $data = ['status'=>1,'title'=>$res['songname'],'author'=>$res['singer'],'playcnt'=>$res['play_count'],'vid'=>$res['hash'],'cover_pic'=>$res['mvicon'],'playurl'=>$res['mvdata']['le']['downurl']];
                $third= array('le'=>'hd','sq'=>'sph','rq'=>'fhd');
               foreach($res['mvdata'] as $k=>$v){
                  if($v['downurl']){
                       $data['url'][$third[$k]] = [
                           'url'    => $v['downurl'],
                           'size'   => round($v['filesize']/1024/1024,2).'M',
                           'time'   => Duration($v['timelength']/1000),
                           //'bitrate'=> $v['bitrate'],
                           'level'  => $third[$k],
                           //'img'    => str_replace('{size}','400',$res['mvicon'])
                       ];
                   }
               }
                cache('kugoumv:'.$mvid,$data,7200);
                //$data['url'] = array_sort_key($data['url'],'DESC','level');
                //$data['url'] = array_reverse($data['url']);//数组倒序显示
            }
        }
        return $data;
    }

    /**
     * QQ音乐歌曲搜索
     * @param $keyword  [关键词]
     * @param $page     [页码]
     * @param $limit    [每页条数 最多30条]
     * @param $rate     [比特率 音质 96 128 320]
     * @return array
     */
    public static function QqMusic($keyword,$page,$limit,$rate=96){
        $apiurl = config('music_api');
        $url    = $apiurl['qq']['url'].'n='.$limit.'&p='.$page.'&w='.urlencode($keyword);
        $headr  =  ['user-agent:'.$apiurl['user-agent'],'Referer:'.$apiurl['qq']['referer']];
        $result = http_curl($url,'',$headr);
        $list   = false;
        //p($result);die;
        if(isset($result['subcode']) && $result['subcode']==0){
            if(isset($result['data']['song']['list']) && is_array($result['data']['song']['list'])){
                foreach($result['data']['song']['list'] as $v){
                    $list[] = array(
                        'type'    => 'qq',
                        'songmid' => $v['songmid'],                                             //歌曲ID
                        'albumid' => $v['albummid'],                                            //专辑ID
                        'author'  => self::AuthorsQq($v['singer']),                            //歌手
                        'title'   => $v['songname'],                                            //歌曲名
                        'albumname'=> $v['albumname'],                                          //专辑名
                        'link'    => sprintf($apiurl['qq']['link'],$v['songmid']),              //源地址
                        'url'     => self::QqMusicUrl($v['songmid'],$rate),                    //播放地址
                        'songimg' => sprintf($apiurl['qq']['songpic'],$v['singer']['0']['mid']),//歌手头像
                        'albumimg'=> sprintf($apiurl['qq']['albumpic'],$v['albummid']),          //专辑封面
                        'time'    => Duration($v['interval']),                                   //歌曲时长
                        'vid'     => $v['vid']                                                   //mv id
                    );
                }
                //$list = array('data'=>self::ToUrl($list),'totalnum'=>$result['data']['song']['totalnum']);
                $list = array('data'=>$list,'totalnum'=>$result['data']['song']['totalnum']);
            }
        }
        return $list;
    }

    //QQ音乐歌手
    protected function AuthorsQq($singer){
        $authors = [];
        foreach ($singer as $v) {
            $authors[] = $v['name'];
        }
        $authors = implode(',', $authors);
        return $authors;
    }

    /**
     * 换取QQ音乐播放地址
     * @param $id
     * @param string $rate
     * @return array|string
     */
    public static function QqMusicUrl($id,$rate='128'){
        $data   = [];
        $key    = self::PlayKey();
        $kbps   = array(
            '320' => 'M800'.$id.'.mp3',
            '128' => 'M500'.$id.'.mp3',
            '96'  => 'C400'.$id.'.m4a'
        );

        //播放key获取失败了
        if(!$key){
            return false;
        }
        $config = config('music_api.qq');
        $data   = sprintf($config['play'],$kbps[$rate],$key);

        return $data;
    }

    /**
     * 获得第三方歌词
     * @param $songmid       [歌曲ID]
     * @param string $type   歌曲渠道 qq kugou
     * @return bool|mixed|string
     */
    public static  function MusicLrc($songmid,$type='qq'){
        $config = config('music_api');
        $url    = $config[$type]['lrc'];
        $data   = cache('lrc:'.$songmid);
        if(empty($data)){
            $defaultLrc = '[00:00.00] 暂无歌词';
            switch($type) {
                case 'qq':
                    $headr  =  ['user-agent:'.$config['user-agent'],'Referer:'.$config[$type]['referer']];
                    $body   = ['songmid'=>$songmid,'format' => 'json','nobase64'=> 1,'songtype'=> 0,'callback'=> 'c'];
                    $res    = self::jsonp2json(http_curl($url,$body,$headr));
                    if($res){
                        $data = self::str_decode($res['lyric']);
                    }
                    break;
                case 'baidu':
                    $headr  = ['user-agent:'.$config['user-agent'],'Referer:'.$config[$type]['link'].$songmid];
                    $body   = ['songid'=>$songmid,'format' => 'json','method'=> $config[$type]['lrc']];
                    $url    = $config[$type]['url'];
                    $res    = http_curl($url,$body,$headr);
                    if(isset($res['lrcContent']) && !empty($res['lrcContent'])){
                        $data = $res['lrcContent'];
                    }
                    break;
                case 'kugou';
                    $headr  = ['user-agent:'.$config['user-agent'],'Referer:http://m.kugou.com/play/info/'.$songmid];
                    $body   = ['hash'=>$songmid,'cmd' => 100,'timelength'=> 999999];
                    $url    .= '?'.http_build_query($body);
                    $data    = http_curl($url,'',$headr);
                    break;
            }
            $data = $data?$data:$defaultLrc;
            if($data !== $defaultLrc){
                cache('lrc:'.$songmid,$data,86400);
            }
        }
        return $data;
    }

    // jsonp 转 json
    protected function jsonp2json($jsonp) {
        if(!$jsonp){
            return false;
        }
        if ($jsonp[0] !== '[' && $jsonp[0] !== '{') {
            $jsonp = @mb_substr($jsonp, mb_strpos($jsonp, '('));
        }
        $json = trim($jsonp, "();");
        if ($json) {
            return json_decode($json, true);
        }
    }

    // 去除字符串转义
    protected function str_decode($str) {
        $str = str_replace(['&#13;', '&#10;'], ['', "\n"], $str);
        $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
        return $str;
    }

    /**
     * 换取QQ音乐播放地址 key 固定不需要从新获取
     * @param $id           [歌曲ID]
     * @param string $rate  [音质  96  128 320 1600]
     * @return array|string
     */
    public static function QqMusicUrlWeixin($id,$rate='96'){
        $data   = false;
        $key    = '?guid=ffffffff82def4af4b12b3cd9337d5e7&uin=346897220&vkey=6292F51E1E384E061FF02C31F716658E5C81F5594D561F2E88B854E81CAAB7806D5E4F103E55D33C16F3FAC506D1AB172DE8600B37E43FAD&fromtag=46';
        $kbps   = array(
            '2100'=> 'A000'.$id.'.ape',//fromtag=53
            '1600'=> 'F000'.$id.'.flac',//fromtag=53
            '320' => 'M800'.$id.'.mp3',
            '128' => 'M500'.$id.'.mp3',
            '96'  => 'C400'.$id.'.m4a',
        );
        if($rate === true){
            foreach($kbps as $k=>$v){
                $data[] = 'http://ws.stream.qqmusic.qq.com/'.$v.$key;
            }
        }else{
            $data = 'http://ws.stream.qqmusic.qq.com/'.$kbps[$rate].$key;
        }
        return $data;
        //return self::ToUrl($data);
    }

    //短连接转换
    protected function ToUrl($data){
        if(empty($data)){
            return false;
        }
        $tourl = "http://api.t.sina.com.cn/short_url/shorten.json?source=1133716002";
        if(is_array($data)){
            $param = '';
            foreach ($data as $k=>$v){
                $param .='&url_long='.urlencode($v['url']);
            }
        }else{
            $param = '&url_long='.urlencode($data);
        }
        $tourl .= $param;
        $res   = http_curl($tourl);

        //接口请求失败了
        if(!$res || isset($res['error_code'])){
            return false;
        }
        if(is_array($data)){
            foreach ($res as $k=>$v){
                if(isset($data[$k]['url'])){
                    $data[$k]['url']=$v['url_short'];
                }else{
                    $data[$k]=$v['url_short'];
                }
            }
        }else{
            $data = $res['0']['url_short'];
        }

        return $data;
    }

    //播放key
    protected function PlayKey($type='qq'){
        $data = '';
        switch ($type) {
            case 'qq':
                $qqkey = cache('vkey_'.$type);
                if($qqkey==false){
                    $config= config('music_api');
                    $headr = ["user-agent:{$config['user-agent']}","Referer:{$config[$type]['referer']}"];
                    $qqkey = http_curl($config[$type]['key'],'',$headr);
                    cache('vkey_'.$type,$qqkey,86400);
                }
                $data = $qqkey['key'];
                break;
        }
        return $data;
    }

    /**
     * QQ音乐巅峰榜
     * @param int $topid  巅峰榜ID
     * @param int $begin  起始条数
     * @param int $rate   音质
     * @return array
     */
    public function Qqtoplist($topid=26,$begin=0,$rate=96){
        $limit  = config('paginate.list_rows');
        $page   = $begin?($begin*$limit):0;
        $config= config('music_api');
        $header= ['referer:https://y.qq.com/n/yqq/toplist/'.$topid.'.html'];
        $url   = sprintf($config['qq']['toplist'],$topid,$page,$limit);
        $res   = http_curl($url,'',$header);
        $data  = [];
        if(isset($res['songlist']) && $res['songlist']){
            foreach($res['songlist'] as $v){
                $data['data'][] = array(
                    //'albummid'=> $v['albummid'],
                    'type'    => 'qq',
                    'songmid' => $v['data']['songmid'],                                             //歌曲ID
                    'albummid'=> $v['data']['albummid'],                                            //专辑ID
                    'mvid'    => $v['data']['vid'],                                                 //MV曲ID
                    'author'  => self::AuthorsQq($v['data']['singer']),                            //歌手
                    'title'   => $v['data']['songname'],                                            //歌曲名
                    'albumdesc'=>$v['data']['albumdesc'],                                           //专辑介绍
                    'link'    => sprintf($config['qq']['link'],$v['data']['songmid']),              //源地址
                    'url'     => self::QqMusicUrlWeixin($v['data']['songmid'],$rate),              //播放地址
                    'songimg' => sprintf($config['qq']['songpic'],$v['data']['singer']['0']['mid']),//歌手头像
                    'albumimg'=> sprintf($config['qq']['albumpic'],$v['data']['albummid']),         //专辑封面
                    'time'    => Duration($v['data']['interval']),                                  //歌曲时长
                    //'isonly'    => Duration($v['data']['isonly']),                                //是否独家
                    'lyrics'  => '',
                    'in_count'=> floor($v['in_count']*100),                                  //人气
                );
            }
            $data['topinfo'] = $res['topinfo'];
            $data['topinfo']['update_time'] = $res['update_time'];
            $data['pageinfo']= array('total'=>$res['total_song_num'],'page'=>$res['song_begin'],'num'=>$res['cur_song_num']);
        }
        //p($res);die;
        return $data;
    }

    /**
     * QQ音乐MV榜单
     * @param string $listtype  榜单ID
     * @return array
     */
    public function QqMvTopList($listtype='all'){
        $config = config('music_api');
        $header = ['user-agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36','referer:https://y.qq.com/portal/mv_toplist.html'];
        $url    = sprintf($config['qq']['mvtoplist'],$listtype);
        $res    = http_curl($url,'',$header);
        $data   = [];
        if(isset($res['data']['list']) && $res['data']['list']){
            foreach($res['data']['list'] as $v){
                $data[] = array(
                    'title'=> $v['info']['Fmv_title'],
                    'pic'  => $v['info']['Fpic'],
                    'vid'  => $v['info']['Fvid'],
                    'load_time' =>date('Y-m-d',$v['info']['Fupload_time']),//发布时间
                    'author'=>self::MvAuthor($v['singers']),//歌手
                    'score' =>$v['score'],//热度
                    'play_count'=>$v['play_count'],
                );
            }
        }
        return $data;
    }

    //MV转换歌手
    public function MvAuthor($singers){
        $data = [];
        foreach($singers as $v){
            $data[] = $v['name'];
        }
        if($data){
            $data = implode('/',$data);
        }
        return $data;
    }

    /**
     * QQ音乐歌曲信息
     * @param $songmid   [歌曲id]
     * @param int $rate  音质
     * @return array
     */
    public function QqSongInfo($songmid,$rate=96){
        $data   = cache('qqsong:'.$songmid.$rate);
        if(!$data){
            $config = config('music_api');
            $header = ['user-agent:'.$config['user-agent'],'referer:'.$config['qq']['referer']];
            $body   = ['songmid'=>$songmid,'format'=>'json'];
            $url    = $config['qq']['info'];
            $res    = http_curl($url,$body,$header);
            $data   = [];
            if(isset($res['data']) && $res['data']){
                $list = $res['data']['0'];
                $data = array(
                    'songmid'  => $list['mid'],
                    'vid'      => $list['mv']['vid'],
                    'albumid'  => $list['album']['mid'],
                    'albumname'=> $list['album']['title'],
                    'author'   => $list['singer']['0']['name'],
                    'songimg'  => sprintf($config['qq']['songpic'],$list['singer']['0']['mid']),
                    'albumimg' => sprintf($config['qq']['albumpic'],$list['album']['mid']),
                    'albumdesc'=> $list['subtitle'],
                    'title'    => $list['name'],
                    'url'      => self::QqMusicUrlWeixin($list['mid'],$rate),
                    'time'     => Duration($list['interval']),
                    'type'     => 'qq',
                );
                cache('qqsong:'.$songmid.$rate,$data,3600);
                if(empty($data['url'])){
                    $playerror = OnPlayError();
                    $data['url'] = $playerror['m4a'];
                }
            }
        }
        return $data;
    }

    /**
     * QQ音乐专辑信息
     * @param $albummid  专辑ID
     * @param int $rate  音质
     * @return array
     */
    public function QqAlbummInfo($albummid,$rate=96){
        $config = config('music_api');
        $header = ['user-agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36','referer:https://y.qq.com/portal/player.html'];
        $url    = 'https://c.y.qq.com/v8/fcg-bin/fcg_v8_album_info_cp.fcg?format=json&inCharset=utf8&outCharset=utf-8&notice=0&platform=yqq&albummid='.$albummid;
        $res    = http_curl($url,'',$header);
        $data   = [];
        if(isset($res['data']) && isset($res['data']['list'])){
            $list = $res['data']['list'];
            foreach($list as $v){
                if($v['vid']){
                    $data = array(
                        'songmid'  => $v['songmid'],
                        'mvid'     => $v['vid'],
                        'albumid'  => $v['albumid'],
                        'songimg'  => sprintf($config['qq']['songpic'],$v['singer']['0']['mid']),
                        'albumimg' => sprintf($config['qq']['albumpic'],$v['albummid']),
                        'albumdesc'=> $v['albumdesc'],
                        'title'    => $v['songname'],
                        'time'     => Duration($v['interval']),
                        'url'      => self::QqMusicUrlWeixin($v['songmid'],$rate),
                        'desc'     => $res['data']['desc'],//专辑介绍
                        'dates'    => $res['data']['aDate'],
                        'type'     => 'qq',
                    );
                    break;
                }
            }

        }
        return $data;
    }

    /**
     * 第三方视频解析
     * @param $vid
     * @param string $type
     * @return array
     */
    public function GetVideoUrl($vid,$type='qq'){
        $url  = 'http://v.ranks.xin/video-parse.php?url=';
        switch ($type) {
            case 'qq':
                $url .= urlencode('http://v.qq.com/x/page/'.$vid.'.html');
                break;
        }
        $head = ['referer:http://v.ranks.xin/','user-agent:Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.80 Safari/537.36'];
        $res  = http_curl($url,'',$head);
        $data = [];
        if(isset($res['data']) && is_array($res)){
            $data = $res['data'];
//            foreach ($res['data'] as $v){
//                $data[] = $v['url'];
//            }
        }
        return $data;
    }

    /**
     * 腾讯视频解析
     * @param  $vid
     * @return array
     */
    public function GteQqVideo($vid){
		//参数中的defn为清晰度标识，可选值有SD（标清），HD（高清），SHD（超清），FHD（1080P）请求结果如下：
		$url   = "http://vv.video.qq.com/getinfo?vids={$vid}&platform=101001&charge=0&otype=json&defn=shd";
		$info  = http_curl($url);
		$data  = [];
		if($info){
			preg_match('/QZOutputJson=(.*);/',$info,$content);
			$info   = json_decode($content['1'],true);
			$format = $info['fl']['fi']['1']['id'];
			$info   = $info['vl']['vi']['0'];
			$host   = $info['ul']['ui']['0']['url'];
			$data  = array(
				'title' => $info['ti'],
				'url1'  => $host.$info['fn'].'?sdtfrom=v1001&type=mp4&level=0&platform=70202&br=128&fmt=shd&sp=0&guid=ffffffff82def4af4b12b3cd9337d5e7&vkey='.$info['fvkey'],
			);
			$url    = 'http://vv.video.qq.com/getkey?format='.$format.'&otype=json&vt=150&vid='.$vid.'&charge=0&filename='.$vid.'.mp4&platform=11&guid=ffffffff82def4af4b12b3cd9337d5e7';
			$getkey = http_curl($url);
			if($getkey){
				preg_match('/QZOutputJson=(.*);/',$getkey,$content);
				$getkey  = json_decode($content['1'],true);
				if(isset($getkey['key'])){
					$data['url2'] = $host.$getkey['filename'].'?sdtfrom=v1001&type=mp4&start=2&level=0&platform=70202&br=287&fmt=shd&sp=0&guid=ffffffff82def4af4b12b3cd9337d5e7&vkey='.$getkey['key'];
				}
			}
		}
		return $data;
    }

    /**
     * 腾讯MV解析
     * SD（标清），HD（高清），SHD（超清），FHD（1080P）
     * @param  [$vid]
     * @return array
     */
    public function GteQqMV($mvid){
        if(empty($mvid)){
            return false;
        }
        $list = cache('qqmv:'.$mvid);
        if(!$list){
            $getMvUrl = array(
                'getMvUrl'=>array('module'=>'gosrf.Stream.MvUrlProxy', 'method'=>'GetMvUrls', 'param'=>array('vids'=>array($mvid),'request_typet'=>10001)
                )
            );
            $param    = array('data'=>json_encode($getMvUrl),'g_tk'=>'1454750237','callback'=>'jQuery112302853267332208149_1546667291497','loginUin'=>0,'hostUin'=>0,'format'=>'jsonp','inCharset'=>'utf8','outCharset'=>'GB2312','notice'=>0,'platform'=>'yqq','needNewCode'=>0
            );

            //MV基本信息
            $head = array( 'referer:https://y.qq.com/n/yqq/mv/v/'.$mvid.'.html',
                'user-agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.80 Safari/537.36',
                'origin: https://y.qq.com','accept: application/json, text/javascript, */*; q=0.01'
            );
            $body = http_build_query($param);
            $url  = 'https://u.y.qq.com/cgi-bin/musicu.fcg?'.$body;
            $res  = jsonp_decode(http_curl($url,'',$head),true);
            $list = [];
            if($res['code']==0){
                $mvlist= array_reverse($res['getMvUrl']['data'][$mvid]['mp4']);
                $expire= 80000;
                $rate  = array('0'=>'od','10'=>'sd','20'=>'hd','30'=>'sph','40'=>'fhd');
                foreach($mvlist as $k=>$v){
                    if($v['code']==0){
                        $list[$rate[$v['filetype']]] =array(
                            'host'=>$v['url'],
                            'vkey'=>$v['vkey'],
                            'url' =>$v['freeflow_url']['0']
                        );
                    }
                }
                if($list){
                    cache('qqmv:'.$mvid,$list,$expire);
                }
            }
        }
        return $list;
    }

    /**
     * IK123 资源获取
     * @param $url 试听播放地址 或 本地ID
     * @return array
     */
    public static function Ik123ToInfo($url){
        $data = array('img'=>config('view_replace_str.__PUBLIC__').'/images/play/logo.png','authors'=> '懂我音乐');
        if(!$url){
            $data['msg'] = 'url：为必填项';
            return $data;
        }

        if(is_numeric($url)){
            $music = db('djmp3')->field(['id','ik_id','play_url','title'])->where(['id'=>$url])->find();
            if(!$music){
                $data['msg'] = '歌曲不存在，或者已删除';
                return $data;
            }
            if($music['play_url']){
                $domain = config('music_api.ik123_play');//播放地址域名+路径
                $vsid   = ik123_vsid();
                $data['url'] = $domain.$music['play_url'].".mp4?vsid={$vsid}&name=www.ik123.com";
                $music['title']= str_replace(array('ik123','试听','by','com','.com',' -',' '),'',$music['title']);
                unset($music['play_url']);
            }else{
                $url  = "http://www.ik123.com/mp3-dj/ik123_{$music['ik_id']}.html";
                $id   = $music['id'];
                $music= self::GetIk123($url);
                if(isset($music['host_url']) && $music['host_url']){
                    db('djmp3')->where(['id'=>$id])->update(['play_url'=>$music['host_url']]);
                }
            }
        }else{
            $music = self::GetIk123($url);
        }

		if($music){
			$data = array_merge($data,$music);
		}
        return $data;
    }

    /**
     * 请求ik123资源 得到真实地址
     * @param $url
     * @return array
     */
	public function GetIk123($url){
		$data    = [];
		$pattern = '/[(http:\/\/www)|(https:\/\/www)]*\.(html)$/i';
		$url     = urldecode(trims($url));
		if(!preg_match($pattern, $url)){
			$data['msg'] = 'url地址不正确';
			return $data;
		}
        $domain = config('music_api.ik123_play');//播放地址域名+路径
        $vsid   = ik123_vsid();
		$list   = http_curl($url);
		if($list){
			$pattern = '/furl=(.*?)[.]flv"/si';
			$mp4name = preg_match_all($pattern,$list,$htmls);
			if($mp4name){
				$mp4name = str_replace('furl="',"",$htmls['0']['0']);
				$mp4name = str_replace('.flv"',"",$mp4name);

                //获取标题
                $gettitle = preg_match_all('/<title>(.*)<\/title>/i',$list,$title);
                $title = isset($title['1']['0'])?iconv('GBK','UTF-8',$title['1']['0']):'VIP 免费获取试听资源 - 懂我音乐';
                $title = explode(",",$title);
                if($vsid){
                    $data['url']   = $domain.$mp4name.".mp4?vsid={$vsid}&name=www.ik123.com";
                    $data['title'] = str_replace(array('ik123','试听','by','com','.com',' -',' - '),'',$title['0']);
                    $data['host_url']= $mp4name;
                    $data['status'] =1;
                }
			}
		}
		if(!$data){
            $data = OnPlayError('试试其他资源吧！资源被星星抓走了，我们表示很遗憾！');
            $data['url']   = $data['mp3'];
            $data['status']= 0;
        }
		return $data;
	}

    /**
     * 腾讯MV 一键获取资源+相关推荐
     * @param $vid  [视频vid]
     * @return bool
     */
	public function yqqmv($vid){
	    $data = false;
	    if(empty($vid)){return $data;}
        $mvinfo = array(
            'mvinfo'=>array( 'module'=> 'video.VideoDataServer','method'=> 'get_video_info_batch',
                'param' => array('vidlist' => array($vid),'required'=> array('vid','type','sid','cover_pic','duration','singers','video_switch','msg','name','desc','playcnt','pubdate','isfav','gmid'))
            ),
            'other' => array( 'module'=>'video.VideoLogicServer', 'method'=> 'rec_video_byvid',
                'param' => array(
                    'vid'     => $vid,
                    'required'=> array('vid','type','sid','cover_pic','duration','singers','video_switch','msg','name','desc','playcnt','pubdate','isfav','gmid','uploader_headurl','uploader_nick','uploader_encuin','uploader_uin','uploader_hasfollow','uploader_follower_num'),
                    'support' => 1
                ),
            ),
        );
	    $platform = array('g_tk'=>5381,'loginUin'=>0,'hostUin'=>0,'format'=>'json','inCharset'=>'utf8','outCharset'=>'utf-8', 'notice'=>0,'platform'=>'yqq.json','needNewCode'=>0,'data'=>json_encode($mvinfo));
	    $url      = config('music_api.qqmv_musicu').'?'.http_build_query($platform);
	    $header   = array(
	        'cookie:o_cookie=1415336788; userAction=1; uid=5513301;',
            'origin:https://y.qq.com',
            'referer:https://y.qq.com/n/yqq/mv/v/'.$vid.'.html',
            'user-agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36'
        );
	    $res      = http_curl($url,[],$header);
	    if(isset($res['mvinfo']) && $res['mvinfo']){
            //$data['mvinfo'] = $res['mvinfo']['data'][$vid];
            $data = $res['mvinfo']['data'][$vid];
            $data['other']  = $res['other']['data']['list'];
            if($data['playcnt'] > 10000){
                $listennum = $data['playcnt']/10000;
                if($listennum > 1){
                    $data['playcnt'] = round($listennum,1).'万';
                }
            }
            $data['author'] = $data['singers']?$this->YqqSingers($data['singers']):$data['uploader_nick'];
            $data['title']  = $data['name'];
            $data['img']    = $data['cover_pic'];
        }
        return $data;
    }

    public function YqqSingers($singers){
	    $data = $singers;
	    if(is_array($singers)){
            $data = '';
	        foreach ($singers as $v){
	            $data .= $v['name'].'、';
            }
            $data = rtrim($data,'、');
        }
        return $data;
    }

    //获取腾讯视频评论
    public function yqqcomment($vid,$video_switch=5,$page=0,$pagesize=30){
        $data = false;
        if(empty($vid)){return $data;}
        $platform= array( 'g_tk'=>5381,'loginUin'=>0,'hostUin'=>0,'format'=>'json','inCharset'=>'utf8','outCharset'=>'GB2312','notice'=>0, 'platform'=>'yqq.json','needNewCode'=>0,'reqtype'=>2,'biztype'=>$video_switch,
            'topid'  => $vid,'cmd'=>8, 'needmusiccrit'=>0,
            'pagenum'=> $page,'pagesize'=>$pagesize, 'domain'=>'qq.com',
        );
        $header  = array(
            'cookie:o_cookie=1415336788;userAction=1;uid=5513301;',
            'origin:https://y.qq.com',
            'referer:https://y.qq.com/n/yqq/mv/v/'.$vid.'.html',
            'user-agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36'
        );
        $url = config('music_api.qqmv_comment').'?'.http_build_query($platform);
        $res = http_curl($url,[],$header);
        if(isset($res['comment']['commentlist']) && $res['comment']['commentlist']){
            $data = array(
                'list' => $res['comment']['commentlist'],
                'total'=> $res['comment']['commenttotal'],
                'hots' => array('list'=>$res['hot_comment']['commentlist'],'commenttotal'=>$res['hot_comment']['commenttotal']),
            );
        }
       return $data;
    }


    //End
}