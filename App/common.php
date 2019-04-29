<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

error_reporting(E_ERROR | E_WARNING | E_PARSE);
use mailer\phpmailer;
use think\Db;
function p($str,$type=null){
    echo '<pre>';
    if($type){
        var_dump($str);
    }else{
        print_r($str);
    }
}

function get_phone_info($phone) {
	$host = "http://ali-mobile.showapi.com";
	$path = "/6-1";
	$method = "GET";
	$appcode = "76a207bd50f94962af230133fb712d0c";
	$headers = array();
	array_push($headers, "Authorization:APPCODE " . $appcode);
	$querys = "num=" . $phone;
	$bodys = "";
	$url = $host . $path . "?" . $querys;

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_FAILONERROR, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HEADER, false);
	return curl_exec($curl);
}

//culr请求
function http_get($url){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_NOBODY, 0); //只取body头
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($ch);
    //$httpinfo = curl_getinfo($ch);
    curl_close($ch);
    //$imageAll = array_merge(array('header' => $httpinfo), array('body' => $package));
    //$jsonData  = json_decode($data,true);
    //return ($jsonData === NULL) ? $data : $jsonData;
    return $data;
}

/**
 * 发送数据请求 GET POST
 * @param String $url     请求的地址
 * @param Array  $header  自定义的header数据
 * @param Array  $postdata POST的数据
 * @param int    $second   请求超时
 * @return String
 * $GLOBALS['HTTP_RAW_POST_DATA'];  file_get_contents("php://input")
 */
function http_curl($url,$postdata=[],$header=[],$second=30){
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_HTTPAUTH,CURLAUTH_BASIC);  //设置http验证方法
    curl_setopt($ch,CURLOPT_TIMEOUT,$second);    //超时时间
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, false); //禁止头信息输出
    curl_setopt($ch, CURLOPT_NOBODY, false); //只取body头
    if(substr($url,0,5)=='https'){
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
        //cert 与 key 分别属于两个.pem文件
        /*curl_setopt($ch,CURLOPT_SSLCERT, "D:/Program Files/phpStudy/Apache/conf/ssl/server.crt");
        curl_setopt($ch,CURLOPT_SSLKEY, "D:/Program Files/phpStudy/Apache/conf/ssl/server.key");
        curl_setopt($ch,CURLOPT_CAINFO, "D:/Program Files/phpStudy/Apache/conf/ssl/pem/rootca.pem");*/
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//设置curl_exec获取的信息的返回方式
    if($header){
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //设置请求头信息
    }


    if($postdata){
        curl_setopt($ch, CURLOPT_POST, true);  //设置发送方式为post请求
        if(is_array($postdata)){
            $postdata = http_build_query($postdata);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS,$postdata); //post 数据
    }

    //curl_setopt($ch, CURLINFO_HEADER_OUT, true); //TRUE 时追踪句柄的请求字符串，从 PHP 5.1.3 开始可用。这个很关键，就是允许你查看请求header

    $response = curl_exec($ch);
    //$info     = curl_getinfo($ch); //获取请求信息

    if($error = curl_error($ch)){
        curl_close($ch);
        return $error;
    }
    curl_close($ch);
    $jsonData  = json_decode($response,true);
    //p($info);
    return ($jsonData === NULL) ? $response : $jsonData;
}

function get_between($input, $start=0, $end) {
    //$data = substr($input, strlen($start)+strpos($input, $start),(strlen($input) - strpos($input, $end))*(-1));
    $start = strlen($start)+strpos($input, $start);
    $data = substr($input,$start,(strlen($input) - strpos($input, $end))*(-1));
    return $data;
}

//歌曲时长
function Duration($times=282){
    $hour   = '';
    $second = $times;
    $minute = intval($second / 60);
    /*if($minute>60){
        $hour  = intval(($minute / 60) / 60).':';
        $minute= ($minute / 60) % 60;
    }*/
    if($minute < 10){
        $minute = "0{$minute}";
    }
    $second = $second % 60;
    if($second < 10){
        $second = "0{$second}";
    }

    $data = $hour.$minute.":".$second;
    return $data;
}

//文件大小
function mp3size($filesize){
    $data = '未知';
    if($filesize){
        $data = round($filesize/1024/1024,2).'Mb';
    }
    return $data;
}

