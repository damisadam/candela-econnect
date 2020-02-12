<?php
/*
Plugin Name: Candela E-connect
Plugin URI: https://sadamhussain.com/
Description: Candela E-connect API
Author: Sadam Hussain
Author URI: http://sadamhussain.com/
Text Domain: candela-econnect
Version: 1.0
*/

function candela_econnect_register_settings() {
    add_option( 'timestamp', '');
    register_setting( 'candela_econnect_options_group', 'timestamp', 'candela_econnect_callback' );

    add_option( 'inventory_timestamp', '');
    register_setting( 'candela_econnect_options_group', 'inventory_timestamp', 'candela_econnect_callback' );


    add_option( 'app_id', '');
    register_setting( 'candela_econnect_options_group', 'app_id', 'candela_econnect_callback' );

    add_option( 'app_key', '');
    register_setting( 'candela_econnect_options_group', 'app_key', 'candela_econnect_callback' );

    add_option( 'shop_id', '');
    register_setting( 'candela_econnect_options_group', 'shop_id', 'candela_econnect_callback' );

    add_option( 'app_url', '');
    register_setting( 'candela_econnect_options_group', 'app_url', 'candela_econnect_callback' );
}
add_action( 'admin_init', 'candela_econnect_register_settings' );

function candela_econnect_register_options_page() {
    add_options_page('Candela Options', 'Candela Options', 'manage_options', 'candela_econnect', 'candela_econnect_options_page');
}
add_action('admin_menu', 'candela_econnect_register_options_page');

function candela_econnect_options_page()
{

    //postOrder(46682);
    //print_r(wp_cron());
    // creatProduct();
    updateInventory();

    ?>
    <div>


        <h2>Candela API credential Options</h2>


        <form method="post" action="options.php">
            <?php settings_fields( 'candela_econnect_options_group' ); ?>

            <table>
                <tr valign="top">
                    <th style="padding-top: 7px;" scope="row"><label for="app_id">App ID</label></th>
                    <td><input type="text" id="app_id" name="app_id" value="<?php echo get_option('app_id'); ?>" /></td>
                </tr>

                <tr valign="top">
                    <th style="padding-top: 7px;"  scope="row"><label for="app_key">App Key</label></th>
                    <td><input type="text" id="app_key" name="app_key" value="<?php echo get_option('app_key'); ?>" /></td>
                </tr>

                <tr valign="top">
                    <th style="padding-top: 7px;" scope="row"><label for="shop_id">Shop ID</label></th>
                    <td><input type="text" id="shop_id" name="shop_id" value="<?php echo get_option('shop_id'); ?>" /></td>
                </tr>

                <tr   valign="top">
                    <th style="padding-top: 7px;" scope="row"><label for="app_url">API URL/IP</label></th>
                    <td><input type="text" id="app_url" name="app_url" value="<?php echo get_option('app_url'); ?>" /></td>
                </tr>

                <tr   valign="top">
                    <th style="padding-top: 7px;" scope="row"><label for="app_url">Product API last Run Timestamp</label></th>
                    <td><input type="text" id="timestamp" name="timestamp" value="<?php echo get_option('timestamp'); ?>" /></td>
                </tr>

                <tr   valign="top">
                    <th style="padding-top: 7px;" scope="row"><label for="app_url">Inventory API last Run Timestamp</label></th>
                    <td><input type="text" id="inventory_timestamp" name="inventory_timestamp" value="<?php echo get_option('inventory_timestamp'); ?>" /></td>
                </tr>

            </table>
            <?php  submit_button(); ?>
        </form>
    </div>
    <?php
}

