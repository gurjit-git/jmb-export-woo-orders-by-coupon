<?php
/*
Plugin Name: JMB Export Woocommerce Orders By Coupon
Plugin URI: http://wordpress.org/plugins/
Description: Export Woocommerce Orders By Coupon
Author: Gurjit Singh
Version: 1.0
Author URI: http://jmbweb.com/
*/
if ( ! defined( 'ABSPATH' ) ) {
    return;
}

include_once('inc/coupon-custom-fields.php');

function getOrderbyCouponCode($coupon_code, $status = '', $start_date = '', $end_date = '') {
    global $wpdb;
    $return_array = [];
    $total_discount = 0;

    //$status_ar = explode('/', $status);
    if(count($status) > 1){
       $order_status_imp = implode("','", $status);
    }
    else{
       $order_status_imp = $status[0];
    }
    
    $query = "SELECT
        p.ID AS order_id
        FROM
        {$wpdb->prefix}posts AS p
        INNER JOIN {$wpdb->prefix}woocommerce_order_items AS woi ON p.ID = woi.order_id
        WHERE
        p.post_type = 'shop_order'  AND
        woi.order_item_type = 'coupon' AND
        woi.order_item_name = '" . $coupon_code . "'";
    
    if(empty($status)){
        $query = $query . " AND p.post_status IN ('" . implode("','", array_keys(wc_get_order_statuses())) . "')";
    }
    else{
        $query = $query . " AND p.post_status IN ('" . $order_status_imp . "')";
    }
    
    if(!empty($start_date) && !empty($end_date)){ 
        $query = $query . " AND DATE(p.post_date) BETWEEN '" . date('Y-m-d', strtotime($start_date)) . "' AND '" . date('Y-m-d', strtotime($end_date)) . "';";
    }
    
    //print_r(date('Y-m-d', strtotime($start_date)) .'  '. date('Y-m-d', strtotime($end_date)));
    
    $orders = $wpdb->get_results($query);

    if (!empty($orders)) {
        
        $dp = ( isset($filter['dp']) ? intval($filter['dp']) : 2 );
        //looping throught all the order_id
        foreach ($orders as $key => $order) {
            $order_id = $order->order_id;
            
            //getting order object
            $objOrder = wc_get_order($order_id);
            
            $order_status = $objOrder->get_status();
            
            $return_array['orders'][$key]['order_id'] = $order_id;
            
            $name = $objOrder->get_billing_first_name().' '.$objOrder->get_billing_last_name();

            $return_array['orders'][$key]['name'] = $name;
            $return_array['orders'][$key]['email'] = $objOrder->get_billing_email();
            $return_array['orders'][$key]['phone'] = $objOrder->get_billing_phone();
            
            $return_array['orders'][$key]['status'] = $order_status;
            
            $return_array['orders'][$key]['total'] = wc_format_decimal($objOrder->get_total(), $dp);
            $return_array['orders'][$key]['sub-total'] = wc_format_decimal($objOrder->get_subtotal(), $dp);
            $return_array['orders'][$key]['total_discount'] = wc_format_decimal($objOrder->get_total_discount(), $dp);
            $return_array['orders'][$key]['total_tax'] = wc_format_decimal($objOrder->get_total_tax(), $dp);
            $total += wc_format_decimal($return_array['orders'][$key]['total']);
            $sub_total += wc_format_decimal($return_array['orders'][$key]['sub-total']);
            $total_discount += wc_format_decimal($return_array['orders'][$key]['total_discount']);
            $total_tax += wc_format_decimal($return_array['orders'][$key]['total_tax']);
        }
//        echo '<pre>';
//        print_r($return_array);
    }
    $return_array['full_sub_total'] = $sub_total;
    $return_array['full_total'] = $total;
    $return_array['full_discount'] = $total_discount;
    $return_array['full_tax'] = $total_tax;
    $return_array['coupon_code'] = $coupon_code;
    return $return_array;
}