//歌曲详细信息
function  mp3info($hash,$list=false){
    $data= [];
    $url = config('kugou.info');
    $list= $list?:http_get($url.$hash);
    if($list['status']){
        $purl = 'play_url';
        $list = $list['data'];
        $data = array(
            'song_name'  => $list['song_name'],//歌曲名
            'author_name'=> $list['author_name'],//歌手
            'album_name' => $list['album_name'],//专辑名称
            'img'        => $list['img'],//歌手头像
            'authors'    => json_encode($list['authors']),//作者json
            'bitrate'    => $list['bitrate'],//音质
            'author_id'  => $list['author_id'],
        );
        if($data['bitrate'] == 320){
            $purl = 'play_urlhq';
        }elseif($data['bitrate'] > 320){
            $purl = 'play_urlsq';
        }
        $data[$purl]  =  $list['play_url'];

        //歌词
        $list['lyrics']   = explode("[",$list['lyrics']);
        unset($list['lyrics']['0']);
        $lyrics = [];
        foreach($list['lyrics'] as $val){
            $lyrics[] = explode("]",$val);
        }
        $data['lyrics'] = json_encode($lyrics);
    }
    return $data;
}

//歌手名
function authors($authors){
    $data = '未知';
    if(!$authors){
        return $data;
    }
    if(is_array($authors)){
        $data = [];
        foreach ($authors as $v){
            $data[] = $v['author_name'];
        }
        $data = implode('、',$data);
    }else{
        $data = $authors;
    }
    return $data;
}

/**
 * 文件转存
 * @param string $file   文件信息
 * @param string $dir    存储目录
 * @return bool|string
 */
function MediaToDisk($file='',$dir ='mp3'){
    $data = false;
    if(!$file){
        return $data;
    }
    //$dir = str_replace('\\','/',ROOT_PATH.$dir.'/'.date('Ymd'));
    //$dir = str_replace('\\','/',"./Runtime/Data/{$dir}".'/'.date('Ymd'));//阿里云
    $dir = str_replace('\\','/',"./Data/{$dir}".'/'.date('Ymd'));//本地
    if(!is_dir($dir)){
        if(!mkdir($dir, 0755, true)) {
            return false;
        }
    }
    $fileinfo =  pathinfo($file);
    $filename = $dir.'/'.$fileinfo['basename'];
    if(is_file($filename)){
        $data = $filename;
    }else{
        $getdata = http_get($file);
        if($getdata){
            if(file_put_contents($filename,$getdata)){
                $data = $filename;
            }
        }
    }

    return $data?str_replace('./','/',$filename):false;
}

/**
 * 获取歌词
 * @param $keyword      歌曲名
 * @param $hash         key
 * @param $length       时长
 * @param bool $is_arr  返回类型 是否数组
 * @return array|bool|string
 */
function GetLyrics($keyword,$hash,$length,$is_arr=false){
    $url  = config('kugou.lyrics');
    $rands= rand();
    $url  = sprintf($url,urlencode($keyword),$hash,$length,$rands);
    $data = http_curl($url,'',['Host: m.kugou.com','Referer: http://m.kugou.com/','Cookie: kg_mid='.md5($rands).'; musicwo17=kugou','Connection: keep-alive']);
    /*if($data){
        $data = $is_arr?AudioToLyrics($data,true):$data;
    }*/
    return $data;
}

/**
 * 歌词
 *@is_arr  返回类型 true=数组 false=json
 */
function AudioToLyrics($data,$is_arr=false){
    if(!$data || $data=='NULL')return false;
    $arr = explode("[",$data);
    unset($arr['0']);
    $lyrics = [];
    foreach($arr as $val){
        $val = explode("]",$val);
        $val['1'] = rtrim($val['1'],"\r\n");
        $lyrics[]=$val;
        unset($val);
    }
	if($is_arr){
		$lyrics = $lyrics?:'';
	}else{
		$lyrics = $lyrics?json_encode($lyrics,JSON_UNESCAPED_UNICODE):'';
	}
    return $lyrics;
}

// 获取远程文件大小函数
function Getfilesize($url, $user = "", $pw = ""){
    ob_start();
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_NOBODY, 1);

    if(!empty($user) && !empty($pw))
    {
        $headers = array('Authorization: Basic ' .  base64_encode("$user:$pw"));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $ok = curl_exec($ch);
    curl_close($ch);
    $head = ob_get_contents();
    ob_end_clean();

    $regex = '/Content-Length:\s([0-9].+?)\s/';
    $count = preg_match($regex, $head, $matches);

    return isset($matches[1]) ? $matches[1]: "unknown";
}