function creatProduct(){
    @ini_set('upload_max_filesize' , '1024M' );
    @ini_set('memory_limit' , '1024M' );
    @ini_set('max_execution_time' , '5000' );
    $app_url=get_option('app_url');
    $app_id=get_option('app_id');
    $app_key=get_option('app_key');
    $shop_id=get_option('shop_id');
    $timestamp=get_option('timestamp');
    //$timestamp=date('yy-m-d',strtotime($timestamp));
    $url= "{$app_url}/api/Products/Products?appid={$app_id}&appkey={$app_key}&TimeStamp={$timestamp}&isWebItem=1";

    $response = wp_remote_get( $url,
        array(
            'method'     => 'GET',
        )
    );

    if(is_wp_error($response)){
        echo 'Error Found ( '.$response->get_error_message().' )';
    }else{
        $body = wp_remote_retrieve_body($response);
        $body=json_decode($body);
        //print_r($body);
        //die;

        if($body->msg=="success"){
            echo  "<pre>";
            $products=$body->data;
            foreach ($products as $product){

                $sku=$product->ProductCode;

                $content_post= get_post((int)$product->ProductItemID);


                if($content_post){}else{
                    $post_id = wp_insert_post( array(
                        'import_id' => (int)$product->ProductItemID,
                        'post_title' => $product->ItemName,
                        'post_content' => $product->ItemName,
                        'post_status' => 'publish',
                        'post_type' => "product",
                    ) );


                    // Cat

                    $term_id=0;
                    if(!term_exists($product->Category, 'product_cat')){
                        $term = wp_insert_term($product->Category, 'product_cat');
                        if ( !is_wp_error($term) && isset( $term[ 'term_taxonomy_id' ] ) ) {
                            $term_id=$term['term_id'];
                        }
                    } else {
                        $term_s = get_term_by( 'name', $product->Category, 'product_cat' );
                        $term_id=$term_s->term_id;
                    }
                    if($term_id!=0){
                        wp_set_object_terms( $post_id, $term_id, 'product_cat');
                    }


                    $term_id_sub=0;
                    if(!term_exists($product->SubCategory, 'product_cat')){
                        $term = wp_insert_term($product->SubCategory, 'product_cat',['parent'=>$term_id]);
                        if ( !is_wp_error($term) && isset( $term[ 'term_taxonomy_id' ] ) ) {
                            $term_id_sub=$term['term_id'];
                        }

                    } else {
                        $term_s = get_term_by( 'name', $product->SubCategory, 'product_cat' );
                        $term_id_sub=$term_s->term_id;
                    }
                    if($term_id_sub!==0 && $term_id!=0){
                        wp_set_object_terms( $post_id,  [$term_id, $term_id_sub], 'product_cat',true);

                    }


                    update_post_meta( $post_id, '_sku', $sku);

                    $attributes=[
                        'ProductCode'=>$product->ProductCode,
                        'LineItem'=>$product->LineItem,
                        'Size'=>$product->Size,
                        'Color'=>$product->Color,
                        'Designers'=>$product->Designers,
                        'ProductGroup'=>$product->ProductGroup,
                        'CalendarSeason'=>$product->CalendarSeason,
                        'Location'=>$product->Location,
                        'CreationDate'=>$product->CreationDate,
                        'Design'=>$product->Design,
                        'PurchaseConUnit'=>$product->PurchaseConUnit,
                        'PurchaseConFactor'=>$product->PurchaseConFactor,
                        'SaleTax'=>$product->SaleTax,
                        'TaxCode'=>$product->TaxCode,
                        'VAT'=>$product->VAT,
                        'VatType'=>$product->VatType,
                        'HSCode'=>$product->HSCode,
                        'AcquireType'=>$product->AcquireType,
                        'VendorCode'=>$product->VendorCode,
                        'PurchaseType'=>$product->PurchaseType,
                        'TechnicalDetails'=>$product->TechnicalDetails,
                        'InternalComments'=>$product->InternalComments,
                        'WebItem'=>$product->WebItem,
                    ];
                    foreach ($attributes as $key=>$value){

                        $product_attributes[$key."_".$post_id] = array(
                            'name' => htmlspecialchars(stripslashes($key)),
                            'value' => $value,
                            'is_visible' => 1,
                            'is_variation' => 1,
                        );

                        update_post_meta( $post_id, '_product_attributes', $product_attributes);
                    }
                    echo $sku. "added \n";
                    /*update_post_meta( $post_id, '_stock', 10 );
                    update_post_meta( $post_id, '_price', 200);
                    update_post_meta( $post_id, '_regular_price', 200 );
                    update_post_meta( $post_id, '_sale_price', 200 );
                    update_post_meta( $post_id, '_manage_stock', 'yes' );*/
//                    update_post_meta( $post_id, 'total_sales', '0' );
//                    update_post_meta( $post_id, '_downloadable', 'no' );
//                    update_post_meta( $post_id, '_virtual', 'yes' );
//                    update_post_meta( $post_id, '_regular_price', '' );
//                    update_post_meta( $post_id, '_sale_price', '' );
//                    update_post_meta( $post_id, '_purchase_note', '' );
//                    update_post_meta( $post_id, '_featured', 'no' );
//                    update_post_meta( $post_id, '_weight', '' );
//                    update_post_meta( $post_id, '_length', '' );
//                    update_post_meta( $post_id, '_width', '' );
//                    update_post_meta( $post_id, '_height', '' );

                    // update_post_meta( $post_id, '_product_attributes', array() );
//                    update_post_meta( $post_id, '_sale_price_dates_from', '' );
//                    update_post_meta( $post_id, '_sale_price_dates_to', '' );
//                    update_post_meta( $post_id, '_price', '' );
//                    update_post_meta( $post_id, '_sold_individually', '' );
//                    update_post_meta( $post_id, '_manage_stock', 'no' );
//                    update_post_meta( $post_id, '_backorders', 'no' );
//                    update_post_meta( $post_id, '_stock', '' );
                }





                //break;
            }

        }
    }

}

