<?php
// +----------------------------------------------------------------------
// | API
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2999 http://lmwljz.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 林梦网络 <1415336788@qq.com>
// +----------------------------------------------------------------------

namespace app\api\controller;
use think\Controller;
use app\api\model\Music as M;
use app\index\model\Music;
use video\Videos;

//use org\util\QueryList;

class Index extends Controller {

    // 支持的网站
    protected  $WebType = array(
        'netease'    => '网易',
        'qq'         => 'ＱＱ',
        'kugou'      => '酷狗',
        'baidu'      => '百度',
        /* 'kuwo'       => '酷我',
         'xiami'      => '虾米',
         '1ting'      => '一听',
         'migu'       => '咪咕',
         'lizhi'      => '荔枝',
         'qingting'   => '蜻蜓',
         'ximalaya'   => '喜马拉雅',
         'kg'         => '全民K歌',
         '5singyc'    => '5sing原创',
         '5singfc'    => '5sing翻唱'*/
    );

	public function _initialize(){
        clientlog();
	}

	public function index(){
	    //$url ='http://api.app.com/index/search?keyword=%E4%B8%9C%E6%96%B9%E6%99%B4%E5%84%BF';
        $topid = input('id','4');
        $page  = input('p','0','intval');
        $model = new \app\index\model\Music();
        $list  = $model->Qqtoplist($topid,$page);
        if($list){
            $ApiToken =config('musictoken');
            $total = ceil($list['pageinfo']['total']/$list['pageinfo']['num']);//总页数
            $list['pagelist'] = $model->listpage($total);
            foreach ($list['data'] as $v){
                $list['audio'][] = array(
                    'lrc'   => '/playinfo?key='.$ApiToken.'&info='.$v['type'].','.$v['songmid'].',lyric',
                    'name'  => $v['title'],
                    'cover' => $v['albumimg'],
                    'url'   => $v['url'],
                    'artist'=> $v['author'],
                );
            }
        }

	    $url ='https://www.kancloud.cn/yaody123/dwyyapi/895045';
        $this->assign('url',$url);
        $this->assign('list',$list);
        return $this->fetch();
        //p($list);
    }

	//音乐搜索
    public function search(){
        liuliangtongji();
	    if(!request()->isPost()){
            ApiReturnData('Use the post request',0);
        }
        $limit          = input('limit',10,'intval');  //每页显示多少条
        $keyword        = input('keyword');                            //关键字
        $filter         = input('filter','name');             //搜索类型
        $type           = input('type','qq');                 //来源渠道
        $page           = input('page','1','intval');  //页码
        $keyword        = trim($keyword);
        $valid_patterns = array(
            'name' => '/^.+$/i',
            'id' => '/^[\w\/\|]+$/i',
            'url' => '/^https?:\/\/\S+$/i'
        );

        if (!$keyword || !$filter || !$type) {
            ApiReturnData('(°ー°〃)参数错误',0);
        }
        $keyword = urldecode(trims($keyword));
        if ($filter !== 'url' && !in_array($type, array_keys($this->WebType), true)) {
            ApiReturnData('(°ー°〃) 目前还不支持这个网站',0);
        }

        if (!preg_match($valid_patterns[$filter], $keyword)) {
            ApiReturnData('(・-・*) 请检查您的输入是否正确',0);
        }

        $data = false;
        $model = new M();
        switch ($filter) {
            case 'name':
                if(!$page){
                    $page = 1;
                }
                $data = $model->SongName($keyword,$type,$page,$limit);
                break;
            case 'id':
                $data = $model->SongId($keyword, $type);
                break;
            case 'url':
                $data = $model->SongUrl($keyword);
                break;
        }

        if (empty($data)) {
            ApiReturnData('ㄟ( ▔, ▔ )ㄏ 没有找到相关信息',0);
        }

        if($data['error']) {
            ApiReturnData('(°ー°〃) ' . $data['error'],0);
        }
        ApiReturnData($data);
    }

    //获取歌词
    public function lyrics(){
        $songid= input('id');   //歌曲ID
        $type  = input('type'); //渠道类型
        if(empty($songid)){
            ApiReturnData('参数错误！',0);
        }
        if(!in_array($type,array_keys($this->WebType))){
            ApiReturnData('目前还不支持这个网站',0);
        }
        $model = new M();
        $data  = $model->SongLyric($songid,$type);
        if($data){
            ApiReturnData($data);
        }else{
            ApiReturnData('没有找到相关歌词',0);
        }

    }


    public function ik123(){
        $url  = input('url');
        $model= new Music();
        $list = $model->Ik123ToInfo($url);
        p($list);

    }

    public function videos(){
        $model = new Videos();
        $list  = $model->index('l0029239gst','qq');
        p($list);

    }
   

   
    //End
}
