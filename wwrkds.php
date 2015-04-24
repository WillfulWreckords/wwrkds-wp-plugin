<?php
    /*
    Plugin Name: WWRKDS WP Tools
    Plugin URI: http://www.wwrkds.net
    Description: Custom plugin for WWRKDS management tasks
    Author: J. Lareau
    Version: 0.1
    Author URI: http://wwrkds.net
    */

/*  Copyright 2015  Jonathan J. Lareau  (email : willfulwreckords@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }

    // ///////////////////////////////////////////////////////////////////////////
    // wc_remove_related_products
    //
    // Clear the query arguments for related products so none show.
    // Add this code to your theme functions.php file.
    // ///////////////////////////////////////////////////////////////////////////
    function wc_remove_related_products( $args ) {
        return array();
    }
    add_filter('woocommerce_related_products_args','wc_remove_related_products', 10); 

    // ///////////////////////////////////////////////////////////////////////////
    // Customizations
    // ///////////////////////////////////////////////////////////////////////////

    //Display product vendor logos if available
    add_action('product_vendors_page_description_before','wwrkds_product_vendors_page_description_before');
    function wwrkds_product_vendors_page_description_before($vendor_id){
        $vendor_data = get_option( 'shop_vendor_' . $vendor_id );
        
        $vendor_website = '';
        if( isset( $vendor_data['website'] ) && ( strlen( $vendor_data['website'] ) > 0 || $vendor_data['website'] != '' ) ) {
            $vendor_website = $vendor_data['website'];
        }
        $vendor_image = '';
        if( isset( $vendor_data['image'] ) && ( strlen( $vendor_data['image'] ) > 0 || $vendor_data['image'] != '' ) ) {
            $vendor_image = $vendor_data['image'];
        }
        if( strlen( $vendor_website ) > 0 && strlen( $vendor_image ) > 0 ) {
            $html = "<a href='$vendor_website'><img alt='$vendor_website' src='$vendor_image'></a>";
        } else if( strlen( $vendor_image ) > 0 ) {
            $html = "<img alt='$vendor_website' src='$vendor_image'>";
        } 
        if( strlen( $vendor_website ) > 0 ) {
            $html .= "<p>Website: <a href='$vendor_website'>$vendor_website</a>";
        }
        echo $html;
    }
    add_action('product_vendors_page_description_after','wwrkds_product_vendors_page_description_after');
    function wwrkds_product_vendors_page_description_after($vendor_id){
        
		global $current_user;
        global $wc_product_vendors;
		global $current_user;
        
		wp_get_current_user();
		$user_id = $current_user->ID;
		$vendor = get_vendor( $vendor_id );
        $vendor_admins = get_vendor_admins( $vendor_id );
                
        $hasAdmins = count($vendor_admins) > 0;
        $isVendorAdmin = is_vendor_admin($vendor_id,$user_id);
                
        $html = "";
        if ($hasAdmins && $isVendorAdmin){
            $html .= "<h2>Vendor Administration:</h2>";
            $html .= "<p>You are registered as an administrator for this vendor account.</p>";
            $html .= wwrkds_get_vendor_admin_links();
        }else if(!$hasAdmins){
            $html .= "<p>This account does not have any assigned administrators.  To claim this account as an administrator please contact us.</p>";
        }else{
            
        }
        echo $html;
    }

//function wp_gear_manager_admin_scripts() {
//wp_enqueue_script('media-upload');
//wp_enqueue_script('thickbox');
//wp_enqueue_script('jquery');
//}
//function wp_gear_manager_admin_styles() {
//wp_enqueue_style('thickbox');
//}
//add_action('admin_print_scripts', 'wp_gear_manager_admin_scripts');
//add_action('admin_print_styles', 'wp_gear_manager_admin_styles');

    add_action('admin_print_scripts', 'wwrkds_enqueue');
    function wwrkds_enqueue(){
        // jQuery
        wp_enqueue_script('jquery');
        // This will enqueue the Media Uploader script
        wp_enqueue_media();
    }

    //Echos the script used for uploading image library fields and adding image url to custom field
    function wwrkds_upload_image_media_library_script(){
        ?>
            <script type="text/javascript">
                jQuery(document).ready(function($){
                    $('#upload-image-btn').click(function(e) {
                        e.preventDefault();
                        var image = wp.media({ 
                            title: 'Upload Image',
                            library : {
                                    type : 'image'
                                    //HERE IS THE MAGIC. Set your own post ID var
                                    //uploadedTo : wp.media.view.settings.post.id
                                },
                            // mutiple: true if you want to upload multiple files at once
                            multiple: false
                        }).open()
                        .on('select', function(e){
                            // This will return the selected image from the Media Uploader, the result is an object
                            var uploaded_image = image.state().get('selection').first();
                            // We convert uploaded_image to a JSON object to make accessing it easier
                            // Output to the console uploaded_image
                            console.log(uploaded_image);
                            var image_url = uploaded_image.toJSON().url;
                            // Let's assign the url value to the input field
                            $('#image-url').val(image_url);
                        });
                    });
                });
            </script>
        <?php
    }

    // Add fields to new vendor form
    add_action( 'shop_vendor_add_form_fields', 'wwrkds_custom_add_vendor_fields');
    function wwrkds_custom_add_vendor_fields( $taxonomy ) {
        wwrkds_upload_image_media_library_script();
        ?>
            <div class="form-field">
            <label for="vendor_website"><?php _e( 'Vendor website' ); ?></label>
            <input type="text" name="vendor_data[website]" id="vendor_website" class="vendor_fields" /><br/>
            <span class="description"><?php _e( 'The vendor\'s website.' ); ?></span>
            </div>

            <div class="form-field">
            <label for="vendor_image"><?php _e( 'Vendor image' ); ?></label>
            <input type="text" name="vendor_data[image]" id="image-url" class="vendor_fields" />
            <input id="upload-image-btn" type="button" value="Upload Image" /><br/>
            <span class="description"><?php _e( 'The vendor\'s image url.' ); ?></span>
            </div>
        <?php
    }
    // Add fields to vendor edit form for admins to edit
    add_action( 'shop_vendor_edit_form_fields', 'wwrkds_custom_edit_vendor_fields');
    function wwrkds_custom_edit_vendor_fields( $vendor ) {
        $vendor_id = $vendor->term_id;
        $vendor_data = get_option( 'shop_vendor_' . $vendor_id );
        
        //echo print_r($vendor_data);
        
        wwrkds_upload_image_media_library_script();
        
        $vendor_website = '';
        if( isset( $vendor_data['website'] ) && ( strlen( $vendor_data['website'] ) > 0 || $vendor_data['website'] != '' ) ) {
            $vendor_website = $vendor_data['website'];
        }
        
        //echo "<p>Website: $vendor_website</p>";
        
        ?>
            <tr class="form-field">
            <th scope="row" valign="top"><label for="vendor_website"><?php _e( 'Vendor website' ); ?></label></th>
            <td>
            <input type="text" name="vendor_data[website]" id="vendor_website" class="vendor_fields" value="<?php echo $vendor_website; ?>"/><br/>
            <span class="description"><?php _e( 'The vendor\'s website' ); ?></span>
            </td>
            </tr>
        <?php
        
        $vendor_image = '';
        if( isset( $vendor_data['image'] ) && ( strlen( $vendor_data['image'] ) > 0 || $vendor_data['image'] != '' ) ) {
            $vendor_image = $vendor_data['image'];
        }
        ?>
            <tr class="form-field">
            <th scope="row" valign="top"><label for="vendor_image"><?php _e( 'Vendor image' ); ?></label></th>
            <td>
            <input type="text" name="vendor_data[image]" id="image-url" class="vendor_fields"  value="<?php echo $vendor_image; ?>"/>
            <input id="upload-image-btn" type="button" value="Upload Image" /><br/>
            <span class="description"><?php _e( 'The vendor\'s image url' ); ?></span>
            </td>
            </tr>
        <?php
    }
    // Add fields to vendor details form for vendors to edit
    add_action( 'product_vendors_details_fields', 'wwrkds_custom_vendor_details_fields');
    function wwrkds_custom_vendor_details_fields( $vendor_id ) {
        $vendor = get_user_vendor();
        $vendor_data = get_option( 'shop_vendor_' . $vendor->ID );
        $vendor_info = get_vendor( $vendor->ID );
        
        wwrkds_upload_image_media_library_script();
        
        $vendor_website = '';
        if( isset( $vendor_data['website'] ) && ( strlen( $vendor_data['website'] ) > 0 || $vendor_data['website'] != '' ) ) {
            $vendor_website = $vendor_data['website'];
        }
        ?>
            <p class="form-field">
                <label for="vendor_website"><?php echo __( 'Website' ); ?>:</label>
                <input type="text" name="wc_product_vendors_website_<?php echo $vendor->ID; ?>" id="vendor_website" class="vendor_fields"  value="<?php echo $vendor_website; ?>" />
            </p>
        <?php
        
        $vendor_image = '';
        if( isset( $vendor_data['image'] ) && ( strlen( $vendor_data['image'] ) > 0 || $vendor_data['image'] != '' ) ) {
            $vendor_image = $vendor_data['image'];
        }
        ?>
            <p class="form-field">
                <label for="vendor_image"><?php echo __( 'Image URL' ); ?>:</label>
                <input type="text" name="wc_product_vendors_image_<?php echo $vendor->ID; ?>" id="image-url" class="vendor_fields" value="<?php echo $vendor_image; ?>"/>
                <input id="upload-image-btn" type="button" value="Upload Image" />
            </p>
        <?php
    }

    // Save fields from vendor details form
    add_action( 'product_vendors_details_fields_save', 'wwrkds_custom_vendor_details_fields_save');
    function wwrkds_custom_vendor_details_fields_save( $vendor_id, $posted ) {
        // This is in the PHP file and sends a Javascript alert to the client
        //$message = "Entering save";
        //echo "<script type='text/javascript'>alert('$message');</script>";
        
        $vendor_data = get_option( 'shop_vendor_' . $vendor_id );
        if( isset( $posted[ 'wc_product_vendors_website_' . $vendor_id ] ) ) {
            $vendor_data['website'] = $posted[ 'wc_product_vendors_website_' . $vendor_id ];
        }
        if( isset( $posted[ 'wc_product_vendors_image_' . $vendor_id ] ) ) {
            $vendor_data['image'] = $posted[ 'wc_product_vendors_image_' . $vendor_id ];
        }
        update_option( 'shop_vendor_' . $vendor_id, $vendor_data );
        //echo "saved";
    } 

    // Only display admin bar to administrators
    //add_action('after_setup_theme', 'remove_admin_bar');
    function remove_admin_bar() {
        if (!current_user_can('administrator') && !is_admin()) {
            show_admin_bar(false);
        }
    }

    /**
    * Removes the tab "Vendors" in the single product page.
    */
    function wc_remove_vendor_tab() {
        global $wc_product_vendors;
        remove_filter( 'woocommerce_product_tabs', array( $wc_product_vendors, 'product_vendor_tab' ) );
    }
    add_action( 'init', 'wc_remove_vendor_tab' ); 

    // Display 100 products per page. 
    function wc_change_pagenation() {
        add_filter( 'loop_shop_per_page', create_function( '$cols', 'return 100;' ), 20 ); 
    }
    add_action( 'init', 'wc_change_pagenation' ); 

    // Remove the 'Free!' text for products with 0 cost
    function wwrkds_remove_free_price_text($price){
        if ($price == 'Free!'){
            $price = '';   
        }
        return $price;
    }
    add_filter('woocommerce_get_price_html','members_only_price');

    //function woo_custom_cart_button_text() {
    //    $product = new WC_Product( get_the_ID() );
    //    $price = $product->price;
    //    if ($price == 'Free!'){
    //        $price = '';   
    //        return __( 'Add To Cart', 'woocommerce' );
    //    }else{
    //        return __( 'Add To Cart', 'woocommerce' );
    //    }
    //} 
    //add_filter( 'add_to_cart_text', 'woo_custom_cart_button_text' ); // < 2.1

    // ///////////////////////////////////////////////////////////////////////////
    // Shortcodes
    // ///////////////////////////////////////////////////////////////////////////

    add_shortcode( 'wwrkds_parse_cdbsdc_data', 'wwrkds_parse_cdbsdc_data' );
    function wwrkds_parse_cdbsdc_data(){
        $fname = "/Volumes/Data2/WillfulWreckords/BusinessDocs/Financial/CDBabyData/current/AlbumTotals.csv";
        $table = wwrkds_parse_csv_w_headers($fname);
        print_r($table);
        
        
        $user = get_user_by( 'login', 'cdbaby' );
        $totals = wwrkds_get_external_order_totals($user->ID);
        print_r($totals);
        
        //TODO: REGEX the table variable for product title -> SKU numbers, check for differences and create new orders accordingly
        //TODO: Once completed turn this into a daily CRON task
        
    }

    //Shortcode for programmatically adding external sales data...
    add_shortcode( 'wwrkds_ordergen', 'wwrkds_ordergen' );
    function wwrkds_ordergen( $atts ) {
        
        if (!current_user_can('administrator') && !is_admin()) {
            return "<p>Sorry!  You must be logged in as an administrator to view/edit raw 3rd party sales data</p>";
        }
        
        $a = shortcode_atts( array(
            'user' => 'cdbaby',
        ), $atts );
        
        //print_r($_REQUEST);
        $user = get_user_by( 'login', $a['user'] );

        $sku = isset($_REQUEST['sku']) ? $_REQUEST['sku'] : null;
        $date = isset($_REQUEST['date']) ? $_REQUEST['date'] : date("Y-m-d H:i:s") ;
        $price = isset($_REQUEST['price']) ? $_REQUEST['price'] : "\$0.0";
                
        $sku = trim($sku);
        $date = trim($date);
        $price = ltrim($price,"\$");
        $price = trim($price);
        
                
        $ret = "";
        $ret .= "<h3>Add New Order:</h3>";
        $ret .= "<form name='test' action='' method='post' >";
        $ret .= "   <input type='hidden' name='new_order'>";
        $ret .= "    SKU:        <input type='text' name='sku' value='".$sku."'/><br>";
        $ret .= "    DATE-TIME:  <input type='datetime' name='date' value='".$date."'/><br>";
        $ret .= "    AMOUNT:     <input type='datetime' name='price' value='".$price."'/><br>";
        $ret .= "    <input type='submit'>";
        $ret .= "</form>";
            
        
        if (isset($_REQUEST['sku']) && isset($_REQUEST['date']) && isset($_REQUEST['price']) && isset($_REQUEST['new_order'])){
            if(wwrkds_create_external_order($sku,$date,$price,$user)){  
                $ret .= "<p>3rd party order recorded!</p>";
            }else{
                $ret .= "<p>Oh, you poor poor bastard.  The computer didn't like your request.</p>";
            }
        }else{
            
        }
        
        //Now give some status to current totals
        $start_time = null;
        $stop_time = null;
        if (isset($_REQUEST['start_date'])) {
            $start_time = $_REQUEST['start_date'];
        }
        if(isset($_REQUEST['stop_date'])){
            $stop_time = $_REQUEST['stop_date'];   
        }
        
        $orders = wwrkds_get_all_user_orders($user->ID,$status='completed', $start_time, $stop_time);
        
        $totals = wwrkds_get_external_order_totals($user->ID, $start_time, $stop_time);
        
        $net = 0;
        foreach($totals as $key => $value){   
            $net += $value;
        }
        
        $ret .= "<h3>Current Total: \$$net</h3>";
        $ret .= "<table><title></title><tr><th>3rd Party Sales</th><th>SKU</th><th>Name</th></tr>";
        foreach($totals as $key => $value){   
            $product = wwrkds_get_product_by_sku($key);
            $ret .= "<<tr><td>$$value</td><td>$key</td><td>".$product->get_title()."</td></tr>";
        }
        $ret .= "</table>";
        $ret .= "<form name='date_search' action='' method='post' >";
        $ret .= "   <input type='hidden' name='order_dates'>";
        $ret .= "    REPORT START:  <input type='date' name='start_date' value='".$start_time."'/>";
        $ret .= "    REPORT STOP:  <input type='date' name='stop_date' value='".$stop_time."'/>";
        $ret .= "    <input type='submit' value='Update'>";
        $ret .= "</form>";
        
        //echo "<p>$net, </p>";
        //print_r($totals);
        
        return $ret;
    }

    // Shortcode for Product Vendors listing
    add_shortcode( 'wwrkds_list_vendors', 'wwrkds_list_vendors_shortcode' );
    function wwrkds_list_vendors_shortcode( $atts ) {
        global $wc_product_vendors;
        $vendor_list = get_vendors();
        
        //echo print_r($vendor_list);
        
        $vendors = '<table>';
        $k = 0;
        $ncols = 4;
        foreach ( $vendor_list as $vendor ) {
            if ($k == 0) {
                $vendors .= "<tr>";
            }
            $vendors .= '<td><a href="' . $vendor->url . '">' . $vendor->title . '</a></td>';
            
            if ($k == $ncols-1) {
                $vendors .= "</tr>";
                $k=-1;
            }
            $k = $k + 1;
        }
        $vendors .= '</table>';
        
        //$terms = get_terms( 'shop_vendor' );
        //foreach ( $terms as $term ) {
        //    $term_link = get_term_link( $term, 'shop_vendor' );
        //    if ( is_wp_error( $term_link ) )
        //        continue;
        //    $vendors .= '<h2><a href="' . $term_link . '">' . $term->name . '</a></h2>';
        //}
        return $vendors;
    } 

    function wwrkds_get_vendor_admin_links(){
        
            $html = "<p>To login to your vendor dashboard and manage, create or edit your vendor products please click <a href='".admin_url()."'>here</a></p>";
            $html .= "<p>To view your current earnings please click <a href='".get_home_url(null,'my-earnings')."'>here</a></p>";
        return $html;
    }
    

    add_shortcode( 'wwrkds_vendor_check', 'wwrkds_vendor_check');
    function wwrkds_vendor_check(){
        $html = '';
        $vendor_id = is_vendor();
        if( $vendor_id ) {
            $vendor = get_vendor($vendor_id);
            $html .= "<h2>Vendor Administration</h2>";
            $html .= "<p>You are registered as an administrator for the <a href='".$vendor->url."'>".$vendor->title."</a> vendor account.</p>";
            $html .= wwrkds_get_vendor_admin_links();
        }else{
            $match = wwrkds_vendor_auto_add(); 
            
            if($match){
                //There is a match
                $vendor = get_vendor($vendor_id);
                $html .= "<h2>Vendor Administration</h2>";
                $html .= "<p>Based on your e-mail address, your account has been automatically linked as a Vendor administrator because it corresponds with an existing vendor PayPal e-mail address.</p>";
                $html .= "<p>You are registered as an administrator for the <a href='".$vendor->url."'>".$vendor->title."</a> vendor account.</p>";
                $html .= wwrkds_get_vendor_admin_links();
            }else{
                $html .= "<h2>Vendor Administration</h2>";
                $html .= "<p>You're account email address has not yet been registered as a designated agent for any of our vendors.</p>";
            
                $html .= "<p>If you'd like to be assigned as the designated agent / manager for a specific vendor. Please contact us.(New contact form coming soon)</p>";
            
                $html .= "<p>Once you've been registered you will be able to see your royalty totals, submit new products for listing on the site, and edit your affiliate / vendor account information.  </p>";
            }
        }
        return $html;
    }

    // ///////////////////////////////////////////////////////////////////////////
    // Utility functions
    // ///////////////////////////////////////////////////////////////////////////


    function wwrkds_parse_csv_w_headers($fname){
        $file = fopen($fname,"r");

        $headers= fgetcsv($file);
        //print_r($headers);
        
        $table=array();
        
        while(! feof($file))
        {
            $row = array();
            $data = fgetcsv($file);
            for ($i=0;$i<count($data);$i++){
                $row[$headers[$i]] = $data[$i];   
            }
            $table[] = $row;
        }
        fclose($file);   
        
        return $table;
    }

    function wwrkds_get_external_order_totals($user_id, $start_time=null, $stop_time=null){
      
        $orders = wwrkds_get_all_user_orders($user_id,$status='completed');
        
        $net = 0;
        $totals = array();
        foreach ($orders as $order_id){
            $order = wc_get_order( $order_id );
            $total = (float) $order->get_total();
            $net += $total;
            $items = $order->get_items();
            $order_date = $order->order_date;
            
            if ($start_time!=null && isset($start_time) && strtotime($start_time) > strtotime($order_date)){
                continue;
            }
            if ($stop_time!=null && isset($stop_time) && strtotime($stop_time) < strtotime($order_date)){
                continue;
            }
                        
            foreach ( $items as $item ) {
                $product_id = $item['product_id'];
                $product = wc_get_product($product_id);
                $product_name = $item['name'];
                $product_name_sku = "$product_name ( $product->sku )";
                if (!array_key_exists($product->sku,$totals)){
                    $totals[$product->sku] = $total;
                }else{
                    $totals[$product->sku] += $total;
                }
                //echo "<p>$ $total : $product_name_sku </p>";
                break;
            }
        }   
        
        return $totals;
    }

    /**
     * Returns all the orders made by the user
     *
     * @param int $user_id
     * @param string $status (completed|processing|canceled|on-hold etc)
     * @return array of order ids
     * http://fusedpress.com/blog/get-all-user-orders-and-products-bought-by-user-in-woocommerce/
     */
    function wwrkds_get_all_user_orders($user_id,$status='completed',$start_time=null,$stop_time=null){
        if(!$user_id)
            return false;

        $orders=array();//order ids

        if (false){
            $args = array(
                'numberposts'     => -1,
                'meta_key'        => '_customer_user',
                'meta_value'      => $user_id,
                'post_type'       => 'shop_order',
                'post_status'     => 'publish',
                'tax_query'=>array(
                        array(
                            'taxonomy'  =>'shop_order_status',
                            'field'     => 'slug',
                            'terms'     =>$status
                            )
                )  
            );
            $posts=get_posts($args);
        }else{
            $posts = get_posts( array(
                'numberposts'     => -1,
                'post_type'   => 'shop_order',
                'meta_key'        => '_customer_user',
                'meta_value'      => $user_id,
                'post_status' => 'wc-completed')
                );
        }
        
        //get the post ids as order ids
        //$orders=wp_list_pluck( $posts, 'ID' );
        //print_r($posts);
        $orders = array();
        foreach($posts as $post){
            $order_date = $post->post_date;
            if ($start_time!=null && isset($start_time) && strtotime($start_time) > strtotime($order_date)){
                continue;
            }
            if ($stop_time!=null && isset($stop_time) && strtotime($stop_time) < strtotime($order_date)){
                continue;
            }
            $orders[] = $post->ID;
        }

        return $orders;
    }

    // Get WC product by SKU
    function wwrkds_get_product_by_sku( $sku ) {
        global $wpdb;
        $product_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku ) );
        if ( $product_id ) return new WC_Product( $product_id );
        return null;
    }

    function wwrkds_create_external_order($sku,$date,$price,$user){
        
        //echo "Got $sku, $date, $price ... \\n";
        $product = wwrkds_get_product_by_sku( $sku );
        if (!$product){
            //echo "<p>Wah ... Fail!</p>";
            return false;
        }
        //echo "<p>Success!</p>";
                
        //echo "Trying to update $sku, $date, $price ... ";
                
        $bundle_data = get_post_meta($product->id,"_bundle_data");
        
        //print_r(get_post_meta($product->id));
        
        try{
            //Test if bundles product 
            if ($bundle_data){
                //We need to do something else here

                //echo "<p>Trying Bundled Product!</p>";

                $total_items = count($bundle_data[0]);
                $total_discount = 0.0;
                $total_qty = 0;
                foreach($bundle_data[0] as $bundle){
                    $total_qty += $bundle['bundle_quantity'] ? $bundle['bundle_quantity'] : 1;
                    $total_discount += $bundle['bundle_discount'] ? $bundle['bundle_discount'] : 0.0;
                }

                $order = wc_create_order();
                update_post_meta($order->id,"_customer_user",$user->ID);

                $totals = array("subtotal"=>0.0,"total"=>0.0,"subtotal_tax"=>0.0,"total_tax"=>0.0);
                $pargs = array("totals"=>$totals);
                $order->add_product( $product , 1 , $pargs);

                foreach($bundle_data[0] as $bundle){
                    $bundle_product = new WC_Product($bundle['product_id']);
                    $bundle_quantity = $bundle['bundle_quantity'] ? $bundle['bundle_quantity'] : 1;
                    $bundle_discount = $bundle['bundle_discount'] ? $bundle['bundle_discount'] : 0.0;

                    $totals = array("subtotal"=>$price/$total_qty*$bundle_quantity,
                                    "total"=>$price/$total_qty*$bundle_quantity,
                                    "subtotal_tax"=>0,"total_tax"=>0);
                    $pargs = array("totals"=>$totals);
                    $order->add_product( $bundle_product , 1 , $pargs);
                }

                $order->calculate_totals();
                $order->update_status( 'completed' );


                //Update the post date if available
                if ($date != null && isValidDateTime($date)){
                    $mypost = array();
                    $mypost['ID'] = $order->id; // the post ID you want to update
                    $mypost['post_date'] = $date; // uses 'Y-m-d H:i:s' format
                    wp_update_post($mypost);
                }

                //echo "Success!";
                return $order;

            }else{
                //echo "<p>Trying Un Bundled Product!</p>";

                $order = wc_create_order();
                update_post_meta($order->id,"_customer_user",$user->id);

                $totals = array("subtotal"=>$price,"total"=>$price,"subtotal_tax"=>0,"total_tax"=>0);
                $pargs = array("totals"=>$totals);
                $order->add_product( $product , 1 , $pargs);

                $order->calculate_totals();
                $order->update_status( 'completed' );

                //Update the post date if available
                if ($date!=null && isValidDateTime($date)){
                    $mypost = array();
                    $mypost['ID'] = $order->id; // the post ID you want to update
                    $mypost['post_date'] = $date; // uses 'Y-m-d H:i:s' format
                    wp_update_post($mypost);
                }

                //echo "Success!";
                return $order;
            }
        }catch(Exception $e){
            return false;   
        }
    }


    function isValidDateTime($dateTime)
    {
        if (preg_match("/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/", $dateTime, $matches)) {
            if (checkdate($matches[2], $matches[3], $matches[1])) {
                return true;
            }
        }

        return false;
    }

    /**
    * Logging function
    */
    function wwrkds_log($message) {
        if (WP_DEBUG === true) {
            if (is_array($message) || is_object($message)) {
                error_log(print_r($message, true));
            } else {
                error_log($message);
            }
        }
    }

    /**
     * Auto add the current user as vendor admin if a vendor paypal email matches
     */
    function wwrkds_vendor_auto_add() {
        $vendors = get_vendors();
        $user = wp_get_current_user();
        $matched_vendor_id = '';

        //$data = print_r($vendors,true);
        //$html .= "<p>$data</p>";
        $match = false;
        //if (!is_vendor()){
            foreach ($vendors as $vendor){
                // Check if vendor paypal email matches this users email
                $vendor_data = (array) $vendor;
                if ($vendor_data['paypal_email'] == $user->user_email){
                    $match = true;

                    // Update user meta
                    update_user_meta( $user->ID, 'product_vendor', $vendor_data['ID'] );
                    // Add user capabilities
                    wwrkds_add_vendor_caps( $user->ID );

                    // Update vendor
                    $vendor_data['admins'][] = $user->ID;
                    update_option( 'shop_vendor_' . $vendor_data['ID'], $vendor_data );

                    //$data = print_r($vendor_data,true);
                    //$html .= "<p>$data</p>";
                    //$html .= "<p>Match Detected!</p>";
                    break;
                }
            }
        //}
        return $match;
    }


    // ///////////////////////////////////////////////////////////////////////////
    // Copied from Product Vendors 
    // ///////////////////////////////////////////////////////////////////////////

    /**
     * Add capabilities to vendor admins
     * @param int $user_id User ID of vendor admin
     * Copied from Product Vendors code
     */
    function wwrkds_add_vendor_caps( $user_id = 0 ) {
        if( $user_id > 0 ) {
            $caps = wwrkds_vendor_caps();
            $user = new WP_User( $user_id );
            foreach( $caps as $cap ) {
                $user->add_cap( $cap );
            }
        }
    }

    /**
     * Remove capabilities from vendor admins
     * @param int $user_id User ID of vendor admin
     * Copied from Product Vendors code
     */
    function wwrkds_remove_vendor_caps( $user_id = 0 ) {
        if( $user_id > 0 ) {
            $caps = $wwrkds_vendor_caps();
            $user = new WP_User( $user_id );
            foreach( $caps as $cap ) {
                $user->remove_cap( $cap );
            }
        }
    }

    /**
     * Set up array of vendor admin capabilities
     * @return arr Vendor capabilities
     * Copied from Product Vendors code
     */
    function wwrkds_vendor_caps() {
        $caps = array(
            "edit_product",
            "read_product",
            "delete_product",
            "edit_products",
            "edit_others_products",
            "delete_products",
            "delete_published_products",
            "delete_others_products",
            "edit_published_products",
            "assign_product_terms",
            "upload_files",
            "manage_bookings",
        );

        $skip_review = get_option( 'woocommerce_product_vendors_skip_review' ) == 'yes' ? true : false;
        if( $skip_review ) {
            $caps[] = 'publish_products';
        }

        $caps = apply_filters( 'product_vendors_admin_caps', $caps );

        return $caps;
    }
?>