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
// | Date: 2019/4/2
// +----------------------------------------------------------------------

namespace app\index\model;
use think\Model;
class BaseModel extends Model {

    /**
     * banner图
     * @param string $flag  显示位置 1.PC  2.移动
     * @return mixed
     */
    public function banner($flag='1'){
        $data = cache('system:banner');
        if($data == false){
            //$data = db('advertising')->field(['title','content','url'])->where(['status'=>1,'type'=>1])->where("find_in_set({$flag}, flag)")->order('sort desc')->select();
            $data = db('advertising')->field(['title','content','url','flag'])->where(['status'=>1,'type'=>1])->order('sort desc')->select();
            if($data){
                cache('system:banner',$data);
            }
        }
        if($data){
            $tmp  = $data;
            $data = [];
            foreach ($tmp as $v){
                if(in_array($flag,explode(',',$v['flag']))){
                    $data[] = $v;
                }
            }
        }
        return $data;
    }

    //End
}