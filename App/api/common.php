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
// | Date: 2018/11/17
// +----------------------------------------------------------------------

//API访问令牌
function ApiAccessToken(){
	$data = array('code'=>0,'msg'=>'');
	$key   = input('key');
    $key   = trims($key);
	if(empty($key)){
		$data['msg'] = 'Resource expiration or permanent transfer';
		return $data;
	}

	$token = config('musictoken');
	if($token == $key){
		$data['code'] = 1;
		return $data;
	}

	$nowtime   = time();
	$keys      = 'token:'.substr(md5($key),5,8);
	$usertoken = cache($keys);
	if(!$usertoken){
		$usertoken = db('usertoken')->field(['token','endtime'])->where(['token'=>$key,'status'=>1])->find();
		if($usertoken){
			$endtime = $nowtime-$usertoken['endtime'];
			cache($keys,$usertoken,$endtime);
			$data['code']= 1;
		}else{
			$data['msg'] = '无效的密钥，请联系管理员！QQ:1415336788,[api.lmwljz.com]';
            return $data;
		}
	}

	if($usertoken['endtime'] < $nowtime){
		$data['code']= 0;
		$data['msg'] = '密钥已失效，请续费！QQ:1415336788,[api.lmwljz.com]';
	}
	return $data;
}

/**
 * 获取客户端类型，手机还是电脑，以及相应的操作系统类型。
 *
 * @param string $subject
 */
function get_os($agent) {
    $os = false;

    if (preg_match ( '/win/i', $agent ) && strpos ( $agent, '95' )) {
        $os = 'Windows 95';
    } else if (preg_match ( '/win 9x/i', $agent ) && strpos ( $agent, '4.90' )) {
        $os = 'Windows ME';
    } else if (preg_match ( '/win/i', $agent ) && preg_match ( '/98/i', $agent )) {
        $os = 'Windows 98';
    } else if (preg_match ( '/win/i', $agent ) && preg_match ( '/nt 6.0/i', $agent )) {
        $os = 'Windows Vista';
    } else if (preg_match ( '/win/i', $agent ) && preg_match ( '/nt 6.1/i', $agent )) {
        $os = 'Windows 7';
    } else if (preg_match ( '/win/i', $agent ) && preg_match ( '/nt 6.2/i', $agent )) {
        $os = 'Windows 8';
    } else if (preg_match ( '/win/i', $agent ) && preg_match ( '/nt 10.0/i', $agent )) {
        $os = 'Windows 10'; // 添加win10判断
    } else if (preg_match ( '/win/i', $agent ) && preg_match ( '/nt 5.1/i', $agent )) {
        $os = 'Windows XP';
    } else if (preg_match ( '/win/i', $agent ) && preg_match ( '/nt 5/i', $agent )) {
        $os = 'Windows 2000';
    } else if (preg_match ( '/win/i', $agent ) && preg_match ( '/nt/i', $agent )) {
        $os = 'Windows NT';
    } else if (preg_match ( '/win/i', $agent ) && preg_match ( '/32/i', $agent )) {
        $os = 'Windows 32';
    } else if (preg_match ( '/linux/i', $agent )) {
        if(preg_match("/Mobile/", $agent)){
            if(preg_match("/QQ/i", $agent)){
                $os = "Android QQ Browser";
            }else{
                $os = "Android Browser";
            }
        }else{
            $os = 'PC-Linux';
        }
    } else if (preg_match ( '/Mac/i', $agent )) {
        if(preg_match("/Mobile/", $agent)){
            if(preg_match("/QQ/i", $agent)){
                $os = "IPhone QQ Browser";
            }else{
                $os = "IPhone Browser";
            }
        }else{
            $os = 'Mac OS X';
        }
    } else if (preg_match ( '/unix/i', $agent )) {
        $os = 'Unix';
    } else if (preg_match ( '/sun/i', $agent ) && preg_match ( '/os/i', $agent )) {
        $os = 'SunOS';
    } else if (preg_match ( '/ibm/i', $agent ) && preg_match ( '/os/i', $agent )) {
        $os = 'IBM OS/2';
    } else if (preg_match ( '/Mac/i', $agent ) && preg_match ( '/PC/i', $agent )) {
        $os = 'Macintosh';
    } else if (preg_match ( '/PowerPC/i', $agent )) {
        $os = 'PowerPC';
    } else if (preg_match ( '/AIX/i', $agent )) {
        $os = 'AIX';
    } else if (preg_match ( '/HPUX/i', $agent )) {
        $os = 'HPUX';
    } else if (preg_match ( '/NetBSD/i', $agent )) {
        $os = 'NetBSD';
    } else if (preg_match ( '/BSD/i', $agent )) {
        $os = 'BSD';
    } else if (preg_match ( '/OSF1/i', $agent )) {
        $os = 'OSF1';
    } else if (preg_match ( '/IRIX/i', $agent )) {
        $os = 'IRIX';
    } else if (preg_match ( '/FreeBSD/i', $agent )) {
        $os = 'FreeBSD';
    } else if (preg_match ( '/teleport/i', $agent )) {
        $os = 'teleport';
    } else if (preg_match ( '/flashget/i', $agent )) {
        $os = 'flashget';
    } else if (preg_match ( '/webzip/i', $agent )) {
        $os = 'webzip';
    } else if (preg_match ( '/offline/i', $agent )) {
        $os = 'offline';
    } else {
        $os = '未知操作系统';
    }
    return $os;
}