function updateInventory(){
    @ini_set('upload_max_filesize' , '1024M' );
    @ini_set('memory_limit' , '1024M' );
    @ini_set('max_execution_time' , '5000' );
    $app_url=get_option('app_url');
    $app_id=get_option('app_id');
    $app_key=get_option('app_key');
    $shop_id=get_option('shop_id');
    $timestamp=get_option('inventory_timestamp');
    // $timestamp=date('yy-m-d h:i:s A',strtotime($timestamp)-(5 * 60));

    //die($timestamp);
    $url= "{$app_url}/api/Inventory/ShopInventory?appid={$app_id}&appkey={$app_key}&ShopId={$shop_id}&TimeStamp={$timestamp}&isWebitem=1";

    $response = wp_remote_get( $url,
        array(
            'method'     => 'GET',
        )
    );
    // print_r($url);
    if(is_wp_error($response)){
        echo 'Error Found ( '.$response->get_error_message().' )';
    }else {
        $body = wp_remote_retrieve_body($response);
        $body = json_decode($body);
        //print_r($body);
        //die();
        if ($body->msg == "success") {

            $products = $body->data;

            foreach ($products as $product) {
                $sku=$product->Product_code;
                //print_r($product);
                // $sku=refineSku($sku);
                //print_r($sku);
                $product_id=(int)$product->product_item_id;
                $content_post= get_post($product_id);
                // $product_id = wc_get_product_id_by_sku($sku);
                if ($content_post) {

                    update_post_meta( $product_id, '_manage_stock', 'yes' );
                    update_post_meta($product_id, '_stock', $product->quantity-$product->Hold_Quantity);
                    update_post_meta($product_id, '_price', $product->Product_price);
                    update_post_meta( $product_id, '_regular_price', $product->Product_price );
                    update_post_meta( $product_id, '_sale_price', $product->Product_price );

                    if(($product->quantity-$product->Hold_Quantity)>0) {
                        update_post_meta($product_id, '_visibility', 'visible');
                        update_post_meta($product_id, '_stock_status', 'instock');
                    }else{
                        update_post_meta($product_id, '_visibility', 'invisible');
                        update_post_meta($product_id, '_stock_status', 'outofstock');
                    }
                    /*print_r($product);
                    die($product_id);*/
                    echo $sku. " Find \n";
                }else{
                    //print_r($product_id);
                    echo "Not found \n";
                }

            }

        }
    }
}
function refineSku($sku){
    $sku= str_replace("-","",$sku);
    $sku= str_replace("_","",$sku);
    $sku= str_replace(".","",$sku);

    return $sku;
}

