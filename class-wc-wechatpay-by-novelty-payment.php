<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once('lib/novelty_wechatpay_helper.php');

/**
 * Chinese WeChat Pay for American merchants(微信支付美国版)
 *
 * Provides WeChat Gateway
 *
 * @class       WC_WeChat_By_Novelty_Payment_Gateway
 * @extends     WC_Payment_Gateway
 * @version     1.0.0
 */
class WC_WechatPay_By_Novelty_Payment_Gateway extends WC_Payment_Gateway{

	public function __construct() {
		$this->id 					= WC_NOVELTY_PAYMENT_WECHATPAY_ID;
		$this->icon 				= WC_NOVELTY_PAYMENT_WECHATPAY_URL. '/images/logo.png';
		$this->has_fields 			= false;
		$this->notify_url 			= WC()->api_request_url( $this->id );
		
		$this->method_title 		= 'Chinese WeChat Pay for American merchants(微信支付美国版)'; // checkout option title
	    $this->method_description 	= 'Pay in CNY by WeChat Pay for goods and services in USD';

	    $this -> init_form_fields();
      	$this -> init_settings();

      	$this->title                  = $this->get_option( 'title' );
        $this->description            = $this->get_option( 'description' );
        $this->merch_id               = $this->get_option( 'merch_id' );
        $this->secure_key             = $this->get_option( 'secure_key' );

        $this->debug                  = $this->get_option( 'debug' );

        // Logs
        if ( 'yes' == $this->debug ) {
            $this->log = new WC_Logger();
        }

        // Actions
        //add_action( 'admin_notices', array( $this, 'requirement_checks' ) );        
        add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) ); // WC <= 1.6.6
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) ); // WC >= 2.0
        //add_action( 'woocommerce_thankyou_'. $this->id, array( $this, 'thankyou_page' ) );
        add_action( 'woocommerce_receipt_'. $this->id, array( $this, 'receipt_page' ) );
        // Payment listener/API hook
        add_action( 'woocommerce_api_'. $this->id, array( $this, 'check_wechatpay_response' ) );


    }


    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields() {

        $this->form_fields = array(
            'enabled' => array(
                'title'     => __('Enable/Disable', 'wechatpay'),
                'type'      => 'checkbox',
                'label'     => __('Enable WeChat Pay By Novelty Payments', 'wechatpay'),
                'default'   => 'no'
            ),
            'title' => array(
                'title'       => __('Title', 'wechatpay'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'wechatpay'),
                'default'     => __('Chinese WeChat Pay for American merchants(微信支付美国版)', 'wechatpay'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Description', 'wechatpay'),
                'type'        => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'wechatpay'),
                'default'     => __('Pay in CNY by WeChat Pay for goods and services in USD', 'wechatpay'),
                'desc_tip'    => true,
            ),
            'merch_id' => array(
                'title'       => __('Merchant ID', 'wechatpay'),
                'type'        => 'text',
                'description' => __('Please enter the merchant ID<br />If you don\'t have it, contact <a href="https://noveltypay.com" target="_blank">Novelty Payments</a> to get one.', 'wechatpay')
            ),
            'secure_key' => array(
                'title'       => __('Security Key', 'wechatpay'),
                'type'        => 'text',
                'description' => __('Please enter the security key<br />If you don\'t have it, contact <a href="https://noveltypay.com" target="_blank">Novelty Payments</a> to get one.', 'wechatpay'),
                'css'         => 'width:400px'
            ),
            'debug' => array(
                'title'       => __('Debug Log', 'wechatpay'),
                'type'        => 'checkbox',
                'label'       => __('Enable logging', 'wechatpay'),
                'default'     => 'no',
                'description' => __('Log events, such as trade status, inside <code>woocommerce/logs/wechatpaybynovelty.txt</code>', 'wechatpay')
            )
        );

        // For WC2.2+
        if(  function_exists( 'wc_get_log_file_path' ) ){
             $this->form_fields['debug']['description'] = sprintf(__('Log events, such as trade status, inside <code>%s</code>', 'wechatpay'), wc_get_log_file_path( 'wechatpaybynovelty' ) );
        }
    }

    public function process_payment($order_id) {
	    $order = new WC_Order ( $order_id );
	    	$this_redirect = $order->get_checkout_payment_url ( true );
		if (empty($this_redirect)){
			$this_redirect = wc_get_checkout_url() . "order-pay/" . $order_id . "/?key=" . $order->get_order_key();
		}
	return array (
	        'result' => 'success',
	        //'redirect' => $order->get_checkout_payment_url ( true )
		'redirect' => $this_redirect
	    );
	}

	public function get_order_status_wechatbynovelty() {
		$order_id = isset($_POST['orderId'])?sanitize_text_field($_POST['orderId']):'';
		$order = new WC_Order ( $order_id );
		$isPaid = ! $order->needs_payment ();
	
		echo json_encode ( array (
		    'status' =>$isPaid? 'paid':'unpaid',
		    'url' => $this->get_return_url ( $order )
		));
		
		exit;
	}

	function wp_enqueue_scripts() {
		$orderId = get_query_var ( 'order-pay' );
		$order = new WC_Order ( $orderId );
		$payment_method = method_exists($order, 'get_payment_method')?$order->get_payment_method():$order->payment_method;
		if ($this->id == $payment_method) {
			if (is_checkout_pay_page () && ! isset ( $_GET ['pay_for_order'] )) {
			    
			    wp_enqueue_script ( 'WECHAT_BY_NOVELTY_JS_QRCODE', WC_NOVELTY_PAYMENT_WECHATPAY_URL. '/js/qrcode.js', array (), WC_NOVELTY_PAYMENT_WECHATPAY_VERSION );
				wp_enqueue_script ( 'WECHAT_BY_NOVELTY_JS_CHECKOUT', WC_NOVELTY_PAYMENT_WECHATPAY_URL. '/js/checkout.js', array ('jquery','WECHAT_BY_NOVELTY_JS_QRCODE' ), WC_NOVELTY_PAYMENT_WECHATPAY_VERSION );
				
			}
		}
	}

	/**
	 * 
	 * @param WC_Order $order
	 */
	function receipt_page($order_id) {
		$order = wc_get_order($order_id);
	    if(!$order||!$order->needs_payment()){
	        wp_redirect($this->get_return_url($order));
	        exit;
	    }
        //if(!isset($_SESSION)){
    	//	session_start();
	//}
	if (session_status() == PHP_SESSION_NONE) {
    		session_start();
	}

        if(isset($_SESSION["checkwechatpayordersbynovelty".$order_id])){
            $times = sanitize_text_field($_SESSION["checkwechatpayordersbynovelty".$order_id]);
            if(empty($times) || $times != $order_id ){
                return ;
            }
        }else{
            $_SESSION["checkwechatpayordersbynovelty".$order_id] = $order_id;
            return;
        }

        echo '<p>' . __ ( 'Please scan the QR code with WeChat to finish the payment.', 'wechatpay' ) . '</p>';

        $total_fee = intval(round(floatval($order->get_total()) * 100));
        $helper = new NoveltyWechatPayHepler();
        $redirect = $helper->getPaymentUrl(
            $this->merch_id, $order_id, 
            $total_fee,
            urldecode( $this->get_return_url( $order ) ),$this->notify_url,
            getNonceStr_novelty_wechat(),"test_subject","test_body",$this->secure_key);

        if ( 'yes' == $this->debug ){
            $this->log->add( 'wechatpaybynovelty', 'payment_url= ' . $redirect);
            $this->log->add('wechatpaybynovelty' , 'total_fee='.$total_fee);
        }

        $respText = getUrlContentGET_novelty_wechat($redirect);
        $result = json_decode($respText,true);

        if ( 'yes' == $this->debug ){
            $this->log->add( 'wechatpaybynovelty', 'request result = ' . $respText);
        }

        $url = isset($result['qrcode_url'])? $result ["qrcode_url"]:'';
        echo  '<input type="hidden" value="'.$respText.'"/>';
        echo  '<input type="hidden" id="novelty-wechat-payment-pay-url" value="'.$url.'"/>';
        echo  '<div style="width:200px;height:200px" id="novelty-wechat-payment-pay-img" data-oid="'.$order_id.'"></div>';
        
        return;
	}

    /**
     * Check for WechatPay IPN Response.异步通知
     *
     * @access public
     * @return void
     */
    function check_wechatpay_response() {

        @ob_clean();

        $order_id = sanitize_text_field($_POST['ordernum']);
        $order = new WC_Order( $order_id );

        if(empty($order)){
            $this->failed_request('ORDER_ERROR',$order_id);
        }

        if( isset( $_POST['ordernum'] ) && !empty( $_POST['ordernum'] ) &&
            isset( $_POST['cqtradenum'] ) && !empty( $_POST['cqtradenum'] ) &&
            isset( $_POST['currency'] ) && !empty( $_POST['currency'] ) &&
            isset( $_POST['total_fee'] ) && !empty( $_POST['total_fee'] ) &&
            isset( $_POST['sign_type'] ) && !empty( $_POST['sign_type'] ) &&
            isset( $_POST['trade_status'] ) && !empty( $_POST['trade_status'] ) &&
            isset( $_POST['sign'] ) && !empty( $_POST['sign'] )){

            $sign = sanitize_text_field($_POST['sign']);
			$trade_status = sanitize_text_field($_POST['trade_status']);

            $param = array(
                "cqtradenum" => sanitize_text_field($_POST['cqtradenum']),
                "currency"   => sanitize_text_field($_POST['currency']),
                "total_fee"  => sanitize_text_field($_POST['total_fee']),
                "exchange_rate" => sanitize_text_field($_POST['exchange_rate']),
                "sign_type"  => sanitize_text_field($_POST['sign_type']),
                "ordernum"   => sanitize_text_field($_POST['ordernum']),
                "trade_status" => sanitize_text_field($_POST['trade_status'])
            );

            $helper = new NoveltyWechatPayHepler();
            $sign_check = $helper->sign($param,$this->get_option( 'secure_key' ));

            $this->log->add( 'wechatpaybynovelty', '111');

            if($sign == $sign_check)
            {
                if($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS')
                {
                    if( $order->status != 'completed' && $order->status != 'processing' ){
                        $this->log->add( 'wechatpaybynovelty', '666');
                        $order->payment_complete();
                        $this->successful_request( $trade_status, $order_id );
                    }
                }
            }
            $this->log->add( 'wechatpaybynovelty', '222');
            $this->failed_request($trade_status,$order_id);

        }else{
            //return fail
            $this->log->add( 'wechatpaybynovelty', '333');
            $this->failed_request('TRADE_ERROR',$order_id);
        }
    }

    /**
     * Successful Payment!
     *
     * @access public
     * @param array $posted
     * @return void
     */
    function successful_request( $trade_status, $order_id ) {
        if ( 'yes' == $this->debug ){
            $this->log->add('wechatpaybynovelty', 'Trade Status Received: '.$trade_status.', Order ID: ' . $order_id );
        }

        header('HTTP/1.1 200 OK');
        echo "success";
        exit;
    }

    /**
     * Failed Payment
     */
    function failed_request( $trade_status, $order_id ){
        if ( 'yes' == $this->debug ){
            $this->log->add('wechatpaybynovelty', 'Trade Status Received: '.$trade_status.', Order ID: ' . $order_id );
        }

        header('HTTP/1.1 200 OK');
        echo "fail";
        exit;
    }

}


?>