function gs_csv_string( $orders ){
    $delimiter = ","; 
    //$filename = "coupon_orders" . date('Y-m-d') . ".csv"; 
     
    // Create a file pointer 
    $f = fopen('php://memory', 'w+'); 
    
    $coupon_code_title = array('Coupon Code: '.$orders['coupon_code'], '', '', '', '', '', '', '', '' );
    fputcsv($f, $coupon_code_title, $delimiter); 
    
    // Set column headers 
    $fields = array('ID', 'NAME', 'EMAIL', 'PHONE', 'SUB TOTAL', 'DISCOUNT', 'TOTAL TAX', 'TOTAL', 'ORDER STATUS');
    //fputcsv( $f, array_keys( reset($orders) ) );
    fputcsv($f, $fields, $delimiter); 
    
    // ulong $val = 43564765876978;
    // string $s = val.ToString();
    
    foreach($orders['orders'] as $order){
        $lineData = array($order['order_id'], $order['name'], $order['email'], $order['phone'], $order['sub-total'], $order['total_discount'], $order['total_tax'], $order['total'], $order['status']); 
        fputcsv($f, $lineData, $delimiter);
    }
    
    $totals = array('Totals', '', '', '', $orders['full_sub_total'], $orders['full_discount'], $orders['full_tax'], $orders['full_total'], '');
    fputcsv($f, $totals, $delimiter);
    // Move back to beginning of file 
    //fseek($f, 0); 
 
    // Set headers to download file rather than displayed 
    // header('Content-Type: text/csv'); 
    // header('Content-Disposition: attachment; filename="' . $filename . '";'); 
 
    //output all remaining data on a file pointer 
    //fpassthru($f);
    rewind($f);
    //fclose( $f );
    return stream_get_contents($f);
}

function gs_send_csv_mail($csvData, $body, $to = '', $coupon_code, $subject = 'Coupon Orders', $from = 'noreply@danielchocolates.com') {

    // This will provide plenty adequate entropy
    $multipartSep = '-----'.md5(time()).'-----';

    // Arrays are much more readable
    $headers = array(
        "From: $from",
        "Reply-To: $from",
        "Content-Type: multipart/mixed; boundary=$multipartSep"
    );

    // Make the attachment
    $attachment = chunk_split(base64_encode(gs_csv_string($csvData)));

    // Make the body of the message
    $body = "--$multipartSep\r\n"
        . "Content-Type: text/plain; charset=ISO-8859-1; format=flowed\r\n"
        . "Content-Transfer-Encoding: 7bit\r\n"
        . "\r\n"
        . "$body\r\n"
        . "--$multipartSep\r\n"
        . "Content-Type: text/csv\r\n"
        . "Content-Transfer-Encoding: base64\r\n"
        . "Content-Disposition: attachment; filename=\"coupon-orders-".$coupon_code."-".date('F-j-Y').".csv\"\r\n"
        . "\r\n"
        . "$attachment\r\n"
        . "--$multipartSep--";

    // Send the email, return the result
    //return wp_mail( $to, $subject, $body, implode("\r\n", $headers) );
    return @mail($to, $subject, $body, implode("\r\n", $headers));

}