/**
 * Ajax方式返回数据到客户端
 * @access protected
 * @param mixed  $data 要返回的数据
 * @param String $code 返回状态码
 * @param int $json_option 传递给json_encode的option参数
 * @return void
 */
function ReturnJson($data,$code=1,$url = ''){
    // 返回JSON数据格式到客户端 包含状态信息
    header('Content-Type:application/json; charset=utf-8');
    $data = ['code'=>$code,'data'=>$data,'msg'=>'','url'=>$url];
    if($code != 1){
        $data['msg'] = $data['data'];
        $data['data']= '';
    }
    exit(json_encode($data));
}

//Base64加密
function lm_base64_encode($string) {
    $data = base64_encode($string);
    $data = str_replace(array('+', '/', '='), array('-', '_', ''), $data);
    return $data;
}

//Base64解密
function lm_base64_decode($string) {
    $data = str_replace(array('-', '_'), array('+', '/'), $string);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data.= substr('====', $mod4);
    }
    return base64_decode($data);
}

//PHP加密 js解密
function strencode($string) {
    $string = base64_encode($string);
    $key = md5('just a test');
    $len = strlen($key);
    $code = '';
    for ($i = 0; $i < strlen($string); $i++) {
        $k = $i % $len;
        $code .= $string [$i] ^ $key [$k];
    }
    return base64_encode($code);
}

/**
 *  获取文件修改时间
 * @param $file        文件
 * @param string $type pc电脑 mobile.手机
 * @return string
 */
function modified($file,$type='pc'){
    $view = config('view_replace_str');
    $dir  = array('pc'=>$view['__PUBLIC__'],'mobile'=>$view['__PUBLIC_MOBILE__']);
    //$filedir = (string)$file;
    $filename = $dir[$type].$file;
    if(is_file('.'.$filename)){
        $time = filemtime('.'.$filename);
    }else{
        $time = date('ym');
    }
    return $filename.'?t='.$time;
}

/**
 * 判断是否是微信/手机QQ浏览器访问
 * @param string $url  跳转URL
 * @return bool
 * 注:微信浏览器内核QQ浏览器 关键字段QQBrowser MQQBrowser
 */
function _weixin($url=''){
    //if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'QQBrowser') !== false) {
    if(strpos($_SERVER['HTTP_USER_AGENT'], 'QQBrowser') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false) {
        if($url){
            Header("Location:{$url}");
        }
        return true;
    }
    return false;
}

/**
 * Reads the requested portion of a file and sends its contents to the client with the appropriate headers.
 *
 * This HTTP_RANGE compatible read file function is necessary for allowing streaming media to be skipped around in.
 *
 * @param string $location
 * @param string $filename
 * @param string $mimeType
 * @return void
 *
 * @link https://groups.google.com/d/msg/jplayer/nSM2UmnSKKA/Hu76jDZS4xcJ
 * @link http://php.net/manual/en/function.readfile.php#86244
 */
function smartReadFile($location, $filename, $mimeType = 'application/octet-stream'){
    if (!file_exists($location)){
        header ("HTTP/1.1 404 Not Found");
        return;
    }

    $size	= filesize($location);
    $time	= date('r', filemtime($location));

    $fm		= @fopen($location, 'rb');
    if(!$fm){
        header ("HTTP/1.1 505 Internal server error");
        return;
    }

    $begin	= 0;
    $end	= $size - 1;

    if (isset($_SERVER['HTTP_RANGE'])){
        if (preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches)){
            $begin	= intval($matches[1]);
            if (!empty($matches[2])){
                $end	= intval($matches[2]);
            }
        }
    }

    if (isset($_SERVER['HTTP_RANGE'])) {
        header('HTTP/1.1 206 Partial Content');
    }else{
        header('HTTP/1.1 200 OK');
    }

    header("Content-Type: $mimeType");
    header('Cache-Control: public, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Accept-Ranges: bytes');
    header('Content-Length:' . (($end - $begin) + 1));
    if (isset($_SERVER['HTTP_RANGE'])){
        header("Content-Range: bytes $begin-$end/$size");
    }
    header("Content-Disposition: inline; filename=$filename");
    header("Content-Transfer-Encoding: binary");
    header("Last-Modified: $time");

    $cur	= $begin;
    fseek($fm, $begin, 0);

    while(!feof($fm) && $cur <= $end && (connection_status() == 0)){
        print fread($fm, min(1024 * 16, ($end - $cur) + 1));
        $cur += 1024 * 16;
    }
}

