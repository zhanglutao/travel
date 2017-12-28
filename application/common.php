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

/**
 * curlPOst
 * @param unknown $url
 * @param unknown $send_data
 * @return unknown
 */
function curlPost($url,$send_data,$header=array(),$method='POST',$timeout=30) {
    $default_header = array(

    );
    $header = array_merge($default_header,$header);
    //$send_data =  json_encode($send_data);
    $ch = curl_init(); //启动一个curl会话
    curl_setopt($ch, CURLOPT_URL, $url); //要访问的地址
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //设置HTTP头字段的数组
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); //设置超时限制防止死循环
    curl_setopt($ch, CURLOPT_POSTFIELDS,$send_data); //Post提交的数据包
    curl_setopt($ch, CURLOPT_HEADER, 0); //显示返回的Header区域内容
    switch ($method){
        case "GET" :
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            break;
        case "POST":
            curl_setopt($ch, CURLOPT_POST,true);
            break;
        case "PUT" :
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            break;
        case "DELETE":
            curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            break;
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //获取的信息以文件流的形式返回
    $result = curl_exec($ch); //执行一个curl会话
    if (curl_errno($ch)) {
        echo 'Errno'.curl_error($ch);
        dump(curl_errno($ch));
        // return false;
    }
    curl_close($ch); //关闭curl
    //$this->log->info('请求地址：'.$url."\n请求头：".json_encode($header_json)."\n请求体：".$send_data."\n接口返回：".$result);
    return json_decode($result,true);
}

function vpost($url,$data=''){ // 模拟提交数据函数
    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
    curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
    //curl_setopt($curl, CURLOPT_COOKIEFILE, $GLOBALS['cookie_file']); // 读取上面所储存的Cookie信息
    curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
    $tmpInfo = curl_exec($curl); // 执行操作
    if (curl_errno($curl)) {
        // echo 'Errno'.curl_error($curl);
        return false;
    }
    curl_close($curl); // 关键CURL会话
    return $tmpInfo; // 返回数据

}