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

    //'default_return_type' => 'json',//默认输入类型

    'LMAPI'               => [
            'PROXY'       => false,// Curl 代理地址，例如：define('PROXY', 'someproxy.com:9999')
            'PROXYUSERPWD'=> false,// Curl 代理用户名和密码，例如：define('PROXYUSERPWD', 'username:password')
            'INTERNAL'    => 0,// 服务器是否在国内
            'lyric'       => false,//是否获取歌词
             //'lyric'       => true,//是否获取歌词
    ],

	// 模板参数替换
    'view_replace_str'    => array(
		'__BAESDIR__'      => '/static',
    ),
    'paginate'=>array(
        'list_rows'=>100,
    ),
];