function postOrder($order_id){
    $app_url=get_option('app_url');
    $app_id=get_option('app_id');
    $app_key=get_option('app_key');
    $shop_id=get_option('shop_id');
    $url= "{$app_url}/api/Orders/PostOrder";

    //$order = new WC_Order(3081);
    $order = wc_get_order( $order_id );
    /* $item = new WC_Order_Item_Product("#3081");
     $product = $item->get_product();*/
    //echo "<pre>";

    $order_data = $order->get_data(); // The Order data



    //Oder detail
    $order_date_created = $order_data['date_created']->date('Y-m-d H:i:s');
    $total_cost = $order_data['total'];
    //$order_status = $order_data['status'];
    $order_payment_method_title = $order_data['payment_method_title'];

    ## BILLING INFORMATION:

    $order_billing_first_name = $order_data['billing']['first_name'];
    $order_billing_last_name = $order_data['billing']['last_name'];
    $order_billing_company = $order_data['billing']['company'];
    $order_billing_address_1 = $order_data['billing']['address_1'];
    $order_billing_address_2 = $order_data['billing']['address_2'];
    $order_billing_city = $order_data['billing']['city'];
    $order_billing_state = $order_data['billing']['state'];
    $order_billing_postcode = $order_data['billing']['postcode'];
    $order_billing_country = $order_data['billing']['country'];
    $order_billing_email = $order_data['billing']['email'];
    $order_billing_phone = $order_data['billing']['phone'];
    // print_r($order);

    $data=[
        'appid'=>$app_id,
        'appKey'=>$app_key,
        "OrderId"=> $order_id."8",
        "ShopId"=> $shop_id,
        "OrderDate"=> $order_date_created,
        "FirstName"=> $order_billing_first_name,
        "LastName"=> $order_billing_last_name,
        "CustomerEmail"=> $order_billing_email,
        "Address"=> $order_billing_address_1." ".$order_billing_address_2,
        "City"=> $order_billing_city,
        "Country"=> $order_billing_country,
        "State"=> $order_billing_state,
        "Telephone"=> $order_billing_phone,
        //"Status"=> $order_status,
        "ShippingCost"=> $total_cost,
        "CustomerNo"=> $order_billing_phone,
        "comments"=> $order_payment_method_title,
        "CourierCompany"=> "TCS",
        "CourierNumber"=> $order_billing_phone,
        "Weight"=> 0,
        "Locality"=> "pakistani",


    ];
    $order_products=[];

    foreach( $order->get_items() as $item_id => $item ){
        $product = $item->get_product();
        //Get the product ID
        $product_id = $item->get_product_id();
        //code
        $product_code= $product->get_sku();

        $sale_price= $product->get_sale_price();
        // The quantity
        $product_qty = $item->get_quantity();

        // The product name
        $product_name = $item->get_name();

        $item_total=$sale_price*$product_qty;

        //Get the WC_Product object


        $order_products[]=[
            "ProductCode"=> $product_code,
            "ProductItemId"=> $product_id,
            "ItemName"=> $product_name,
            "Qty"=> $product_qty,
            "ItemAmount"=> $sale_price,
            "DiscountPerc"=> 0,
            "ItemTotal"=> $item_total
        ];


    }
    $data['Products']=$order_products;

    // print_r($data); die();
    //print_r(json_encode($data));

    $response = wp_remote_post( $url, array(
        'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
        'body'        => json_encode($data),
        'method'      => 'POST',
        'data_format' => 'body',
    ) );
    print_r($response);
    print_r($data);
    //creatProduct();
}
add_action( 'woocommerce_thankyou', function( $order_id ){
    // $order = new WC_Order( $order_id );
    postOrder($order_id);

});

// update inventory  every five mint
function myprefix_custom_cron_schedule( $schedules ) {
    $schedules['5min'] = array(
        'interval' =>  5*60, // Every 6 hours
        'display'  => __( 'Every every minute' ),
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'myprefix_custom_cron_schedule' );


if ( ! wp_next_scheduled( 'my_task_hookss' ) ) {
    wp_schedule_event( time(), '5min', 'my_task_hookss' );
}



add_action( 'my_task_hookss', 'my_task_hooks_function' );

function my_task_hooks_function() {
    updateInventory();

}





// Product update every hour

function product_custom_cron_schedule( $schedules ) {
    $schedules['60min'] = array(
        'interval' =>  5*60, // Every 6 hours
        'display'  => __( 'Every 60 minute' ),
    );
    return $schedules;
}


add_filter( 'cron_schedules', 'product_custom_cron_schedule' );


if ( ! wp_next_scheduled( 'product_task_hookss' ) ) {
    wp_schedule_event( time(), '60min', 'product_task_hookss' );
}



add_action( 'product_task_hookss', 'product_task_hooks_function' );

function product_task_hooks_function() {

    creatProduct();

}