//获取百度应用 access_token
function baidu_token(){
    $token = cache('token:baidu');
    if(!$token){
        $url   = 'https://openapi.baidu.com/oauth/2.0/token';
        $param = 'grant_type=client_credentials&client_id=wXKSFlUvWPFSyEG1fNKCAD62O3xLiRO2&client_secret=IhaRGHgjtTL4zt4tLfHzKvzSlcBklCR4';
        $token = http_curl($url,$param);
        if($token['access_token']){
            cache('token:baidu',$token,$token['expires_in']-5);
        }
    }
    return $token['access_token'];
}

/**
 * 语音合成
 * @param string $str
 * @param int $per 发音人选择, 0为普通女声，1为普通男生，3为情感合成-度逍遥，4为情感合成-度丫丫，默认为普通女声
 * @return bool|mixed
 */
function baidu_audio_tts($str='对不起！网络连接错误。',$per=4){
    header("Content-type: text/html; charset=utf-8");
    $path     = './Data/info';
    $str      = urlencode($str);
    $name     = substr(md5($per.$str),8,8).'_'.$per;
    $pathfile =  $path.'/'.$name.'.m4a';
    if(!is_file($pathfile)){
        $token = baidu_token();
        $url   = 'http://tsn.baidu.com/text2audio?';//文字上传地址
        $url  .= 'tok='.$token.'&ctp=1&per='.$per.'&cuid=admin&lan=zh&tex='.$str;
        if(!is_dir($path)){
            if(!mkdir($path, 0755, true)) {
                return false;
            }
        }

        $getdata = http_curl($url);
        if($getdata){
            file_put_contents($pathfile,$getdata);
        }
    }
    return $pathfile?str_replace('./','/',$pathfile):false;
}

/**
 * 发送邮件
 * @param string $subject  邮件主题
 * @param $toemail         收件人邮箱地址
 * @param $content         内容
 * @return bool|string
 */
function sendEmail($subject='林梦网络官方邮件',$toemail,$content){
    $config = config('mail');
    $mail   = new PHPMailer();
    $mail->isSMTP();                          // 使用SMTP服务
    $mail->CharSet    = "utf8";               // 编码格式为utf8，不设置编码的话，中文会出现乱码
    $mail->Host       = $config['host'];      // 发送方的SMTP服务器地址
    $mail->SMTPAuth   = true;                // 是否使用身份验证
    $mail->Username   = $config['username']; // 发送方邮箱用户名
    $mail->Password   = $config['password']; // 发送方邮箱密码，注意用163邮箱这里填写的是“客户端授权密码”而不是邮箱的登录密码
    $mail->SMTPSecure = "ssl";               // 使用ssl协议方式</span><span style="color:#333333;">
    $mail->Port       = $config['port'];     // 163邮箱的ssl协议方式端口号是465/994
    $mail->setFrom($config['setfrom'],$config['info']); // 设置发件人信息，如邮件格式说明中的发件人
    $mail->addAddress($toemail,$config['info']);        // 设置收件人信息，如邮件格式说明中的收件人，这里会显示为Liang(yyyy@163.com)
    $mail->addReplyTo($config['setfrom'],"Reply");      // 设置回复人信息，指的是收件人收到邮件后，如果要回复，回复邮件将发送到的邮箱地址
    //$mail->addCC("1415336788@qq.com");// 设置邮件抄送人，可以只写地址，上述的设置也可以只写地址(这个人也能收到邮件)
    //$mail->addBCC("xxx@163.com");// 设置秘密抄送人(这个人也能收到邮件)
    //$mail->addAttachment("bug0.jpg");// 添加附件
    $mail->Subject = $subject;   // 邮件标题
    $mail->Body    = $content;   // 邮件正文
    $mail->AltBody = $content;   //这个是设置纯文本方式显示的正文内容，如果不支持Html方式，就会用到这个，基本无用
    //p($mail);die;
    if(!$mail->send()){
        return $mail->ErrorInfo;
    }else{
        return true;
    }
}

//清楚所有空格
function trims($str){
    /*$str  = trim($str);
    $data = str_replace(' ','',$str);*/
    $data = preg_replace('/[ ]/', '', $str);
    return $data;
}

