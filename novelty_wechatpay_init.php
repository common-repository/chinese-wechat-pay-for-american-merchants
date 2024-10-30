<?php
/*
Plugin Name: Chinese WeChat Pay for American merchants(微信支付美国版) 
Plugin URI: 
Description: Allow American merchants to integrate WeChat Pay with WordPress sites. Clients pay in Chinese Yuan and U.S. merchants receive money in US dollars ($USD). Novelty Payments is the offcial partner of WeChat Pay in USA.
Version: 1.6.5
Author: Novelty Payments
Author URI: https://noveltypay.com
*/

if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

if (! defined ( 'WC_NOVELTY_PAYMENT_WECHATPAY' )) {
	define ( 'WC_NOVELTY_PAYMENT_WECHATPAY', 'WC_NOVELTY_PAYMENT_WECHATPAY' );
} else {
	return;
}

define('WC_NOVELTY_PAYMENT_WECHATPAY_VERSION','1.0.0');
define('WC_NOVELTY_PAYMENT_WECHATPAY_ID','wc_wechatpay_by_novelty_payment_gateway');
define('WC_NOVELTY_PAYMENT_WECHATPAY_DIR',rtrim(plugin_dir_path(__FILE__),'/'));
define('WC_NOVELTY_PAYMENT_WECHATPAY_URL',rtrim(plugin_dir_url(__FILE__),'/'));

function wc_wechatpay_by_novelty_payment_gateway_init() {

    if( !class_exists('WC_Payment_Gateway') )  return;

    //语言包
    //load_plugin_textdomain( 'wechatpay', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/');

    require_once( plugin_basename( 'class-wc-wechatpay-by-novelty-payment.php' ) );
    $api = new WC_WechatPay_By_Novelty_Payment_Gateway();
    //$api->check_wechatpay_response();

    add_filter('woocommerce_payment_gateways', 'woocommerce_wechatpay_by_novelty_payment_add_gateway' );

    add_action( 'wp_ajax_get_order_status_wechatbynovelty', array($api, "get_order_status_wechatbynovelty" ) );
    add_action( 'wp_ajax_nopriv_get_order_status_wechatbynovelty', array($api, "get_order_status_wechatbynovelty") );

    add_action( 'wp_enqueue_scripts', array ($api,'wp_enqueue_scripts') );

    add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_wechatpay_by_novelty_payment_plugin_edit_link' );

}
add_action( 'plugins_loaded', 'wc_wechatpay_by_novelty_payment_gateway_init' );


/**
 * Add the gateway to WooCommerce
 *
 * @access  public
 * @param   array $methods
 * @package WooCommerce/Classes/Payment
 * @return  array
 */
function woocommerce_wechatpay_by_novelty_payment_add_gateway( $methods ) {

    $methods[] = 'WC_WechatPay_By_Novelty_Payment_Gateway';
    return $methods;
}

function wc_wechatpay_by_novelty_payment_plugin_edit_link( $links ){
    return array_merge(
        array(
            'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section='.WC_NOVELTY_PAYMENT_WECHATPAY_ID) . '">'.__( 'Settings', 'wechatpay' ).'</a>'
        ),
        $links
    );
}

?>