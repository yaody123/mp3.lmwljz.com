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
// | Date: 2018/6/6
// +----------------------------------------------------------------------

namespace app\index\controller;
use app\index\model\Music;
use think\Db;
class Dwplay extends Base{
    public $Music;

    public function _initialize(){
       $this->Music= new Music();
    }


    //歌曲信息 PC端 MP3格式
    public function info(){
        //验证请求是否合法
        $token = parent::Token_Header();
        if($token['code']==0){
            return json($token);
        }
        //$data  = $this->Music->AudioInfo();
        $data  = $this->Music->MobileAudioInfo();
        if($data){
            $this->Music->PlayDownCount($data['id']);
            return json(['code'=> 1,'data'=>$data]);
        }
        $this->error('暂无法提供试听','',self::playerror());
    }

    //歌曲信息 手机端 m4a格式
    public function playinfo(){
        //验证请求是否合法
        $token = parent::Token_Header();
        if($token['code']==0){
            //return json($token);
            $this->error($token['msg'],'',self::playerror());//play resource validation failed, please play later
        }
        $data  = $this->Music->MobileAudioInfo();
        if($data){
            //unset($data['lyrics']);
            $data['m4a'] = $data['mp3'];
            unset($data['mp3']);
            unset($data['url']);
            //unset($data['lyrics']);
            $this->Music->PlayDownCount($data['id']);
            return json(['code'=> 1,'data'=>$data]);
        }
        $this->error('暂无法提供试听','',self::playerror('非常抱歉!播放资源好像被猩猩抓走了,我们正极力营救,请稍后.'));
    }

    public function index(){
        //abort(404,'页面不存在');die;
        $list = $this->Music->MobileAudioInfo();
        if(!$list){
            $this->redirect('Error/play');
        }
        //unset($list['lyrics']);p($list);
        $title = $list['audio_name'];
        $audio = ['id'=>$list['id'],'title'=>$list['audio_name'],'h'=>$list['hash'],'artist'=>$list['authors'],'timelength'=>$list['timelength']];
        $this->addplay($audio);//添加到播放列表
        $this->assign('title',$title.' - 在线播放');
        $this->assign('list',$list);
        $this->assign('token',self::HtmlToken());
        $this->assign('keyword','');
        return $this->fetch();
    }

    //删除播放列表
    public function delplay(){
        $request= request();
        if($request->isPost()){
            //return json(['code'=>1,'msg'=>'ok']);
            $id = input('id',0,'intval');
            if($id){
                $update=false;
                $list = self::playlist(true);
                foreach($list as $k=>$v){
                    if($v['id']==$id){
                        $update = true;
                        unset($list[$k]);
                        break;
                    }
                }
                if($update){
                    $list = array_values($list);
                    cache($this->uid,$list,config('kugou.maxsave'));
                }
            }
            return json('ok');
        }
        $this->error('网络参数错误');
    }

    //清空播放列表
    public function clearplay(){
        if(cache($this->uid)){
            cache($this->uid,null);
        }
    }




    //End
}