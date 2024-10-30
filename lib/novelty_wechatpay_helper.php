<?php 

require_once("novelty_wechatpay_core.function.php");

class NoveltyWechatPayHepler{
	
	private $payment_gateway_url = 'https://paymentgateway.noveltypay.com/api/posservice/onlinepay/?';
	private $query_gateway_url = 'https://paymentgateway.noveltypay.com/api/posservice/onlinequery/?';

	public function __construct(){

	}

	public function sign($para,$key){
		if(empty($para) || $key == ''){
			return '';
		}

		$param = argSort_novelty_wechat($para);

		$linkstr = createLinkstring_novelty_wechat($param);

		$linkstr = $linkstr . '&key=' . $key;

		return strtoupper(md5($linkstr));

	}

	public function getPaymentUrl(
		$merchnum,$ordernum,$total_fee,
		$callback_url,$notify_url,
		$nonce_str,$subject,$body,$secure_key){

		$payment_args = array(
			"merchnum"  => $merchnum,
			"ordernum"  => $ordernum,
			"total_fee" => $total_fee,
			"paymodeid" => "20",
			"callback_url" => $callback_url,
			"notify_url" => $notify_url,
			"nonce_str" => $nonce_str,
			"subject"   => $subject,
			"body"		=> $body,
			"sign_type" => "MD5"
		);

		$sign = $this->sign($payment_args,$secure_key);

		$payment_args = array_merge($payment_args,array("sign"=>$sign));

		return $this->payment_gateway_url . createLinkstring_novelty_wechat($payment_args);
	}

	public function getQueryUrl($merchnum,$ordernum,$nonce_str,$secure_key){

		$payment_args = array(
			"merchnum"  => $merchnum,
			"ordernum"  => $ordernum,
			"paymodeid" => "20",
			"nonce_str" => $nonce_str,
			"sign_type" => "MD5"
		);

		$sign = $this->sign($payment_args,$secure_key);

		$payment_args = array_merge($payment_args,array("sign"=>$sign));

		return $this->query_gateway_url . createLinkstring_novelty_wechat($payment_args);
	}
	
}

?>