//密码
function UserPwd($input,$pwd=null){
    if(!$input) return false;
    $input   = md5($input);
    $strpwd  = substr($input,4,12);
    $strpwd .= substr($input,20,-12);
    $data    = md5($strpwd);
    if($pwd){
        if($data == $pwd){
            $data = true;
        }else{
            $data = false;
        }
    }
    return $data;
}

/*
 * QQ登录数据转换
 * callback( {"client_id":"YOUR_APPID","openid":"YOUR_OPENID"} )
 * return  array
 */
function QQcalldata($data){
    if(empty($data))return false;
    $lpos = strpos($data, "(");
    $rpos = strrpos($data, ")");
    $data = substr($data, $lpos + 1, $rpos - $lpos -1);
    $data = json_decode($data,true);
    return $data;
}

//过滤所有HTML标签
function clear_html_label($html){
    $search = array ("'<script[^>]*?>.*?</script>'si", // 去掉 javascript
        "'<[\/\!]*?[^<>]*?>'si", // 去掉 HTML 标记
        "'([\r\n])[\s]+'", // 去掉空白字符
        "'&(quot|#34);'i", // 替换 HTML 实体
        "'&(amp|#38);'i",
        "'&(lt|#60);'i",
        "'&(gt|#62);'i",
        "'&(nbsp|#160);'i"
    );
    $replace = array ("","","\\1","\"","&","<",">"," ");
    $html = preg_replace($search, $replace, $html);
    return $html;
}

/**
 * 截取字符串
 * @param $str          数据来源
 * @param int $length   截取长度
 * @param int $start    开始位置
 * @return string
 */
function substr_form($str,$length=10,$start=0){
    $length = $length*2;
    if(mb_strwidth($str, 'utf8') > $length){
        $str = mb_strimwidth($str, $start,$length+3, '...', 'utf8');
    }
    return $str;
}

/**
 * 获取拼音信息
 * @param     string  $str  字符串
 * @param     int  $ishead  是否为首字母
 * @param     int  $isclose  解析后是否释放资源
 * @return    string
 */
function pinyin($str,$ishead=0,$isclose=1){
    $pinyins = [];
    $restr = '';
    $str   = iconv( "UTF-8", "gb2312//IGNORE",$str);//UTF8转gb2312
    $str   = trim($str);
    $slen  = strlen($str);

    if(count($pinyins) == 0){
        $fp = fopen(ROOT_PATH.'public/static/pinyin.dat', 'r');
        while(!feof($fp)){
            $line = trim(fgets($fp));
            $pinyins[$line[0].$line[1]] = substr($line, 3, strlen($line)-3);
        }
        fclose($fp);
    }
    for($i=0; $i<$slen; $i++){
        if(ord($str[$i])>0x80){
            $c = $str[$i].$str[$i+1];
            $i++;
            if(isset($pinyins[$c])){
                if($ishead==0){
                    $restr .= $pinyins[$c];
                }else{
                    $restr .= $pinyins[$c][0];
                }
            }else{
                $restr .= "_";
            }
        }elseif(preg_match("/[a-z0-9]/i", $str[$i])){
            $restr .= $str[$i];
        }else{
            $restr .= "_";
        }
    }
    if($isclose==1){
        unset($pinyins);
    }
    return $restr;
}

//管理员登陆信息
function GetMinfo($field=null){
   $model = new \app\common\model\Common();
   $data  = $model->GetaAminInfo($field);
    return $data;
}

//渠道info
function site_name($id=null){
    $model = new \app\admin\model\Site();
    $list  = $model->GetList();
    if(is_null($id)){
        return $list;
    }
    return $id ? $list[$id]['title'] :'暂无';
}

//显示渠道名称
function ShowSiteName($siteid){
    $sitelist = explode(",",$siteid);
    $sitename = '';
    foreach($sitelist as $t){
        if($t){
            $sitename .= site_name($t).",";
        }
    }
    return  rtrim($sitename,',');
}

//支付类型
function ShowPayment($type=0){
    $data = ['微信','支付宝','余额','其他'];
    return $data[$type];
}

/**
 * 对象转换成数组
 * @param $obj
 */
function objToArray($obj){
    return json_decode(json_encode($obj), true);
}