function gs_pdf_string( $data ){
    ini_set('display_startup_errors',1);
    ini_set('display_errors',1);
    error_reporting(-1);
    require_once('fpdf/html-link.php');
    //require_once('fpdf/fpdf.php');

    $pdf = new PDF_HTML();

    //$pdf = new FPDF('P', 'mm', 'A4');
    //$pdf->AddFont('Courier','','courier.php');
    $pdf->AddFont('OpenSans-Bold','','OpenSans-Bold.php');
    $pdf->AddFont('OpenSans-Regular','','OpenSans-Regular.php');
    
    $pdf->AddPage();
    $pdf->Image('https://example.com/wp-content/uploads/2021/06/example-Logo_RED.png',85,0,40);
    $pdf->Ln(17);
    $pdf->SetFont('OpenSans-Regular','',10);
    $pdf->Cell(0,0,'Coupon Code: '.$data['coupon_code'],0,0,'C');
    // Line break
    $pdf->Ln();
    $pdf->Cell(0,10,'Expiry Date:'.date('M d, Y'),0,0,'C');
    $pdf->Ln(12);
    $pdf->SetFont('OpenSans-Regular','',12);
    $pdf->Cell(0,10,'Group Savings Order Total = $'. $data['full_total'].' (Minimum Order $600)',0,0,'C');
    $pdf->Ln(15);
    // Colors, line width and bold font
    $pdf->SetFillColor(255,0,0);
    $pdf->SetTextColor(255);
    $pdf->SetDrawColor(255,0,0);
    $pdf->SetLineWidth(.002);
    $pdf->SetFont('OpenSans-Bold','', 10);
    // Header
    $w = array(20, 50, 70, 30, 20);
    $header = array('ORDER #', 'NAME', 'EMAIL', 'PHONE', 'TOTAL');
    for($i=0;$i<count($header);$i++)
        $pdf->Cell($w[$i],7,$header[$i],0,0,'C',true);
    $pdf->Ln();
    // Color and font restoration
    $pdf->SetFillColor(224,235,255);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(218,220,224);
    $pdf->SetLineWidth(.002);
    $pdf->SetFont('OpenSans-Regular','');
    // Data
    $fill = false;
    // echo "<pre>";
    // print_r($data);
    // echo "</pre>";
    foreach($data['orders'] as $row)
    {
        $pdf->Cell($w[0],10,$row['order_id'],1,0,'L',$fill);
        $pdf->Cell($w[1],10,$row['name'],1,0,'L',$fill);
        $pdf->Cell($w[2],10,$row['email'],1,0,'L',$fill);
        $pdf->Cell($w[3],10,$row['phone'],1,0,'R',$fill);
        //$pdf->Cell($w[4],10,number_format($row['sub-total']),'LR',0,'R',$fill);
       // $pdf->Cell($w[5],10,number_format($row['total_discount']),'LR',0,'R',$fill);
       // $pdf->Cell($w[6],10,number_format($row['total_tax']),'LR',0,'R',$fill);
        $pdf->Cell($w[4],10,'$'.number_format($row['total']),1,0,'R',$fill);
        //$pdf->Cell($w[8],10,$row['status'],'LR',0,'R',$fill);
        $pdf->Ln();
        //$fill = !$fill;
    }
    // Color and font restoration
    $pdf->SetFillColor(255,255,255);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(218,220,224);
    $pdf->SetLineWidth(.1);
    $pdf->SetFont('OpenSans-Bold','');
    
    $pdf->Cell(170,10,'Total',1,0,'R', true);
    $pdf->Cell(20,10, '$'.$data['full_total'],1,0,'R', true);
    // $results = array('Totals', '', '', '', '', '', '', $data['full_total'], '');
    // for($i=0;$i<count($results);$i++)
    //     $pdf->Cell($w[$i],7,$results[$i],1,0,'C',true);
    $pdf->Ln(30);
    $pdf->SetFont('OpenSans-Bold','',11);
    $pdf->Cell(0,10,'Have Further Questions or Require Assistance?',0,0,'C');
    $pdf->Ln(12);
    $pdf->SetFont('OpenSans-Regular','',10);
    
    $html_email = '<a href="mailto:customercare@example.com">customercare@example.com</a>';
    $calendly_link = '<a href="https://calendly.com/example-customercare">https://calendly.com/example-customercare</a>';

    //$pdf->WriteHTML($html_email);
    $pdf->SetTextColor(0, 0, 255);
    $pdf->SetFont('','U');
    $pdf->Cell(112,10,'customercare@example.com ',0,0,'R');
    $pdf->SetTextColor(0);
    $pdf->SetFont('','');
    $pdf->Cell(90,10,'| 604.880.3862',0,0,'L');
    $pdf->Ln(5);
    
    //$pdf->WriteHTML($calendly_link, 20);
    $pdf->SetFont('','');
    $pdf->Cell(75,10,'Schedule an appointment: ',0,0,'R');
    $pdf->SetTextColor(0, 0, 255);
    $pdf->SetFont('','U');
    $pdf->Cell(116,10,'https://calendly.com/example-customercare',0,0,'L');
    $pdf->Ln(8);
    $pdf->SetTextColor(0);
    $pdf->SetFont('','');
    $pdf->Cell(0,10,'www.example.com/group-savings',0,0,'C');
    $pdf->Ln(15);
    
    // encode data (puts attachment in proper format)
    $pdfdoc = $pdf->Output("", "S");
    return $pdfdoc;
    
}
function gs_send_pdf_mail($pdfData, $message, $to = '', $coupon_code, $subject = 'Coupon Orders', $from = 'noreply@danielchocolates.com') {

    // This will provide plenty adequate entropy
    $multipartSep = '-----'.md5(time()).'-----';

    // Arrays are much more readable
    $headers = array(
        "From: $from",
        "Reply-To: $from",
        "Content-Type: multipart/mixed; boundary=$multipartSep"
    );

    // Make the attachment
    $attachment = chunk_split(base64_encode(gs_pdf_string($pdfData)));

    // Make the body of the message
    $body = "--$multipartSep\r\n"
        . "Content-Type: text/plain; charset=ISO-8859-1; format=flowed\r\n"
        . "Content-Transfer-Encoding: 7bit\r\n"
        . "\r\n"
        . "$body\r\n"
        . "--$multipartSep\r\n"
        . "Content-Type: application/pdf;\r\n"
        . "Content-Transfer-Encoding: base64\r\n"
        . "Content-Disposition: attachment; filename=\"coupon-orders-".$coupon_code."-".date('F-j-Y').".pdf\"\r\n"
        . "\r\n"
        . "$attachment\r\n"
        . "--$multipartSep--";


    // Send the email, return the result
    //return wp_mail( $to, $subject, $body, implode("\r\n", $headers) );
    return @mail($to, $subject, $body, implode("\r\n", $headers));

}