/**
 * 获取 客户端的浏览器类型
 * @return string
 */
function get_broswer($sys){
    if (stripos($sys, "Firefox/") > 0) {
        preg_match("/Firefox\/([^;)]+)+/i", $sys, $b);
        $exp[0] = "Firefox";
        $exp[1] = $b[1];  //获取火狐浏览器的版本号
    } elseif (stripos($sys, "Maxthon") > 0) {
        preg_match("/Maxthon\/([\d\.]+)/", $sys, $aoyou);
        $exp[0] = "傲游";
        $exp[1] = $aoyou[1];
    } elseif (stripos($sys, "MSIE") > 0) {
        preg_match("/MSIE\s+([^;)]+)+/i", $sys, $ie);
        $exp[0] = "IE";
        $exp[1] = $ie[1];  //获取IE的版本号
    } elseif (stripos($sys, "OPR") > 0) {
        preg_match("/OPR\/([\d\.]+)/", $sys, $opera);
        $exp[0] = "Opera";
        $exp[1] = $opera[1];
    } elseif(stripos($sys, "Edge") > 0) {
        //win10 Edge浏览器 添加了chrome内核标记 在判断Chrome之前匹配
        preg_match("/Edge\/([\d\.]+)/", $sys, $Edge);
        $exp[0] = "Edge";
        $exp[1] = $Edge[1];
    } elseif (stripos($sys, "Chrome") > 0) {
        preg_match("/Chrome\/([\d\.]+)/", $sys, $google);
        $exp[0] = "Chrome";
        $exp[1] = $google[1];  //获取google chrome的版本号
    } elseif(stripos($sys,'rv:')>0 && stripos($sys,'Gecko')>0){
        preg_match("/rv:([\d\.]+)/", $sys, $IE);
        $exp[0] = "IE";
        $exp[1] = $IE[1];
    }else {
        $exp[0] = "未知浏览器";
        $exp[1] = "";
    }
    return $exp[0].'('.$exp[1].')';
}

/**
 * 根据 客户端IP 获取到其具体的位置信息
 * @param unknown $ip
 * @return string
 */
function get_address_by_ip($ip) {
    $url = "http://ip.taobao.com/service/getIpInfo.php?ip=".$ip;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $info = curl_exec($curl);
    curl_close($curl);
    return $info;
}

function clientlog() {
    $useragent = $_SERVER ['HTTP_USER_AGENT'];
    $clientip = $_SERVER ['REMOTE_ADDR'];

    $client_info = get_os ( $useragent ) . "---" . get_broswer ( $useragent );

    /*$rawdata_position = get_address_by_ip ( $clientip );

    $rawdata_position = json_decode($rawdata_position, true);
    $country = $rawdata_position['data']['country'];
    $province = $rawdata_position['data']['region'];
    $city = $rawdata_position['data']['city'];
    $nettype = $rawdata_position['data']['isp'];
*/

    $time = date ( 'Y-m-d H:m:s' );
//    $param= http_build_query(request()->param());
//    $url = url('').'?'.$param;
    $request = \think\Request::instance();
    $url = $request->url(true);

    //$data = "来自{$country} {$province} {$city }{$nettype} 的客户端: {$client_info},IP为:{$clientip},在{$time}时刻访问了{$_SERVER['PHP_SELF']}文件！\r\n";
    //$data = "[{$time}] {$url} IP:{$clientip}，客户端: {$client_info}，来自{$country} {$province} {$city }{$nettype}\r\n";
    $data = "[{$time}] {$url} IP:{$clientip}，客户端: {$client_info}，来自未知\r\n";

    $filename = RUNTIME_PATH."clientlog.log";
    if (! file_exists ( $filename )) {
        fopen ( $filename, "w+" );
    }
    file_put_contents ( $filename, $data, FILE_APPEND );
}

function liuliangtongji(){
    $file = RUNTIME_PATH."tongji.db";
    if (! file_exists ( $file )) {
        fopen ($file, "w+" );
    }
    $fp = fopen($file, 'r+');
    $content = '';
    if (flock($fp, LOCK_EX)) {
        while (($buffer = fgets($fp, 1024)) != false) {
            $content = $content . $buffer;
        }
        $data = unserialize($content);

        //设置记录键值
        $total = 'total';
        $month = date('Ym');
        $today = date('Ymd');
        $yesterday = date('Ymd', strtotime("-1 day"));
        $tongji = array();
        // 总访问增加
        $tongji[$total] = $data[$total] + 1;
        // 本月访问量增加
        $tongji[$month] = $data[$month] + 1;
        // 今日访问增加
        $tongji[$today] = $data[$today] + 1;
        //保持昨天访问
        $tongji[$yesterday] = $data[$yesterday];

        //保存统计数据
        ftruncate($fp, 0); // 将文件截断到给定的长度
        rewind($fp); // 倒回文件指针的位置
        fwrite($fp, serialize($tongji));
        flock($fp, LOCK_UN);
        fclose($fp);

        //输出数据
        $total = $tongji[$total];
        $month = $tongji[$month];
        $today = $tongji[$today];
        $yesterday = $tongji[$yesterday] ? $tongji[$yesterday] : 0;
        //echo "document.write('访总问:{$total}, 本月:{$month}, 昨日:{$yesterday}, 今日:{$today}')";
        return "访总问:{$total}, 本月:{$month}, 昨日:{$yesterday}, 今日:{$today}";
    }
}
