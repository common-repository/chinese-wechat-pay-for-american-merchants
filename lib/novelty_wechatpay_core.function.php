<?php
/* *
 * 接口公用函数
 * 详细：该类是请求、通知返回两个文件所调用的公用函数核心处理文件
 */

/**
 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
 * @param $para 需要拼接的数组
 * return 拼接完成以后的字符串
 */
function createLinkstring_novelty_wechat($para) {
	$arg  = "";
	//while (list ($key, $val) = each ($para)) {
	foreach($para as $key => $val){
		$arg.=$key."=".$val."&";
	}
	//去掉最后一个&字符
	$arg = substr($arg,0,count(array($arg))-2);
	
	return $arg;
}
/**
 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
 * @param $para 需要拼接的数组
 * return 拼接完成以后的字符串
 */
function createLinkstringUrlencode_novelty_wechat($para) {
	$arg  = "";
	while (list ($key, $val) = each ($para)) {
		$arg.=$key."=".urlencode($val)."&";
	}
	//去掉最后一个&字符
	$arg = substr($arg,0,count($arg)-2);
	
	return $arg;
}
/**
 * 除去数组中的空值和签名参数
 * @param $para 签名参数组
 * return 去掉空值与签名参数后的新签名参数组
 */
function paraFilter_novelty_wechat($para) {
	$para_filter = array();
	while (list ($key, $val) = each ($para)) {
		if($key == "sign" || $key == "sign_type" || $val == "")continue;
		else	$para_filter[$key] = $para[$key];
	}
	return $para_filter;
}
/**
 * 对数组排序
 * @param $para 排序前的数组
 * return 排序后的数组
 */
function argSort_novelty_wechat($para) {
	ksort($para);
	reset($para);
	return $para;
}

/**
 * 写日志，方便测试（看网站需求，也可以改成把记录存入数据库）
 * 注意：服务器需要开通fopen配置
 * @param $word 要写入日志里的文本内容 默认值：空值
 */
function logResult_novelty_wechat($word='') {
	$log =  new WC_Logger();
	$log->add('wechatpay', $word);
}

	/**
	 * 
	 * 产生随机字符串，不长于32位
	 * @param int $length
	 * @return 产生的随机字符串
	 */
	function getNonceStr_novelty_wechat($length = 32) 
	{
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {  
			$str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
		} 
		return $str;
	}

/**
 * 远程获取数据，POST模式
 * 注意：
 * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
 * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
 * @param $url 指定URL完整路径地址
 * @param $cacert_url 指定当前工作目录绝对路径
 * @param $para 请求的数据
 * @param $input_charset 编码格式。默认值：空值
 * return 远程输出的数据
 */
function getHttpResponsePOST_novelty_wechat($url, $cacert_url, $para, $input_charset = '') {

	$header = array();
	if (trim($input_charset) != '') {
		$header = array('Content-Type' => 'application/application/x-www-form-urlencoded; charset='.$input_charset);
	}
	$responseText = wp_remote_post( $url, array(
							'method'      => 'POST',
							'timeout'     => 45,
							'redirection' => 5,
							'httpversion' => '1.0',
							'sslverify' 	=> true,
							'sslcertificates' => $cacert_url,
							'blocking'    => true,
							'headers'     => $header,
							'body'        => $para,
							'cookies'     => array()
							)
						);
	return $responseText;
}

/**
 * 远程获取数据，GET模式
 * 注意：
 * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
 * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
 * @param $url 指定URL完整路径地址
 * @param $cacert_url 指定当前工作目录绝对路径
 * return 远程输出的数据
 */
function getHttpResponseGET_novelty_wechat($url,$cacert_url) {
	$responseText = wp_remote_get( $url, array(
							'method'      => 'GET',
							'timeout'     => 45,
							'redirection' => 5,
							'httpversion' => '1.0',
							'sslverify' 	=> true,
							'sslcertificates' => $cacert_url,
							'blocking'    => true,
							'headers'     => array(),
							'body'        => null,
							'cookies'     => array()
							)
						);
	return $responseText;
}

function getUrlContentGET_novelty_wechat($url){
  $responseText = wp_remote_get( $url, array(
							'method'      => 'GET',
							'timeout'     => 45,
							'redirection' => 5,
							'httpversion' => '1.0',
							'sslverify' 	=> false,
							'blocking'    => true,
							'user-agent' => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)',
							'headers'     => array(),
							'body'        => null,
							'cookies'     => array()
							)
						);
	$response_code = wp_remote_retrieve_response_code( $responseText );
	$responseText = wp_remote_retrieve_body($responseText);
	return ($response_code>=200 && $response_code<300) ? $responseText : false;
 }

/**
 * 实现多种字符编码方式
 * @param $input 需要编码的字符串
 * @param $_output_charset 输出的编码格式
 * @param $_input_charset 输入的编码格式
 * return 编码后的字符串
 */
function charsetEncode_novelty_wechat($input,$_output_charset ,$_input_charset) {
	$output = "";
	if(!isset($_output_charset) )$_output_charset  = $_input_charset;
	if($_input_charset == $_output_charset || $input ==null ) {
		$output = $input;
	} elseif (function_exists("mb_convert_encoding")) {
		$output = mb_convert_encoding($input,$_output_charset,$_input_charset);
	} elseif(function_exists("iconv")) {
		$output = iconv($_input_charset,$_output_charset,$input);
	} else die("sorry, you have no libs support for charset change.");
	return $output;
}
/**
 * 实现多种字符解码方式
 * @param $input 需要解码的字符串
 * @param $_output_charset 输出的解码格式
 * @param $_input_charset 输入的解码格式
 * return 解码后的字符串
 */
function charsetDecode_novelty_wechat($input,$_input_charset ,$_output_charset) {
	$output = "";
	if(!isset($_input_charset) )$_input_charset  = $_input_charset ;
	if($_input_charset == $_output_charset || $input ==null ) {
		$output = $input;
	} elseif (function_exists("mb_convert_encoding")) {
		$output = mb_convert_encoding($input,$_output_charset,$_input_charset);
	} elseif(function_exists("iconv")) {
		$output = iconv($_input_charset,$_output_charset,$input);
	} else die("sorry, you have no libs support for charset changes.");
	return $output;
}
?>