add_filter( 'woocommerce_coupon_data_tabs', 'gs_custom_product_tab', 10, 1 );
function gs_custom_product_tab( $default_tabs ) {
    $default_tabs['gs_coupon_orders'] = array(
        'label'   =>  __( 'Orders', 'domain' ),
        'target'  =>  'gs_coupon_orders_data',
        'priority' => 60,
        'class'   => array()
    );
    return $default_tabs;
}

function jmb_admin_scripts_method() {
    wp_enqueue_style(
        'jmb-admin-custom-style',
        plugin_dir_url( __FILE__ ) . '/assets/css/admin.css' 
    );
    wp_enqueue_script(
        'jmb-admin-custom-script3',
        plugin_dir_url( __FILE__ ) . 'assets/js/admin.js', array( 'jquery' ), null, true 
    );
	wp_localize_script('jmb-admin-custom-script3', 'ajax_load', array('ajaxurl' => admin_url('admin-ajax.php')));
}
add_action( 'admin_enqueue_scripts', 'jmb_admin_scripts_method' );

add_action( 'wp_ajax_coupon_orders', 'gs_ajax_coupon_orders_json_handler' );
function gs_ajax_coupon_orders_json_handler() {
    // Your response in array
    $count = 0;
    $data = array();
    $order_status_imp = $start_date = $end_date = '';
    if (!in_array("", $_POST['dates'])) {
        $start_date = $_POST['dates'][0];
        $end_date = $_POST['dates'][1];
    }
    
    $coupon_code = $_POST['coupon'];
    $order_status = $_POST['order_status'];
   
    $orders = getOrderbyCouponCode($coupon_code, $order_status, $start_date, $end_date);

    ob_start();
    //   echo "<pre>";
    //     print_r($orders);
    //     echo "</pre>";
        if(array_key_exists('orders', $orders) == 1){
            foreach($orders['orders'] as $order){
                ?>
                    <tr class="<?php echo (++$count%2 ? "alternate" : ""); ?>" >
                        <th class="column-columnname"><?php echo $order['order_id']; ?></th>
                        <th class="column-columnname"><?php echo $order['name']; ?></th>
                        <td class="column-columnname"><?php echo $order['email']; ?></td>
                        <td class="column-columnname"><?php echo $order['phone']; ?></td>
                        <td class="column-columnname"><?php echo $order['sub-total']; ?></td>
                        <td class="column-columnname"><?php echo $order['total_discount']; ?></td>
                        <td class="column-columnname"><?php echo $order['total_tax']; ?></td>
                        <td class="column-columnname"><?php echo $order['total']; ?></td>
                        <th class="column-columnname"><?php echo $order['status']; ?></th>
                    </tr> 
                <?php
            }
            ?>
                <tr class="totals">
                    <th class="column-columnname" colspan="4">Totals: </th>
                    <td class="column-columnname"><?php echo $orders['full_sub_total']; ?></td>
                    <td class="column-columnname"><?php echo $orders['full_discount']; ?></td>
                    <td class="column-columnname"><?php echo $orders['full_tax']; ?></td>
                    <td class="column-columnname" colspan="2"><?php echo $orders['full_total']; ?></td>
                </tr> 
            <?php
            
       }else{
            ?>
            <tr class="alternate">
                <th class="column-columnname" colspan="7"><b>No Order Found</b></th>
            </tr> 
            <?php
        }
       
        $data['html'] = ob_get_clean();
        
        $data['count'] = count($orders['orders']);
        
        echo json_encode( $data );
 
    wp_die();
}