//游戏待选数据
function game_option($id=null){
    $data = cache('gamelist');
    if($data==false){
        $list = Db::table('gm_game_list')->field(['id','gameid','name'])->order('id desc')->select();
        if($list){
            foreach ($list as $v){
                $data[$v['gameid']]=$v;
            }
            cache('gamelist',$data,86400);
        }
    }
    return $id ? $data[$id] : $data;
}

//游戏ID 模版显示
function game_html($id,$field='name'){
    $data = game_option($id);
    return $data?$data[$field]:false;
}

/**
 * 分类
 * @param null $id     分类ID
 * @param null $field  返回字段
 * @return mixed
 */
function cate_option($id=null,$field=null){
    $data = model('Cate')->CateData($id);
    if($data){
        if($field){
            $data = $data[$field];
        }
    }
    return $data;
}

function  cateAll($id=0){
    $list = cate_option();
    if($list && is_array($list)){
        foreach ($list as $k=>$v){

        }
    }

    return $list;
}

function IkType($type){
	$data = array('6'=>'劲爆DJ舞曲','5'=>'最新慢摇嗨曲','25'=>'最新DJ舞曲');
	return $data[$type];
}

/**
 * 把jsonp转为php数组
 * @param string $jsonp jsonp字符串
 * @param boolean $assoc 当该参数为true时，将返回array而非object
 * @return array
 */
function jsonp_decode($jsonp, $assoc = false){
    if(!$jsonp){
        return false;
    }
	$jsonp = trim($jsonp);
	if(isset($jsonp[0]) && $jsonp[0] !== '[' && $jsonp[0] !== '{') {
		$begin = strpos($jsonp, '(');
		if(false !== $begin){
			$end = strrpos($jsonp, ')');
			if(false !== $end){
				$jsonp = substr($jsonp, $begin + 1, $end - $begin - 1);
			}
		}
	}
	return json_decode($jsonp, $assoc);
}

//微妙时间戳
function GetTime(){
	list($msec, $sec) = explode(' ', microtime());
	$msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
	return $msectimes = substr($msectime,0,13);
}

/**
 * 系统动态配置
 * @param bool $name 配置名称
 * @return mixed
 */
function sysconfig($name=false){
    $data = cache('sysconfig');
    if(!$data){
        $list = Db::name('sysconfig')->field(['name','value'])->where(['status'=>1])->order('id asc')->select();
        if($list){
            foreach($list as $v){
                $data[$v['name']]= $v['value'];
            }
            cache('sysconfig',$data,86400);
        }
    }
    if($name){
        if(array_key_exists($name,$data)){
            $data = $data[$name];
        }else{
            $data = false;
        }
    }
    return $data;
}

//第三方播放key
function ik123_vsid($type=false){
    //return 'ea90caa813fa42d80bf780b2f3883c74';
	//return '9c4a95c8f3033c0d5a12cf53660af0b1';
	if($type==false){
		return '#vsid#';
	}
    
    $vsid = cache('ik123_vsid');
    if(empty($vsid)){
        $head= array(
            'Referer:http://www.ik123.com/',
            'Host:fv.ik123.com',
            'User-Agent:Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.80 Safari/537.36',
            //'Cookie:virtualwall=vsid=9c4a95c8f3033c0d5a12cf53660af0b1',
            'Upgrade-Insecure-Requests:1',
        );
        $res = http_curl('http://fv.ik123.com/_sys_vw.vhtml?js=yes','',$head);
        if($res){
            $vsid  = str_replace(array('varVW_VSID="','";'),'',trims($res));
            cache('ik123_vsid',$vsid,86400);
        }else{
            $vsid = sysconfig('vsid');
        }
    }
    return $vsid;
    //return 'cbb619367773ec48f7ffdec6bf1affb0';
}

//IK123播放key
function token_ik123($data=false,$isget=false){
	//http://www.ik123.com/ik/a/?201710%2Fik123_11252.mp4
	if($data){
		//cache('token:ik123',$data,7200);
		cookie('vsid',$data,7200);
		$vsid = $data;
	}else{
		//$vsid = cache('token:ik123');
		$vsid = cookie('vsid');
		if(empty($vsid) && $isget){
			$vsid = ik123_vsid(true);
		}
	}
	
	return $vsid;
}

