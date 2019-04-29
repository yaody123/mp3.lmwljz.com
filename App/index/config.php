<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [

    // 应用Trace
    //'app_trace'              => true,

    // 模板参数替换
    'view_replace_str'    => array(
		'__BAESDIR__'      => '/static',
        '__PUBLIC__'       => '/static/index',
        '__UEDITOR__'      => '/ueditor',
        '__PUBLIC_MOBILE__'=> '/static/mobile',
        '__BAES_DIR__'     => BAES_DIR,
        '__IMGCSS__'       => '/static/public',
		
    ),

    'app_musickey'   => 'y.lmwljz.com'.date('Ymd'),

    'template'       => [
        // 模板路径
        'view_path'  => APP_DIR.'template/pc/'
    ],

    //首页搜索 和歌单配置
	/*此处配置已删除，如项目需要，请联系作者 qq：1415336788*/
    'kugou' =>array(
        
        //播放列表最大存储条数
        'maxpage'  => 100,
        //列表保存时间单位 秒
        'maxsave'  => 2592000,
    ),

	//酷狗热榜
	'kugou_top'=>array(
	    array(
	        'name'=>'热门榜',//热门榜
            'list'=>array(
                '8888'=>array('name'=>'TOP500','img'=>'/static/public/images/20150717100046499341.png','hot'=>false),
                '6666'=>array('name'=>'飙升榜','img'=>'/static/public/images/20150717100030907982.png','hot'=>false),
                '23784'=>array('name'=>'网络红歌','img'=>'/static/public/images/20150818104300762763.png','hot'=>false),
                '24971'=>array('name'=>'DJ热搜歌榜','img'=>'/static/public/images/20160119114653428408.png','hot'=>false),
                '31308'=>array('name'=>'华语新歌榜','img'=>'/static/public/images/20171206140124940068.png','hot'=>false),
                '24306'=>array('name'=>'全球百大DJ','img'=>'/static/public/images/20160129102832611181.png','hot'=>false),
                '24307'=>array('name'=>'KTV','img'=>'/static/public/images/20151105172333633386.png','hot'=>true),
                '33163'=>array('name'=>'影视金曲榜','img'=>'/static/public/images/20151105172246425804.png','hot'=>true),
                '24574'=>array('name'=>'洗脑神曲','img'=>'/static/public/images/20160713115034579027.jpg','hot'=>true),
                '22163'=>array('name'=>'中国TOP排行榜','img'=>'/static/public/images/20170706113952687748.jpg','hot'=>true),
            ),
        ),

	),

    'log'   => [
        // 日志记录方式，支持 file socket
        'type' => 'File',
        //日志保存目录
        'path' => LOG_PATH,
        //单个日志文件的大小限制，超过后会自动记录到第二个文件
        'file_size'     =>2097152,
        //日志的时间格式，默认是` c `
        'time_format'   =>'Y-m-d H:i:s',
        // error和sql日志单独记录
        //'apart_level'   =>  ['error','sql','cache','info'],
        //'apart_level'   =>  ['error'],
    ],

    //错误页面 非调试模式下有效
    'http_exception_template'    =>  [
        // 定义404错误的重定向页面地址
        404 =>  APP_PATH.'404.html',
        // 还可以定义其它的HTTP status
        401 =>  APP_PATH.'401.html',
    ]





 
];