add_action( 'wp_ajax_export_coupon_orders', 'gs_ajax_export_coupon_orders_json_handler' );
function gs_ajax_export_coupon_orders_json_handler() {
    // Your response in array
    //$gs_coordinator_email = get_post_meta($_GET['post'], 'gs_coordinator_email', true);
    $admin_email = get_option( 'admin_email' );
    
    $start_date = $end_date = '';
    if (!in_array("", $_POST['dates'])) {
        $start_date = $_POST['dates'][0];
        $end_date = $_POST['dates'][1];
    }
    
    $coupon_code = $_POST['coupon'];
    $order_status = $_POST['order_status'];
    
    $coordinate_email = $_POST['coordinate_email'];
   
    $orders = getOrderbyCouponCode($coupon_code, $order_status, $start_date, $end_date);
    // echo "<pre>";
    // print_r($orders);
    // echo "</pre>";
    gs_send_pdf_mail($orders, "Coupon Orders", $coordinate_email, $coupon_code );
    gs_send_csv_mail($orders, "Coupon Orders", $coordinate_email, $coupon_code);
        //echo ob_get_clean();
    //}
    // Don't forget to stop execution afterward.
    wp_die();
}

/*
* Adding a Data Panel in Coupon page. This panel will open when click the Orders tab.
*/
add_action( 'woocommerce_coupon_data_panels', 'gs_coupon_orders_data' );
function gs_coupon_orders_data() {
    
    $gs_coordinator_email = get_post_meta($_GET['post'], 'gs_coordinator_email', true);
    $admin_email = get_option( 'admin_email' );
    
    $orders = getOrderbyCouponCode(get_the_title());
    echo '<div id="gs_coupon_orders_data" class="panel woocommerce_options_panel">';
    ?>
    <div class="options_group">
        <table class="widefat fixed" cellspacing="0">
            <tr>
                <td>
                    <?php
                        woocommerce_wp_text_input( array(
                            'id'                => 'gs_start_date',
                            'label'             => __('Start Date', 'woocommerce' ),
                            'placeholder'       => '',
                            'description'       => __( 'Enter Start date', 'woocommerce' ),
                            'desc_tip'    => true,
                            'class' => 'date-picker order_date'
                        ) );
                        
                        woocommerce_wp_text_input( array(
                            'id'                => 'gs_end_date',
                            'label'             => __('End Date', 'woocommerce' ),
                            'placeholder'       => '',
                            'description'       => __( 'Enter End date', 'woocommerce' ),
                            'desc_tip'    => true,
                            'class' => 'date-picker order_date'
                        ) );
                        
                    ?>
                </td>
                
                <td>
                    <span class="order_status_group">
                        <?php 
                            $order_status = wc_get_order_statuses(); 
                            //print_r($order_status);
                            foreach($order_status as $key => $status){
                            ?>
                                <label for="order_status">
                                    <input type="checkbox" name="order_status" id="order_status" class="order_status" value="<?php echo $key; ?>"/> <?php echo $status; ?>
                                </label>
                            <?php 
                            } 
                        ?>
                    </span>
                    
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <a href="#" class="button button-primary button-large export-coupon-orders">Export</a>
                    <p class="form-field">PDF and CSV files will go in coorninator email address: <b> <?php echo (!empty($gs_coordinator_email) ? $gs_coordinator_email : 'Add email address to get the export file.' ); ?></b></p>
                </td>
            </tr>
        </table>
        
        <table class="widefat fixed" cellspacing="0">
            <tr>
                <td>
                    <p class="form-field">
                        Automatic export weekly or monthly.
                    </p>
                    <?php
                    // $aef_args = array(
                    //     'coupon_id' => get_the_id()
                    // );
                    do_action('gs_auto_export_fields'); ?>
                </td>
            </tr>
        </table>
        
        <table class="widefat fixed gscoupon" cellspacing="0" data-coupon="<?php echo get_the_title(); ?>">
            <thead>
            <tr>
                <td colspan="9">
                    <p><b>Number of Orders: </b><span class="orders-count"><?php echo count($orders['orders']); ?></span></p>
                </td>
            </tr>
            <tr>
                <th class="manage-column column-columnname" scope="col">Order#</th>
                <th id="name" class="manage-column column-columnname" scope="col">Name</th>
                <th id="email" class="manage-column column-columnname" scope="col">Email </th>
                <th id="phone" class="manage-column column-columnname" scope="col">Phone</th>
                <th id="subtotal" class="manage-column column-columnname" scope="col">Sub Total </th>
                <th id="discount" class="manage-column column-columnname" scope="col">Discount</th>
                <th id="tax" class="manage-column column-columnname" scope="col">Tax Total</th>
                <th id="total" class="manage-column column-columnname" scope="col">Total </th>
                <th class="manage-column column-columnname">Status</th>
            </tr>
            </thead>
        
            <tfoot>
                <tr>
                    <th class="manage-column column-columnname" scope="col">Order#</th>
                    <th class="manage-column column-columnname" scope="col">Name</th>
                    <th class="manage-column column-columnname" scope="col">Email </th>
                    <th class="manage-column column-columnname" scope="col">Phone</th>
                    <th class="manage-column column-columnname" scope="col">Sub Total </th>
                    <th class="manage-column column-columnname" scope="col">Discount</th>
                    <th class="manage-column column-columnname" scope="col">Tax Total</th>
                    <th class="manage-column column-columnname" scope="col">Total</th>
                    <th class="manage-column column-columnname" scope="col">Status</th>
                </tr>
            </tfoot>
        
            <tbody class="coupon-orders">
                <?php
                // echo "<pre>";
                // print_r($orders);
                // echo "</pre>";
                    $count = 0;
					if($orders['orders']){
						foreach($orders['orders'] as $order){
						?>
							<tr class="<?php echo (++$count%2 ? "alternate" : ""); ?>">
								<th class="column-columnname"><?php echo $order['order_id']; ?></th>
								<th class="column-columnname"><?php echo $order['name']; ?></th>
								<td class="column-columnname"><?php echo $order['email']; ?></td>
								<td class="column-columnname"><?php echo $order['phone']; ?></td>
								<td class="column-columnname"><?php echo $order['sub-total']; ?></td>
								<td class="column-columnname"><?php echo $order['total_discount']; ?></td>
								<td class="column-columnname"><?php echo $order['total_tax']; ?></td>
								<td class="column-columnname"><?php echo $order['total']; ?></td>
								<td class="column-columnname"><?php echo $order['status']; ?></td>
							</tr> 
						<?php
						}
					}
                   ?>
                   
                   <tr class="totals">
                        <th class="column-columnname" colspan="4">Totals: </th>
                       
                        <td class="column-columnname"><?php echo $orders['full_sub_total']; ?></td>
                        <td class="column-columnname"><?php echo $orders['full_discount']; ?></td>
                        <td class="column-columnname"><?php echo $orders['full_tax']; ?></td>
                        <td class="column-columnname" colspan="2"><?php echo $orders['full_total']; ?></td>
                        
                    </tr>
                   
            </tbody>
        </table>
    </div>
   <?php
   echo '</div>';
}


