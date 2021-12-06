<?php
// Add a custom field to Admin coupon settings pages
add_action( 'woocommerce_coupon_options', 'gs_add_coupon_text_field', 10 );
function gs_add_coupon_text_field() {
    
    //  Coordinator's Name,  Coordinator's Email
    
    $fields_ar = array(
        array(
            'id' => 'gs_coordinator_name',
            'label' => 'Coordinator`s Name',
            'desc' => 'Coordinator`s Name'
        ),
        array(
            'id' => 'gs_coordinator_email',
            'label' => 'Coordinator`s Email',
            'desc' => 'Coordinator`s Email'
        ), 
    );
    
    foreach($fields_ar as $field){
         woocommerce_wp_text_input( array(
            'id'                => $field['id'],
            'label'             => __( $field['label'], 'woocommerce' ),
            'placeholder'       => '',
            'description'       => __( $field['desc'], 'woocommerce' ),
            'desc_tip'    => true,
    
        ) );
    }
    
   
}

add_action('gs_auto_export_fields', 'gs_auto_export_fields_func');
function gs_auto_export_fields_func(){
    woocommerce_wp_checkbox( array(
        'id'        => '_gs_export_weekly',
        'desc'      => __('Export PDF and CSV weekly', 'woocommerce'),
        'label'     => __('Weekly', 'woocommerce'),
        //'desc_tip'  => 'true'
    )); 
    woocommerce_wp_checkbox( array(
        'id'        => '_gs_export_monthly',
        'desc'      => __('Export PDF and CSV monthly', 'woocommerce'),
        'label'     => __('Monthly', 'woocommerce'),
        //'desc_tip'  => 'true'
    )); 
}

// Save the custom field value from Admin coupon settings pages
add_action( 'woocommerce_coupon_options_save', 'gs_save_coupon_text_field', 10, 2 );
function gs_save_coupon_text_field( $post_id, $coupon ) {
    if( isset( $_POST['gs_coordinator_name'] ) ) {
        $coupon->update_meta_data( 'gs_coordinator_name', sanitize_text_field( $_POST['gs_coordinator_name'] ) );
        $coupon->save();
    }
    if( isset( $_POST['gs_coordinator_email'] ) ) {
        $coupon->update_meta_data( 'gs_coordinator_email', sanitize_text_field( $_POST['gs_coordinator_email'] ) );
        $coupon->save();
    }
    
    //if( isset( $_POST['_gs_export_weekly'] ) ) {
        $_gs_export_weekly = isset( $_POST['_gs_export_weekly'] ) ? 'yes' : 'no';
        update_post_meta($post_id, '_gs_export_weekly', esc_attr( $_gs_export_weekly ));
    //}
    
   // if( isset( $_POST['_gs_export_monthly'] ) ) {
        $_gs_export_monthly = isset( $_POST['_gs_export_monthly'] ) ? 'yes' : 'no';
        update_post_meta($post_id, '_gs_export_monthly', esc_attr( $_gs_export_monthly ));
    //}
    
}