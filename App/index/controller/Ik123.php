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
// | Date: 2018/9/28
// +----------------------------------------------------------------------

namespace app\index\controller;
use app\index\model\Music;
use think\Db;
use org\util\QueryList;
class Ik123 extends Base {

    public function index(){
      	$id   = input('id',25,'intval');
        $field= ['id','title','ik_id','play_url','addtime','type'];
        $where= ['type'=>$id,'pid'=>0];
        $list = Db::name('djmp3')->field($field)->where($where)->order('id desc')->paginate(config('paginate.list_rows'));
        $param= array('id'=>$id,'title'=>IkType($id));
        $this->assign('param',$param);
        $this->assign('list',$list);
        $this->assign('page', $list->render());
        return $this->fetch();
        //p($list);
    }

    //采集ik123 最新DJ舞曲
    public function Ik123add(){
        /* $url =<<<STR
                 <form id="musiclist" name="musiclist" method="get"></form>
        STR;*/
        $id      = input('id',1,'intval');
        //$url     = "http://www.ik123.com/djtop/n25_{$id}.html";//最新DJ舞曲
       //$url     = "http://www.ik123.com/special/disco_h_3_{$id}.Htm";
      	//$url     = "http://www.ik123.com/djtop/jinbao/jinbao_6_{$id}.html";//劲爆DJ舞曲
      	$url     = input('url');//劲爆DJ舞曲

        if(!$url){
            echo '请输入url地址';
            die;
        }

        $pattern = '/[(http:\/\/www)|(https:\/\/www)]*\.(html|htm)$/i';
        $url     = urldecode(trims($url));
        if(!preg_match($pattern, $url)){
            $this->error('url地址不正确');
        }

        $pattern = '/<form id="musiclist"(.*?)>(.*?)<\/form>/si';
        $geturl  = http_curl($url,'',['Referer:http://www.ik123.com','Host: www.ik123.com']);
        $list    = '';
     
        if($geturl){
            preg_match_all($pattern,$geturl,$htmls);
            if($htmls && is_array($htmls)){
                $rules = array(
                    'title' => ['.url','text'],
                    'ik_id' => ['input[type=checkbox]','value'],
                );
                $html  = iconv('GBK','UTF-8',$htmls['0']['0']);
                $list =  QueryList::Query($html,$rules,'#musiclist>li')->data;
                if($list){
                    $lists = [];
                    foreach ($list as $v){
                        if($v['ik_id']){
                            $v['title'] = str_replace(array('ik123','试听','by','com','.com',' -',' '),'',$v['title']);
                            $lists[$v['ik_id']]=$v;
                            $ik_id[] = $v['ik_id'];
                        }
                    }
                    //$res = Db::name('djmp3')->field(['id'])->where(['ik_id'=>$list['0']['ik_id']])->find();
                    $res = Db::name('djmp3')->field(['ik_id'])->where(['ik_id'=>['in',$ik_id]])->select();
                    if($res){
                        foreach ($res as $v){
                           unset($lists[$v['ik_id']]);
                        }
                    }

                    if($lists){
                        $addtime = time();
                        foreach($lists as $k=>$v){
                            $lists[$k]['addtime']=$addtime;
                            $lists[$k]['type']=6;
                            $lists[$k]['pid']=0;
                        }
                        Db::name('djmp3')->insertAll($lists);
                    }
                }
            }
        }
        p($lists);
    }

    //播放已采集的曲目 ik123资源
    public function play(){
        /*$header = request()->header();
        $baseurl = parse_url($header['referer']);
        if(!array_key_exists('host',$baseurl) || $header['host'] != $baseurl['host']){
            $this->redirect('Error/index');
        }*/
        $id   = input('id',0,'intval');
        $type = input('type',25,'intval');
        if(!$id){
            $this->redirect('Error/index');
        }

        $music              = Music::Ik123ToInfo($id);
        $music['prev_song'] = Db::name('djmp3')->where(['type'=>$type,'id'=>['gt',$id]])->order('id asc')->value('id');
        $music['next_song'] = Db::name('djmp3')->where(['type'=>$type,'id'=>['lt',$id]])->order('id desc')->value('id');
        $music['m4a']       = $music['url'];

        $this->assign('title','在线播放');
        $this->assign('list',$music);
        //p($music);die;
        if(request()->isMobile()){
            echo json_encode(['code'=>1,'data'=>$music]);
        }else{
            return $this->fetch();
        }
    }

    //获取IK123试听地址
    public function getik123(){
        //$url  = "http://www.ik123.com/mp3-dj/ik123_{$djmp3['ik_id']}.html";
        $model= new Music();
        if(request()->isPost()){
            $url  = input('url');
            $list = $model->Ik123ToInfo($url);
            $list['code'] = 1;
            $list['ik123_url'] = $url;
            return json($list);
        }
        $url  = 'http://www.ik123.com/mp3-dj/ik123_8089.html';
        $list = $model->Ik123ToInfo($url);
        $list['ik123_url'] = $url;
        if(!$list['status']){
            $list = array('title'=>'深港DJ在线试听');
        }
        $this->assign('list',$list);
        $this->assign('title','懂我音乐');
        return $this->fetch('../template/pc/ik123/getik123.html');
        //p($list);
    }

    //设置播放key
    public function setvsid(){
        $vsid = input('vsid');
        if($vsid){
            return token_ik123($vsid);
        }
    }

    public function test(){
        $model = Music::Ik123ToInfo('130');
        p($model);

    }

    //End
}