add_filter( 'cron_schedules', 'gs_auto_export_coupons_orders_hook' );
function gs_auto_export_coupons_orders_hook( $schedules ) {
    $schedules['export_weekly'] = array(
            'interval'  => 7 * 24 * 60 * 60, //604800,
            'display'   => __( 'Weekly', 'textdomain' )
    );
    $schedules['export_monthly'] = array(
            'interval'  => 30 * 24 * 60 * 60,
            'display'   => __( 'Monthly', 'textdomain' )
    );
    return $schedules;
}

// Schedule an action if it's not already scheduled
if ( ! wp_next_scheduled( 'gs_auto_export_coupons_orders_weekly_hook' ) ) {
    wp_schedule_event( time(), 'export_weekly', 'gs_auto_export_coupons_orders_weekly_hook' );
}
if ( ! wp_next_scheduled( 'gs_auto_export_coupons_orders_monthly_hook' ) ) {
    wp_schedule_event( time(), 'export_monthly', 'gs_auto_export_coupons_orders_monthly_hook' );
}

// Hook into that action that'll fire Weekly
add_action( 'gs_auto_export_coupons_orders_weekly_hook', 'gs_auto_export_coupons_orders_weekly_func' );
function gs_auto_export_coupons_orders_weekly_func() {
    
    $coupon_posts = get_posts( array(
        'posts_per_page'   => -1,
        'orderby'          => 'name',
        'order'            => 'asc',
        'post_type'        => 'shop_coupon',
        'post_status'      => 'publish',
    ) );

    $coupon_codes = []; // Initializing

    foreach( $coupon_posts as $coupon_post) {
        $coupon_id = $coupon_post->ID;
        $coupon_code = $coupon_post->post_name;
        
        $gs_export_weekly = get_post_meta($coupon_id, '_gs_export_weekly', true);
        if($gs_export_weekly == 'yes'){
            $orders = getOrderbyCouponCode($coupon_code);
            gs_send_csv_mail($orders, "Coupon Orders", 'gurjit191@gmail.com', $coupon_code);
        }
    }
    
}