//播放错误
function OnPlayError($str='非常抱歉!您播放的音乐正在赶来的路上,请稍候.',$per=4){
    $name= input('name');
    $mp3 = baidu_audio_tts($str,$per);
    $path= config('view_replace_str.__PUBLIC__');
    $data= ['m4a'=>$mp3,'mp3'=>$mp3,'title'=>$str,'img'=>$path.'/images/play/logo.png','song_name'=>$name?:'音乐正在赶来的路上','authors'=>'懂我音乐','audio_name'=>'懂我音乐'];
    return $data;
}

/**
 * Ajax方式返回数据到客户端
 * @access protected
 * @param mixed  $data 要返回的数据
 * @param String $code 返回状态码
 * @param int $json_option 传递给json_encode的option参数
 * @return void
 */
function ApiReturnData($data,$code=1,$url = ''){
    header('Content-Type:application/json; charset=utf-8');
    $data = ['code'=>$code,'data'=>$data,'msg'=>'','url'=>$url];
    if($code != 1){
        $data['msg'] = $data['data'];
        $data['data']= [];
    }
    exit(json_encode($data));
}

/**
 * 检测URL是否正确
 * @param $url
 * @return bool
 */
function check_url($url){
    if(preg_match('/(http|https):\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/is',$url)){
        return true;
    }
    return false;
}

/**
 * 写入、读取扩展配置
 * @param $name          [文件名] config
 * @param string $value  [配置内容数组]
 * @param string $path   [保存路径] extra:扩展路径
 * @return array|bool|int|mixed|string
 */
function F($name, $value='', $path='') {
    static $_cache = array();
    $path           = $path ? APP_PATH.DS.$path.DS : APP_PATH.DS;
    $filename       = $path.$name.'.php';
    if ('' !== $value) {
        if (is_null($value)) {
            // 删除缓存
            return false !== strpos($name,'*')?array_map("unlink", glob($filename)):unlink($filename);
        } else {
            // 缓存数据
            $dir            =   dirname($filename);
            // 目录不存在则创建
            if(!is_dir($dir)){
                mkdir($dir,0755,true);
            }
            $_cache[$name]  =   $value;
            return file_put_contents($filename, strip_whitespace("<?php\treturn " . var_export($value, true) . ";\t\n?>"));
        }
    }
    if (isset($_cache[$name]))
        return $_cache[$name];
    // 获取缓存数据
    if (is_file($filename)) {
        $value          =   include $filename;
        $_cache[$name]  =   $value;
    } else {
        $value          =   false;
    }
    return $value;
}

/**
 * 去除代码中的空白和注释
 * @param string $content 代码内容
 * @return string
 */
function strip_whitespace($content) {
    $stripStr   = '';
    //分析php源码
    $tokens     = token_get_all($content);
    $last_space = false;
    for ($i = 0, $j = count($tokens); $i < $j; $i++) {
        if (is_string($tokens[$i])) {
            $last_space = false;
            $stripStr  .= $tokens[$i];
        } else {
            switch ($tokens[$i][0]) {
                //过滤各种PHP注释
                case T_COMMENT:
                case T_DOC_COMMENT:
                    break;
                //过滤空格
                case T_WHITESPACE:
                    if (!$last_space) {
                        $stripStr  .= ' ';
                        $last_space = true;
                    }
                    break;
                case T_START_HEREDOC:
                    $stripStr .= "<<<THINK\n";
                    break;
                case T_END_HEREDOC:
                    $stripStr .= "THINK;\n";
                    for($k = $i+1; $k < $j; $k++) {
                        if(is_string($tokens[$k]) && $tokens[$k] == ';') {
                            $i = $k;
                            break;
                        } else if($tokens[$k][0] == T_CLOSE_TAG) {
                            break;
                        }
                    }
                    break;
                default:
                    $last_space = false;
                    $stripStr  .= $tokens[$i][1];
            }
        }
    }
    return $stripStr;
}

/**
 * 多维数组排序
 * @param $data
 * @param string $sort     排序顺序标志 DESC. 降序 ASC.升序
 * @param string $field    排序字段
 * @return mixed
 */
function array_sort_key($data,$sort='DESC',$field='score'){
    $sortkey = array('direction' =>'SORT_'.strtoupper($sort),'field'=> $field);
    $arrSort = array();
    if(is_array($data)){
        foreach($data as $k => $v){
            foreach($v as $key=>$value){
                $arrSort[$key][$k] = $value;
            }
        }
        if($sortkey['direction']){
            array_multisort($arrSort[$sortkey['field']], constant($sortkey['direction']), $data);
        }
    }
    return $data;
}