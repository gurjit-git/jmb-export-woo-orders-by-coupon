jQuery(document).on('click', '.li-head', function(){
        jQuery(this).next('.li-footer').slideToggle();
        
});

jQuery(document).ready(function($) {
    
    // $("div, h1, h2, h3, h4, h5, h6, p, span, a").text(function () {
    //     return $(this).text().replace("Billing", "Shipping"); 
    // });
        
    $('.order-view').on('click', function(){
        $(this).parents('.order-header').next('.order-body').slideToggle();
    });
    
    $('input.variation_id').on('change',  function(){
        if( '' !== $('input.variation_id').val() ) {
            
            var var_id = $('input.variation_id').val();
            // alert('You just selected variation #' + var_id);
            //$('.woocommerce-variation-price').prependTo('.quantity');
            
            $('.variation_info-inner').hide();
          
            jQuery.ajax({
        		type: 'POST',
        		data: {action:"jmb_products_variation_data", var_id:var_id},
        		url: ajax_load.ajaxurl,
        		//dataType: 'json',
        		beforeSend: function() {
        		    $('.loading').addClass('on');
        		    $('.loading').removeClass('off');
                },
        		success:function(response){
        		    $('.loading').removeClass('on');
        		    $('.loading').addClass('off');
        		    $('.ingredients_tab').remove();
        		    $('.nutrition_tab').remove();
        	        $('.variation_info-inner .gs-ul-ingri-nutr').append(response);
        	        $('.variation_info-inner').slideDown();
        		},
        		error: function (response) {
        		  $('.variation_info-inner').html(response);
        		}
    
            });

        }
    });

    $('.variation_info').on('click',".wc-tabs.product-tabs a", function(){
       var tab_control = $(this).parent('li').attr('aria-controls');
       $('.variation_info .woocommerce-Tabs-panel ').removeClass('active');
       $('#'+tab_control).addClass('active');
        
    });

    
    $('.close-gs-popupmessage-hospitality').click(function(){
        
        $('.container-popupmessage_hospitality').fadeOut();
        $('.gs-popupmessage-hospitality').fadeOut();
        
    });
	
// 	$('.li-head').click(function(){

//         $(this).next('.li-footer').show();
        
//     });
    
    
            jQuery('.order_lookup').on('click',  function (event) {

                var order_id = $('#order_id').val();
                var email_id = $('#email_id').val();
                
                var ar = [order_id, email_id];
                
                jQuery.ajax({
                    url: ajax_load.ajaxurl,
                    //contentType: 'application/json; charset=utf-8',
            		type: 'POST',
            		data: {action:"order_lookup", ar:ar},
            		dataType: 'JSON',
            		
            		//dataType: 'json',
            // 		beforeSend: function() {
            // 		    $('.loading').addClass('on');
            // 		    $('.loading').removeClass('off');
            //         },
            		success:function(response){

            		    
            		},
            		error: function (response) {
            		   
            		}
        
                });
            
                event.preventDefault();
            });
    
});

if (jQuery('form.variations_form').length !== 0) {
    var form = jQuery('form.variations_form');
    var variable_product_price = '';
    setInterval(function() {
        if (jQuery('.single_variation_wrap span.price span.amount').length !== 0) {
            if (jQuery('.single_variation_wrap span.price span.amount').text() !== variable_product_price) {
                variable_product_price = jQuery('.single_variation_wrap span.price span.amount').text();
console.log(variable_product_price);
                jQuery('.price.product-page-price').text(variable_product_price);
            }
        }
    }, 500);
}
jQuery( ".variations_form" ).on( "woocommerce_variation_select_change", function () {
    if (jQuery('form.variations_form').length !== 0) {
    var form = jQuery('form.variations_form');
    var variable_product_price = '';
    setInterval(function() {
        if (jQuery('.single_variation_wrap span.price span.amount').length !== 0) {
            if (jQuery('.single_variation_wrap span.price span.amount').text() !== variable_product_price) {
                variable_product_price = jQuery('.single_variation_wrap span.price span.amount').text();
console.log(variable_product_price);
                jQuery('.price.product-page-price').text(variable_product_price);
            }
        }
    }, 500);
}
} );