// Hook into that action that'll fire Monthly
add_action( 'gs_auto_export_coupons_orders_monthly_hook', 'gs_auto_export_coupons_orders_monthly_func' );
function gs_auto_export_coupons_orders_monthly_func() {
    
    $coupon_posts = get_posts( array(
        'posts_per_page'   => -1,
        'orderby'          => 'name',
        'order'            => 'asc',
        'post_type'        => 'shop_coupon',
        'post_status'      => 'publish',
    ) );

    $coupon_codes = []; // Initializing

    foreach( $coupon_posts as $coupon_post) {
        $coupon_id = $coupon_post->ID;
        $coupon_code = $coupon_post->post_name;
        
        $gs_coordinator_email = get_post_meta($coupon_id, 'gs_coordinator_email', true);
        
        $gs_export_monthly = get_post_meta($coupon_id, '_gs_export_monthly', true);
        if($gs_export_monthly == 'yes'){
            $orders = getOrderbyCouponCode($coupon_code);
            gs_send_pdf_mail($orders, "Coupon Orders", $gs_coordinator_email, $coupon_code);
        }
    }
    
}

function gs_extend_admin_search( $query ) {

	// Extend search for document post type
	$post_type = 'shop_coupon';
	// Custom fields to search for
	$custom_fields = array(
        "gs_coordinator_name", "gs_coordinator_email",
    );

    if( ! is_admin() )
    	return;
    
  	if ( $query->query['post_type'] != $post_type )
  		return;

    $search_term = $query->query_vars['s'];

    // Set to empty, otherwise it won't find anything
    $query->query_vars['s'] = '';

    if ( $search_term != '' ) {
        $meta_query = array( 'relation' => 'OR' );

        foreach( $custom_fields as $custom_field ) {
            array_push( $meta_query, array(
                'key' => $custom_field,
                'value' => $search_term,
                'compare' => 'LIKE'
            ));
        }

        $query->set( 'meta_query', $meta_query );
    };
}

add_action( 'pre_get_posts', 'gs_extend_admin_search' );