(function ($) {
    function queryOrderStatus() {
        var ajax_url = wc_checkout_params.ajax_url;
        var order_id = $('#novelty-wechat-payment-pay-img').attr('data-oid');
        $.ajax({
            type: "POST",
            url: ajax_url,
            data:{
                "action":"get_order_status_wechatbynovelty",
                "orderId":order_id
            }
        }).done(function (data) {
            data = JSON.parse(data);
            if (data && data.status === "paid") {
                location.href = data.url;
            } else {
            	setTimeout(queryOrderStatus, 1000);
            }
        });
    }

   $(function(){
	   var qrcode = new QRCode(document.getElementById('novelty-wechat-payment-pay-img'), {
	        width : 282,
	        height : 282
	    });
	    
	    qrcode.makeCode($('#novelty-wechat-payment-pay-url').val());
	    queryOrderStatus();
   });
})(jQuery);