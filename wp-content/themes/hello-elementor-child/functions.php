<?php
    add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );
    function my_theme_enqueue_styles() {
        $parenthandle = 'parent-style';
        $theme = wp_get_theme();
        wp_enqueue_style( $parenthandle, get_template_directory_uri() . '/style.css', 
            array(),  // if the parent theme code has a dependency, copy it to here
            $theme->parent()->get('Version')
        );
        wp_enqueue_style( 'child-style', get_stylesheet_uri(),
            array( $parenthandle ),
            $theme->get('Version') // this only works if you have Version in the style header
        );
    }
    
    // SMTP Authentication
    add_action( 'phpmailer_init', 'send_smtp_email' );
    function send_smtp_email( $phpmailer ) {
    	$phpmailer->isSMTP();
    	$phpmailer->Host       = SMTP_HOST;
    	$phpmailer->SMTPAuth   = SMTP_AUTH;
    	$phpmailer->Port       = SMTP_PORT;
    	$phpmailer->Username   = SMTP_USER;
    	$phpmailer->Password   = SMTP_PASS;
    	$phpmailer->SMTPSecure = SMTP_SECURE;
    	$phpmailer->From       = SMTP_FROM;
    	$phpmailer->FromName   = SMTP_NAME;
    }
    
    // Show Ver Producto in product store
    add_filter( 'woocommerce_loop_add_to_cart_link', 'ts_replace_add_to_cart_button', 10, 2 );
    function ts_replace_add_to_cart_button( $button, $product ) {
        if (is_product_category() || is_shop()) {
        $button_text = __("Ver Producto", "woocommerce");
        $button_link = $product->get_permalink();
        $button = '<a class="button" href="' . $button_link . '">' . $button_text . '</a>';
        return $button;
        }
    }
    
    // Skip Cart and change Add to Cart text
    add_filter( 'woocommerce_add_to_cart_redirect', 'skip_woo_cart' );
    function skip_woo_cart() {
       return wc_get_checkout_url();
    }
    add_filter( 'woocommerce_product_single_add_to_cart_text', 'cw_btntext_cart', 20, 2);
    add_filter( 'woocommerce_product_add_to_cart_text', 'cw_btntext_cart', 20, 2);
    function cw_btntext_cart( $button_text, $product ) {
        if( $product->is_type('external') )
            return $button_text;

        return __( 'Comprar Ahora', 'woocommerce' );
    }

    /* WooCommerce: The Code Below Removes Checkout Fields */
    add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );
    function custom_override_checkout_fields( $fields ) {
        unset($fields['order']['order_comments']);
        return $fields;
    }
    
    // Remove generator tags to hide versions (functions.php)
    remove_action('wp_head', 'wp_generator');
    function remove_wordpress_version() {
        return '';
    }
    add_filter('the_generator', 'remove_wordpress_version');
    
    // Pick out the version number from scripts and styles
    function remove_version_from_style_js( $src ) {
        if ( strpos( $src, 'ver=' . get_bloginfo( 'version' ) ) )
        $src = remove_query_arg( 'ver', $src );
        return $src;
    }
    add_filter( 'style_loader_src', 'remove_version_from_style_js');
    add_filter( 'script_loader_src', 'remove_version_from_style_js');
    
    // To avoid Wordpress from resizing big images
    add_filter( 'big_image_size_threshold', '__return_false' );
    
    
    
    // BEGIN Limit Logn Attempts
    function check_attempted_login( $user, $username, $password ) {
    if ( get_transient( 'attempted_login' ) ) {
        $datas = get_transient( 'attempted_login' );

        if ( $datas['tried'] >= 3 ) {
            $until = get_option( '_transient_timeout_' . 'attempted_login' );
            $time = time_to_go( $until );

            return new WP_Error( 'too_many_tried',  sprintf( __( '<strong>ERROR</strong>: Has alcanzado el máximo número de intentos de acceso, podrás intentarlo de nuevo en %1$s.' ) , $time ) );
        }
    }

    return $user;
    }
    add_filter( 'authenticate', 'check_attempted_login', 30, 3 ); 
    function login_failed( $username ) {
        if ( get_transient( 'attempted_login' ) ) {
            $datas = get_transient( 'attempted_login' );
            $datas['tried']++;
    
            if ( $datas['tried'] <= 3 )
                set_transient( 'attempted_login', $datas , 300 );
        } else {
            $datas = array(
                'tried'     => 1
            );
            set_transient( 'attempted_login', $datas , 300 );
        }
    }
    add_action( 'wp_login_failed', 'login_failed', 10, 1 ); 
    
    function time_to_go($timestamp)
    {
    
        // converting the mysql timestamp to php time
        $periods = array(
            "segundo",
            "minuto",
            "hora",
            "día",
            "semana",
            "mes",
            "año"
        );
        $lengths = array(
            "60",
            "60",
            "24",
            "7",
            "4.35",
            "12"
        );
        $current_timestamp = time();
        $difference = abs($current_timestamp - $timestamp);
        for ($i = 0; $difference >= $lengths[$i] && $i < count($lengths) - 1; $i ++) {
            $difference /= $lengths[$i];
        }
        $difference = round($difference);
        if (isset($difference)) {
            if ($difference != 1)
                $periods[$i] .= "s";
                $output = "$difference $periods[$i]";
                return $output;
        }
    }
    // END Limit Logn Attempts

    // Add navigation arrows in product gallery
    add_filter( 'woocommerce_single_product_carousel_options', 'sf_update_woo_flexslider_options' );
    function sf_update_woo_flexslider_options( $options ) {
        $options['directionNav'] = true;
        return $options;
    }
    
    // Set auto slide
    add_filter( 'woocommerce_single_product_carousel_options', 'customslug_single_product_carousel_options', 99, 1 );
    function customslug_single_product_carousel_options( $options ) {
        $options['slideshow'] = true;
        $options['animationLoop'] = true;
        return $options;
    }
    
    // Send email when order is cancelled
    add_action('woocommerce_order_status_changed', 'cancelled_order_email_notifications', 10, 4 );
    function cancelled_order_email_notifications( $order_id, $old_status, $new_status, $order ){
        if ( $new_status == 'cancelled' ) 
            WC()->mailer()->get_emails()['WC_Email_Cancelled_Order']->trigger( $order_id );
    }
    
    // Send email when order is created
    add_action('woocommerce_checkout_order_created', 'pending_order_email_notifications');
    function pending_order_email_notifications( $order ){
        $order_id = $order->get_id();
        WC()->mailer()->get_emails()['WC_Email_New_Pending_Order']->trigger( $order_id );
    }
    
    // Bypass logout confirmation
    add_action( 'template_redirect', 'bypass_logout_confirmation' );
    function bypass_logout_confirmation() {
        global $wp;
    
        if ( isset( $wp->query_vars['salir'] ) ) {
            wp_redirect( str_replace( '&amp;', '&', wp_logout_url( wc_get_page_permalink( 'myaccount' ) ) ) );
            exit;
        }
    }  
    
    // Add Shortcode for IMG with external image url and link
    function img_shortcode($atts)
    {
        // Attributes
        $atts = shortcode_atts(
            [
            'src' => '',
            'link' => '',
            ], $atts, 'img'
        );
    
        $return = '<a href="' . $atts['link'] . '" rel="nofollow" target="_blank">
                    <img src="' . $atts['src'] . '"/>
                </a>';
                
        return $return;
    }
    add_shortcode('img', 'img_shortcode');

    // Limit products per page
    add_filter( 'loop_shop_per_page', 'bt_new_loop_shop_per_page', 20 );
    function bt_new_loop_shop_per_page( $limit ) {
        $limit = 28;
        return $limit;
    }

    // Disable Google Fonts and Font Awesome
    add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );
    // Disable Font Awesome
    add_action( 'elementor/frontend/after_register_styles',function() {
        foreach( [ 'solid', 'regular', 'brands' ] as $style ) {
            wp_deregister_style( 'elementor-icons-fa-' . $style );
        }
    }, 20 );

    // Disable Eicons
    add_action( 'wp_enqueue_scripts', 'remove_default_stylesheet', 20 ); 
    function remove_default_stylesheet() { 
        wp_deregister_style( 'elementor-icons' ); 
    }

    // Enqueue used Google fonts (user | in family for adding extra fonts)
    /* function wpb_add_google_fonts() {
        wp_enqueue_style( 'wpb-google-fonts', 'https://fonts.googleapis.com/css?family=Delius+Swash+Caps', false ); 
    }
    add_action( 'wp_enqueue_scripts', 'wpb_add_google_fonts' ); */

    //Remove Gutenberg Block Library CSS from loading on the frontend
    function smartwp_remove_wp_block_library_css(){
        wp_dequeue_style( 'wp-block-library' );
        wp_dequeue_style( 'wp-block-library-theme' );
    }
    add_action( 'wp_enqueue_scripts', 'smartwp_remove_wp_block_library_css' );

    /** Disable Ajax Call from WooCommerce on front page and posts*/
    add_action( 'wp_enqueue_scripts', 'dequeue_woocommerce_cart_fragments', 11);
    function dequeue_woocommerce_cart_fragments() {
        if (is_front_page() || is_single() ) wp_dequeue_script('wc-cart-fragments');
    }

    // Disable Emojis in WordPress
    function disable_emoji_feature() {
	
        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
        remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
        remove_action( 'admin_print_styles', 'print_emoji_styles' );
        remove_filter( 'the_content_feed', 'wp_staticize_emoji');
        remove_filter( 'comment_text_rss', 'wp_staticize_emoji');
        remove_filter( 'embed_head', 'print_emoji_detection_script' );
        remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
        add_filter( 'tiny_mce_plugins', 'disable_emojis_tinymce' );
        add_filter( 'option_use_smilies', '__return_false' );
    }
    
    function disable_emojis_tinymce( $plugins ) {
        if( is_array($plugins) ) {
            $plugins = array_diff( $plugins, array( 'wpemoji' ) );
        }
        return $plugins;
    }
    
    add_action('init', 'disable_emoji_feature');
    add_filter( 'option_use_smilies', '__return_false' );
?>