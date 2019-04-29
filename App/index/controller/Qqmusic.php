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
// | Date: 2018/12/7
// +----------------------------------------------------------------------

namespace app\index\controller;
use app\index\model\Music;
class Qqmusic extends Base {
    public $menu = array(
        'music'=>array(
            '4' =>array('name'=>'流行指数','pic'=>'http://y.gtimg.cn/music/common/upload/t_order_channel_hitlist_conf/64073.png'),
            '5' =>array('name'=>'内地','pic'=>'http://y.gtimg.cn/music/common//upload/iphone_order_channel/20140519103525.jpg'),
            '6' =>array('name'=>'港台','pic'=>'http://y.gtimg.cn/music/common//upload/iphone_order_channel/20140519103855.jpg'),
            '26'=>array('name'=>'热门','pic'=>'http://y.gtimg.cn/music/common//upload/iphone_order_channel/20150820172909.jpg'),
            '27'=>array('name'=>'新歌','pic'=>'http://y.gtimg.cn/music/common/upload/iphone_order_channel/20150820172435.jpg','hot'=>true),
            '28'=>array('name'=>'网络歌曲','pic'=>'http://y.gtimg.cn/music/common//upload/t_order_channel_hitlist_conf/47905.png','hot'=>true),
            '36'=>array('name'=>'K歌金曲','pic'=>'http://y.gtimg.cn/music/common/upload/t_order_channel_hitlist_conf/34151.png'),
        ),
        'mv' => array(
            'all'     => array('name'=>'总榜','pic'=>'http://y.gtimg.cn/music/common//upload/iphone_order_channel/20140519103525.jpg'),
            'mainland'=> array('name'=>'内地榜','pic'=>'http://y.gtimg.cn/music/common/upload/t_order_channel_hitlist_conf/64073.png','hot'=>true),
            'jp'      => array('name'=>'日本榜','pic'=>'http://y.gtimg.cn/music/common//upload/iphone_order_channel/20140519103855.jpg'),
            'hktw'    => array('name'=>'港台榜','pic'=>'http://y.gtimg.cn/music/common/upload/t_order_channel_hitlist_conf/34151.png'),
            'kr'      => array('name'=>'韩国榜','pic'=>'http://y.gtimg.cn/music/common//upload/iphone_order_channel/20150820172909.jpg'),
        ),
    );

    public function index(){
        $type  = input('type');
        $topid = input('id','4');
        $page  = input('p','0','intval');
        $model = new Music();
        $list  = [];
        if($type && $type=='mv'){//MV
            $mvlist  = $model->QqMvTopList($topid);
            if($mvlist){
                $list['data']     = $mvlist;
                $list['pagelist'] = '';
                $list['topinfo']['update_time'] = $mvlist['0']['load_time'];
                $list['topinfo']['ListName'] = 'MV'.$this->menu['mv'][$topid]['name'];
            }
            //p($list);die;
        }else{
            $list  = $model->Qqtoplist($topid,$page);
            if($list){
                $total = ceil($list['pageinfo']['total']/$list['pageinfo']['num']);//总页数
                $list['pagelist'] = $model->listpage($total);
                foreach ($list['data'] as $v){
                    $list['audio'][] = array(
                        //'lrc'   => '/api/playinfo?key='.$this->ApiToken.'&info='.$v['type'].','.$v['songmid'].',lyric',
                        'lrc'   => url('play/playinfo').'?key='.$this->ApiToken.'&info='.$v['type'].','.$v['songmid'].',lyric',
                        'name'  => $v['title'],
                        'cover' => $v['albumimg'],
                        'url'   => $v['url'],
                        'artist'=> $v['author'],
                    );
                }
            }
        }
        $list['type'] = $type;
        $title = $list?$list['topinfo']['ListName']:'巅峰榜单';
        $this->assign('title',$title);
        $this->assign('menu',$this->menu);
        $this->assign('list',$list);
        $this->assign('id',$topid);
        $this->assign('param',array('search'=>array('type'=>'qq')));
        return $this->fetch();
    }


    //酷狗MV播放
    public function videos(){
        $id    = input('id');
        $img   = input('img');
        $model = new Music();
        $albumm= $model->QqAlbummInfo($id);
        $mvid  = $albumm['mvid']?:$id;
        $list['mvid']     = $mvid;
        $list['albumimg'] = $img?:$albumm['albumimg'];

        $list = array_merge($albumm,$list);
        $this->assign('list',$list);
        return $this->fetch('video_qq');
    }

    public function  CallbackToJson($str,$callback='mvgetinfo'){
        //preg_match('/QZOutputJson=(.*)/',$str,$content);
        $data = false;
        $res  = str_replace($callback.'(','',$str);
        $res  = json_decode(rtrim($res,')'),true);
        if($res){
            $data = $res;
        }
        return $data;
    }

    //End
}