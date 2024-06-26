<?php
// remove cart WC()->cart->empty_cart();
add_action('wp_ajax_ctwoo_add_to_cart_ajax', 'ctwoo_add_to_cart_ajax');
add_action('wp_ajax_nopriv_ctwoo_add_to_cart_ajax', 'ctwoo_add_to_cart_ajax');
function ctwoo_add_to_cart_ajax(){
    $symbol = get_woocommerce_currency_symbol();
    $quantity = 1;
    $product_id = absint($_POST['product_id']);
    $variation_id = absint($_POST['variation_id']);
    $postid = ( !empty($variation_id) && $variation_id !== 0 ) ? $variation_id : $product_id;

    $extra_status = addslashes($_POST['extra_status']);
    $ctprice_extra = addslashes($_POST['ctprice_extra']);
    $cttitle_extra = addslashes($_POST['cttitle_extra']);
    $cart_item_data = array();   
    if ($extra_status == 'yes') {
        $product = wc_get_product( $postid );
        $price = $product->get_price();
        $total_price = $price + $ctprice_extra;
        
        $cart_item_data['ctex_price_pr'] = $total_price;
        $cart_item_data['ctex_title_pr'] = $cttitle_extra;
        $cart_item_data['ctprice_extra_buy'] = 'Tổng giá mua kèm + sản phẩm ( '.$symbol.' ): '.number_format($ctprice_extra, 0, ',', '.').' + '.number_format($price, 0, ',', '.');
    }
    $status = false;
    if ($variation_id !== 0) {
        $status = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, array(), $cart_item_data);
    }else{
        $status = WC()->cart->add_to_cart($product_id, $quantity, 0, array(), $cart_item_data);
    }
    if ($status == false) {
        $messege = '<span class="error">Thêm thất bại</span>';
    }else{
        $messege = '<span class="sucess">Đã thêm</span>';
    }
    html_add_cartpp($product_id, $variation_id, $messege);
    die();
}


/**
 * function custom price before add to cart
 */
add_action( 'woocommerce_before_calculate_totals', 'ctcs_cart_item_price', 20, 1 );
function ctcs_cart_item_price( $wc_cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) )
        return;   
    // First loop to check if product 11 is in cart
    foreach ( WC()->cart->get_cart() as $key => $cart_item ){        
        $product_id = $cart_item['product_id'];                
        $OriginalPrice = $cart_item['data']->get_price();  
        $price_cs = 500;       
        $FinalPrice = ( !empty($price_cs) ) ? $price_cs : $OriginalPrice;
        $cart_item['data']->set_price( $FinalPrice );
    }

}
function ctcs_data_item_cart(){
    return [
        'piece' => __('Piece Number', 'woocommerce')
    ];
}
/*
*We’re using woocommerce_add_cart_item_data, another filter, to add the custom cart item data.
*/
add_filter( 'woocommerce_add_cart_item_data', 'ctcs_add_cart_item_data', 10, 3 );
function ctcs_add_cart_item_data( $cart_item_data, $product_id, $variation_id ) { 
    $arr_field_data = ctcs_data_item_cart();
    foreach ($arr_field_data as $key => $value) {
        if( isset( $_REQUEST[$key]) ) {            
            $cart_item_data[$key] = sanitize_text_field( $_REQUEST[$key] );     
        } 
    }    
    return $cart_item_data;
}
/*
*To display the custom data in the cart, we’re going to use another filter, this time the woocommerce_get_item_data filter.
*/
add_filter( 'woocommerce_get_item_data', 'ctcs_woo_get_item_data', 10, 2 );
function ctcs_woo_get_item_data( $item_data, $cart_item_data ) {
    $arr_field_data = ctcs_data_item_cart();
    foreach ($arr_field_data as $key => $value) {
        if( isset( $cart_item_data[$key] ) ) {
            $item_data[] = array(
                'key' => $value,
                'value' => wc_clean( $cart_item_data[$key] )
            );
        }
    }    
    return $item_data;
}
/*
*create order line item
*/
add_action( 'woocommerce_checkout_create_order_line_item', 'ctcs_checkout_create_order_line_item', 10, 4 );
function ctcs_checkout_create_order_line_item( $item, $cart_item_key, $values, $order ) {
    $arr_field_data = ctcs_data_item_cart();
    foreach ($arr_field_data as $key => $value) {
        if( isset( $values[$key] ) ) {
            $item->add_meta_data( $value, $values[$key], true);
        }
    }  
}
/*
* add order item name in email
*/
add_filter( 'woocommerce_order_item_name', 'ctcs_order_item_name', 10, 2 );
function ctcs_order_item_name( $product_name, $item ) {
    $arr_field_data = ctcs_data_item_cart();
    foreach ($arr_field_data as $key => $value) {
        if( isset( $item[$key] ) ) {
            $product_name .= sprintf( '<p><span>%s: %s</span></p>', $value, esc_html( $item[$key] ));
        }
    }
    return $product_name;
}

add_action( 'woocommerce_order_status_completed', 'ctcs_entry_after_wc_order_completed' );
function ctcs_entry_after_wc_order_completed( $order_id ) {
	$order = new WC_Order( $order_id );
}
?>