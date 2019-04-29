<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
//echo date('2017-12-26')."<br>";
//echo time('2017-12-24')."<br>";
//ini_set('session.cookie_domain', ".app.com");

//开启调试模式 建议开发阶段开启 部署阶段注释或者设为false True
define('APP_DEBUG',True);
define('LMWL_VERSION','2.0');

//应用名称
define('APP_NAME', 'App');

//入口目录
define('BAES_DIR',__DIR__);

//目录
define('APP_DIR', BAES_DIR . '/../');

//应用目录
define('APP_PATH', APP_DIR . 'App/');

//运行时目录
define('RUNTIME_PATH',APP_DIR.'Runtime/');
//define('RUNTIME_PATH',__DIR__.'/Runtime/');

ini_set("memory_limit","516M");

// 加载框架引导文件
//require APP_DIR . 'thinkphp/start.php';


/*开启域名部署*/
require APP_DIR . 'thinkphp/base.php';
switch ($_SERVER['HTTP_HOST']) {
    case 'api.app.com':
		\think\Route::bind('api');
        break;
    case 'admin.app.com':
        \think\Route::bind('admin');
        break;
	case 'mp3.app.com':
        \think\Route::bind('index');
        break;
}
\think\App::run()->send();
//开启域名部署

//CLI可以从$_SERVER['argc']和$_SERVER['argv'']取得参数的个数和值   php test.php news 1 5  print_r($argv);

/*system("pwd");
p($result);
$and=" 百  度  一  下  ， 你 就  知  道  ";
echo preg_replace('/[ ]/', '', $and);//除去所以空格
echo '<hr>';

shell_exec("cd layui");
system("pwd");
$output = shell_exec('ls');
//p($output);
$output = preg_replace('/[\n\r]/', '|', $output);
$data = explode("|",rtrim($output,'|'));

p($data);

//exec("dir",$output);
//p($output);*/