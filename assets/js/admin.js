jQuery(document).ready(function($) {
 
    $('input.order_date, input.order_status').on('change',  function(){
        //if( '' !== $('input.variation_id').val() ) {
        
        var start_date = $('input[name="gs_start_date"]').val();
        var end_date = $('input[name="gs_end_date"]').val();
        var coupon = $('.gscoupon').data('coupon');
       // var order_status = $('input[name="order_status"]').val();
        
        var dates = $('.order_date').map(function() {
            return this.value;
        }).get();
       
        var order_status = $('.order_status').map(function() {
            if(this.checked) {
                return this.value;
            }
        }).get();
       
        //console.log(order_status);
       
       //document.location = document.location.href+"&success=no";
       
       //console.log(document.location);
       
        jQuery.ajax({
    		type: 'POST',
    		data: {action:"coupon_orders", dates:dates, coupon:coupon, order_status:order_status},
    		url: ajax_load.ajaxurl,
    		dataType: 'json',
    		beforeSend: function() {
    		    $('.coupon-orders').html('<tr><td style="color: red; font-weight: 800" colspan="3">Loading...</td></tr>');
            },
    		success:function(response){
    		    //document.location = document.location.href;
    		    console.log(response);
    		    $('.coupon-orders').html(response.html);
    		    $('.orders-count').text(response.count);
    		    //console.log(response);
    		   // $(".coupon-orders").tmpl(cricketers).appendTo("#container");
    		},
    		error: function (response) {
    		  $('.coupon-orders').html(response);
    		}

        });

       // }
    });
    
    $('a.export-coupon-orders').on('click',  function(e){
        //if( '' !== $('input.variation_id').val() ) {
        
        var start_date = $('input[name="gs_start_date"]').val();
        var end_date = $('input[name="gs_end_date"]').val();
        var coupon = $('.gscoupon').data('coupon');
       
        var dates = $('.order_date').map(function() {
            return this.value;
        }).get();
       
        var order_status = $('.order_status').map(function() {
            if(this.checked) {
                return this.value;
            }
        }).get();
        //window.open('&id='+coupon);
        
        var coordinate_email = $('#gs_coordinator_email').val();
       
        jQuery.ajax({
    		type: 'POST',
    		data: {action:"export_coupon_orders", dates:dates, coupon:coupon, order_status:order_status, coordinate_email:coordinate_email},
    		url: ajax_load.ajaxurl,
    		cache: false,
    		success:function(data){
    		    alert('Exported data file sent to the given emails. Please check.');
    		    //document.location = document.location.href+"&export=yes";
    		    console.log(data);
    		  // window.open('?id=helleo');
    		  //document.location = document.location.href+"&success=yes";
              /*
              * Make CSV downloadable
              */
            //   var downloadLink = document.createElement("a");
            //   var fileData = [data];
            // //   console.log(fileData);

            //   var blobObject = new Blob(fileData,{
            //      type: "text/csv;charset=utf-8;"
            //   });

            //   var url = URL.createObjectURL(blobObject);
            //   downloadLink.href = url;
            //   downloadLink.download = "coupon_orders.csv";

            //   /*
            //   * Actually download CSV
            //   */
            //   document.body.appendChild(downloadLink);
            //   downloadLink.click();
            //   document.body.removeChild(downloadLink);
    		},
    		error: function (response) {
    		  $('.coupon-orders').html(response);
    		}

        });
        
        e.preventDefault()

       // }
    });

    $('a.order_pickup_notify').on('click',  function(e){
        var order_pickup_date = $('#gs_order_pickup_date').val();
        var gs_billing_email = $('#gs_billing_email').val();
        console.log(order_pickup_date);
        jQuery.ajax({
    		type: 'POST',
    		data: {action:"send_order_pickup_notification", order_pickup_date:order_pickup_date, billing_email: gs_billing_email },
    		url: ajax_load.ajaxurl,
    		cache: false,
    		success:function(data){
    		   alert('Email has sent to customer to pickup the Order.');
    		    console.log(data);
    		   //location.reload();
    		},
    		error: function (response) {
    		  console.log(response);
    		}
        });
        e.preventDefault()
    });

});
