<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://rewardstream.com
 * @since      1.0.0
 *
 * @package    RewardStream
 * @subpackage RewardStream/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * @package    RewardStream
 * @subpackage RewardStream/public
 * @author     Grow Development <daniel@growdevelopment.com>
 */
class RewardStream_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * API class instance
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     RewardStream_API $api
	 */
	private $api;

	/**
	 * Custom endpoint for page in My Account area
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     string
	 */
	private $endpoint;


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version, $api_public, $endpoint ) {

		$this->api = $api_public;
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->endpoint = $endpoint;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/rewardstream-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		global $wp_query;

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/rewardstream-public.js', array( 'jquery' ), $this->version, false );

		$is_endpoint = isset( $wp_query->query_vars[ $this->endpoint ] );
		if ( $is_endpoint && ! is_admin() && is_main_query() && is_account_page() ) {
			// get current customer data
			$user = wp_get_current_user();

			if ( ! $user )
				return;

			$access_token = get_user_meta( $user->ID, 'rewardstream_access_token', true );
			$src = trailingslashit( $this->api->get_instance_url() ). 'js/spark.' .$this->api->get_version() . '.min.js?api_key=' . $this->api->get_key() . '&token=' . $access_token;

			wp_enqueue_script( 'rewardstream-spark-widget', $src, '', '', false );

		}
		else{
			$src = trailingslashit( $this->api->get_instance_url() ). 'js/spark.' .$this->api->get_version() . '.min.js?api_key=' . $this->api->get_key();

			wp_enqueue_script( 'rewardstream-spark-widget', $src, '', '', false );
		}

	}

	/*
	 *
	 */
	public function register_shortcode(){

		add_shortcode( 'rewardstream_my_referrals', array( $this, 'rewardstream_my_referrals' ) );

	}

	/**
	 * This is the DIV included on the My Referrals page that the Spark JS populates
	 * This code is added
	 *
	 * @param array $atts
	 * @return string
	 */
	public function rewardstream_my_referrals( $atts ) {
		$require_purchase = $this->get_option( 'require-purchase' );
		$user_id          = get_current_user_id();
		$paying_customer  = $this->is_paying_customer( $user_id );

		$error_message    = get_user_meta( $user_id, 'rewardstream_error_message', true );

		if ( 0 < strlen( $error_message ) ) {
			return '<span style="color: red">' . $error_message . '</span>';
		}
		if ( $require_purchase && ! $paying_customer ) {
			// If Require Purchase is checked, make sure user is a paying customer.
			return '';
		} else {
			// Require Purchase is not checked, so display to all users.
			return '<div class="spark-refer-embed"></div>';
		}


	}

	/**
	 * Add a link to the My Account page that links to the refer page
	 *
	 * @since    1.0.0
	 */
	public function add_refer_link() {
		$require_purchase = $this->get_option( 'require-purchase' );
		$require_purchase_notification = $this->get_option( 'purchase-required-notification' );
		$user_id = get_current_user_id();
		$paying_customer  = $this->is_paying_customer( $user_id );
		
	}


	/**
	 * Call the API sync member data if we're on the WooCommerce My Account page
	 *
	 */
	public function maybe_call_sync_member_data() {

		global $wp_query;
		$is_endpoint = isset( $wp_query->query_vars[ $this->endpoint ] );

		if ( $is_endpoint && is_main_query() && is_account_page() ) {

			// get current customer data
			$user = wp_get_current_user();

			if ( ! $user || 0 == $user->ID ) {
				return;
			}

			$key = md5( $user->ID );

			if ( get_transient( $key ) ) {
				return;
			}

			// has customer made purchase?
			$order_count   = wc_get_customer_order_count( $user->ID );
			$made_purchase = ( 0 < $order_count ) ? true : false;

			// get options
			$require_purchase = $this->get_option( 'require-purchase' );

			if ( $require_purchase == 'yes' && ! $made_purchase ) {
				return;
			}

			// get token
			$access_token = $this->api->call_sync_member_data();

			if ( ! is_wp_error( $access_token ) ) {
				if ( 0 < strlen( $access_token ) ) {
					update_user_meta( $user->ID, 'rewardstream_access_token', $access_token );
					update_user_meta( $user->ID, 'rewardstream_error_message', '' );
					set_transient( $key, $access_token, 100 * 60 );
				} else {
					delete_user_meta( $user->ID, 'rewardstream_access_token', $access_token );
					update_user_meta( $user->ID, 'rewardstream_error_message', '' );
					delete_transient( $key );
				}
			} else {
				$error_message = __( 'Sorry, the Refer-A-Friend functionality is currently unavailable, please try again later.', 'rewardstream' ) .
				                 ' (code: ' . $access_token->get_error_code() . ' ' . $access_token->get_error_message() . ')';
				update_user_meta( $user->ID, 'rewardstream_access_token', '' );
				update_user_meta( $user->ID, 'rewardstream_error_message', $error_message );
				delete_transient( $key );
			}

		}
	}

	/**
	 * Get the setting option requested.
	 *
	 * @since   1.0.0
	 * @param   $option_name
	 * @return  string $option
	 */
	public function get_option( $option_name ){

		$options = get_option( $this->plugin_name . '-options' );
		$option = '';

		if ( ! empty( $options[ $option_name ] ) ) {
			$option = $options[ $option_name ];
		}

		return $option;
	}

	/**
	 * Check the coupon code vs API
	 *
	 * @return null
	 */
	public function check_for_rewardstream_coupon() {

		// Are we applying a coupon code?
		if ( empty( $_POST['apply_coupon'] ) && empty( $_POST['coupon_code'] ) ) {
			return;
		}
		//Get code from form
		$code = sanitize_text_field( $_POST['coupon_code'] );
		$code_prefix = substr($code, 0, 3 );
		$coupon = new WC_Coupon( $code );
		if ( $coupon->exists ) {
			// code exists
			return;
		}

		//If valid RS offer code is entered, create WC Coupon
		if ( 'ref' == strtolower($code_prefix)) {
			$reward = $this->api->call_get_offer( $code );
			if ( 'valid' == $reward->Status  &&  isset( $reward->Code ) ) {
				$this->create_offer_coupon( $reward );
			}

		}

		//If valid RS reward code is entered, create WC Coupon
		if( 'rew' == strtolower($code_prefix)) {
			$response = $this->api->call_get_certificate( $code );
			$certificate = $response->records[0];
			if ( $this->is_code_valid($certificate)  &&  isset( $certificate->CertificateNumber ) ){
				$this->create_reward_coupon( $certificate );
			}
		}

		return;
	}

	/**
	 * Check certificate to see if code has been used
	 *
	 * @param $certificate
	 * @return boolean
	 */
	public function is_code_valid( $certificate ){
		if($certificate->RedeemedAmount < $certificate->IssueAmount){
			return true;
		}
		return false;

	}

	/**
	 * Hook for coupon creation
	 *
	 * @param $certificate
	 * @return array
	 * @throws Exception
	 * @internal param array $data
	 * @internal param $code
	 *
	 */
	public function create_reward_coupon( $certificate ){
		$type = '';
		$free_shipping = false;
		$amount = 0;
		$min_cart = $certificate->Certificate->MinimumPurchase ?  $certificate->Certificate->MinimumPurchase : '';
		$max_cart = '';
		switch ( $certificate->Certificate->DiscountType ) {
			case 'percent_off':
				$type = 'percent';
				$amount = (int) $certificate->Certificate->DiscountValue;
				if ($certificate->Certificate->MaximumDiscount > 0)
				{
					// Calculate maximum cart total
					$max_cart = $certificate->Certificate->MaximumDiscount * (100 / $amount);
				}
				break;
			case 'dollar_off':
				$type = 'fixed_cart';
				$amount = (float) $certificate->Certificate->DiscountValue;
				break;
			case 'free_ship':
				$type = 'fixed_cart';
				$amount = 0;
				$free_shipping = true;
				break;
			default:
				throw new Exception("Unsupported discount type: " . $certificate->Certificate->DiscountType);
		}

		// Create coupon
		$coupon_data = array(
			'type'                         => $type,
			'amount'                       => $amount,
			'individual_use'               => false,
			'product_ids'                  => array(),
			'exclude_product_ids'          => array(),
			'usage_limit'                  => 1,
			'usage_limit_per_user'         => '',
			'limit_usage_to_x_items'       => '',
			'usage_count'                  => '',
			'expiry_date'                  => '',
			'enable_free_shipping'         => $free_shipping,
			'product_category_ids'         => array(),
			'exclude_product_category_ids' => array(),
			'exclude_sale_items'           => false,
			'minimum_amount'               => $min_cart,
			'maximum_amount'               => $max_cart,
			'customer_emails'              => '',
			'description'                  => 'Reward coupon created by RewardStream.'
		);

		$new_coupon = array(
			'post_title'   =>  $certificate->CertificateNumber ,
			'post_content' => '',
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
			'post_type'    => 'shop_coupon',
			'post_excerpt' => $coupon_data['description']
		);

		$id = wp_insert_post( $new_coupon, true );

		if ( is_wp_error( $id ) ) {
			return;
		}


		// Update wp cache so cart will know this coupon exists
		wp_cache_set( WC_Cache_Helper::get_cache_prefix( 'coupons' ) . 'coupon_id_from_code_' .  $certificate->CertificateNumber , $id, 'coupons' );

		// Set coupon meta
		update_post_meta( $id, 'discount_type', $coupon_data['type'] );
		update_post_meta( $id, 'coupon_amount', wc_format_decimal( $coupon_data['amount'] ) );
		update_post_meta( $id, 'individual_use', ( true === $coupon_data['individual_use'] ) ? 'yes' : 'no' );
		update_post_meta( $id, 'product_ids', implode( ',', array_filter( array_map( 'intval', $coupon_data['product_ids'] ) ) ) );
		update_post_meta( $id, 'exclude_product_ids', implode( ',', array_filter( array_map( 'intval', $coupon_data['exclude_product_ids'] ) ) ) );
		update_post_meta( $id, 'usage_limit', absint( $coupon_data['usage_limit'] ) );
		update_post_meta( $id, 'usage_limit_per_user', absint( $coupon_data['usage_limit_per_user'] ) );
		update_post_meta( $id, 'limit_usage_to_x_items', absint( $coupon_data['limit_usage_to_x_items'] ) );
		update_post_meta( $id, 'usage_count', absint( $coupon_data['usage_count'] ) );
		update_post_meta( $id, 'free_shipping', ( true === $coupon_data['enable_free_shipping'] ) ? 'yes' : 'no' );
		update_post_meta( $id, 'product_categories', array_filter( array_map( 'intval', $coupon_data['product_category_ids'] ) ) );
		update_post_meta( $id, 'exclude_product_categories', array_filter( array_map( 'intval', $coupon_data['exclude_product_category_ids'] ) ) );
		update_post_meta( $id, 'exclude_sale_items', ( true === $coupon_data['exclude_sale_items'] ) ? 'yes' : 'no' );
		update_post_meta( $id, 'minimum_amount', wc_format_decimal( $coupon_data['minimum_amount'] ) );
		update_post_meta( $id, 'maximum_amount', wc_format_decimal( $coupon_data['maximum_amount'] ) );
		update_post_meta( $id, 'customer_email', array_filter( array_map( 'sanitize_email', $coupon_data['customer_emails'] ) ) );
	}

	/**
	 * @param $getOfferResponse
	 */
	public function create_offer_coupon( $getOfferResponse ){

		$type = '';
		$free_shipping = false;
		$amount = 0;
		$min_cart = isset( $getOfferResponse->Offer->MinimumPurchase ) ?  $getOfferResponse->Offer->MinimumPurchase : 0;
		$max_cart = '';

		$customer_email = isset( $getOfferResponse->Referee->Email ) ? array( $getOfferResponse->Referee->Email ) : array();
		switch ( $getOfferResponse->Offer->Type ) {
			case 'percent_off':
				$type = 'percent';
				$amount = (int) $getOfferResponse->Offer->Value;
				if (isset( $getOfferResponse->Offer->MaximumDiscount ) && $getOfferResponse->Offer->MaximumDiscount > 0)
				{
					// Calculate maximum cart total
					$max_cart = $getOfferResponse->Offer->MaximumDiscount * (100 / $amount);
				}
				break;
			case 'dollar_off':
				$type = 'fixed_cart';
				$amount = (float) $getOfferResponse->Offer->Value;
				break;
			case 'free_ship':
				$type = 'fixed_cart';
				$amount = 0;
				$free_shipping = true;
				break;
			default:
				throw new Exception( "Unsupported discount type: " . $getOfferResponse->Offer->Type);
		}

		// Create coupon
		$coupon_data = array(
			'type'                         => $type,
			'amount'                       => $amount,
			'individual_use'               => false,
			'product_ids'                  => array(),
			'exclude_product_ids'          => array(),
			'usage_limit'                  => 1,
			'usage_limit_per_user'         => '',
			'limit_usage_to_x_items'       => '',
			'usage_count'                  => '',
			'expiry_date'                  => '',
			'enable_free_shipping'         => $free_shipping,
			'product_category_ids'         => array(),
			'exclude_product_category_ids' => array(),
			'exclude_sale_items'           => false,
			'minimum_amount'               => $min_cart,
			'maximum_amount'               => $max_cart,
			'customer_emails'              => $customer_email,
			'description'                  => 'Created by RewardStream. Referrer: ' .
			                                  $getOfferResponse->Referrer->FirstName . ' ' . $getOfferResponse->Referrer->LastName . ' (' .
			                                  $getOfferResponse->Referrer->Email . ')'
		);

		$new_coupon = array(
			'post_title'   => strtolower( $getOfferResponse->Code ),
			'post_content' => '',
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
			'post_type'    => 'shop_coupon',
			'post_excerpt' => $coupon_data['description']
		);

		$id = wp_insert_post( $new_coupon, true );

		if ( is_wp_error( $id ) ) {
			return;
		}


		// Update wp cache so cart will know this coupon exists
		wp_cache_set( WC_Cache_Helper::get_cache_prefix( 'coupons' ) . 'coupon_id_from_code_' . strtolower( $getOfferResponse->Code ), $id, 'coupons' );

		// Set coupon meta
		update_post_meta( $id, 'discount_type', $coupon_data['type'] );
		update_post_meta( $id, 'coupon_amount', wc_format_decimal( $coupon_data['amount'] ) );
		update_post_meta( $id, 'individual_use', ( true === $coupon_data['individual_use'] ) ? 'yes' : 'no' );
		update_post_meta( $id, 'product_ids', implode( ',', array_filter( array_map( 'intval', $coupon_data['product_ids'] ) ) ) );
		update_post_meta( $id, 'exclude_product_ids', implode( ',', array_filter( array_map( 'intval', $coupon_data['exclude_product_ids'] ) ) ) );
		update_post_meta( $id, 'usage_limit', absint( $coupon_data['usage_limit'] ) );
		update_post_meta( $id, 'usage_limit_per_user', absint( $coupon_data['usage_limit_per_user'] ) );
		update_post_meta( $id, 'limit_usage_to_x_items', absint( $coupon_data['limit_usage_to_x_items'] ) );
		update_post_meta( $id, 'usage_count', absint( $coupon_data['usage_count'] ) );
		update_post_meta( $id, 'free_shipping', ( true === $coupon_data['enable_free_shipping'] ) ? 'yes' : 'no' );
		update_post_meta( $id, 'product_categories', array_filter( array_map( 'intval', $coupon_data['product_category_ids'] ) ) );
		update_post_meta( $id, 'exclude_product_categories', array_filter( array_map( 'intval', $coupon_data['exclude_product_category_ids'] ) ) );
		update_post_meta( $id, 'exclude_sale_items', ( true === $coupon_data['exclude_sale_items'] ) ? 'yes' : 'no' );
		update_post_meta( $id, 'minimum_amount', wc_format_decimal( $coupon_data['minimum_amount'] ) );
		update_post_meta( $id, 'maximum_amount', wc_format_decimal( $coupon_data['maximum_amount'] ) );
		update_post_meta( $id, 'customer_email', array_filter( array_map( 'sanitize_email', $coupon_data['customer_emails'] ) ) );


	}

	/**
	 * Report order completion to RewardStream API
	 *
	 * @param $order_id
	 */
	public function complete_purchase( $order_id ){

		if ( is_int( $order_id ) ) {

			$order = wc_get_order( $order_id );

			if ( is_wp_error( $order ) )
				return;

			$coupons = $order->get_used_coupons();


			foreach ( $coupons as $coupon ) {

				if("rew" == substr( strtolower($coupon), 0, 3)){
					$this->api->call_redeem_reward( $coupon );

				}
				else if("ref" == substr( strtolower($coupon), 0, 3)){
					$this->api->call_redeem_code( $coupon, $order );
				}
			}
		}
	}

	/**
	 * Only display the content of the custom endpoint if the customer is logged in.
	 *
	 * @param $content
	 * @return string
	 */
	public function referrals_the_content ( $content ) {

		global $wp_query;
		$is_endpoint = isset( $wp_query->query_vars[ $this->endpoint ] );

		if ( $is_endpoint  && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {

			if ( is_user_logged_in() ) {
				return $this->verify_customer_purchase( $content );
			}

		}

		return $content;

	}

	/**
	 * Check if this is the My Referrals page. If Require Purchase is enabled
	 * and customer has not made a purchase display the Purchase Required Notification.
	 *
	 * @param $content
	 * @return string
	 */
	public function verify_customer_purchase( $content ){

		global $wp_query;
		$is_endpoint = isset( $wp_query->query_vars[ $this->endpoint ] );

		if ( $is_endpoint  && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {

			$require_purchase = $this->get_option( 'require-purchase' );
			$require_purchase_notification = $this->get_option( 'purchase-required-notification' );
			$user_id = get_current_user_id();
			$paying_customer  = $this->is_paying_customer( $user_id );

			if ( $require_purchase && ! $paying_customer ) {
				return  $require_purchase_notification;
			}

		}

		return $content;
	}


	/**
	 * Display HTML on the Order Received page
	 */
	public function order_received_html() {

		$html = $this->get_option( 'optional-html' );

		if ( 0 < strlen( $html ) ) {
			echo $html;
		}

	}

	/**
	 * Redirect non-logged-in user to my-account page if they try to access my-referrals page directly.
	 */
	public function maybe_redirect_to_login(){

		if( is_page('my-referrals') ){
			if ( ! is_user_logged_in() ) {
				$redirect = wc_get_page_permalink( 'myaccount' );
				wp_redirect( $redirect );
				exit();
			}
		}
	}

	/**
	 * Add custom endpoint to WooCommerce
	 */
	public function add_endpoints() {
		add_rewrite_endpoint( $this->endpoint, EP_ROOT | EP_PAGES );
	}

	/**
	 * Output the content for the custom endpoint page
	 */
	public function endpoint_content() {

		if ( ! is_user_logged_in() ) {
			wc_get_template( 'myaccount/form-login.php' );
		} else {
			$my_referrals_page_content = $this->get_option( 'my-referrals-page-content' );
			$the_content = $this->verify_customer_purchase( $my_referrals_page_content );
			echo do_shortcode( $the_content );
		}

	}

	/**
	 * Add this endpoint to the possible query vars.
	 *
	 * @param $vars
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		$vars[] = $this->endpoint;
		return $vars;
	}

	/**
	 * Change the title of the My Account page if on the custom endpoint
	 *
	 * @param $title
	 * @return string|void
	 */
	public function endpoint_title( $title ) {
		global $wp_query;
		$is_endpoint = isset( $wp_query->query_vars[ $this->endpoint ] );
		if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
			// New page title.
			$title = __( 'My Referrals', 'woocommerce' );
			remove_filter( 'the_title', array( $this, 'endpoint_title' ) );
		}
		return $title;

	}

	/**
	 * @param $items
	 *
	 * @return mixed
	 */
	public function new_menu_items( $items ) {
		// Remove the logout menu item.
		$logout = $items['customer-logout'];
		unset( $items['customer-logout'] );
		// Insert your custom endpoint.
		$items[ $this->endpoint ] = __( 'My Referrals', 'rewardstream' );
		// Insert back the logout item.
		$items['customer-logout'] = $logout;
		return $items;

	}

	/**
	 * This is a custom function that mimics the WooCommerce one because we might search for
	 * past user orders in the future.
	 *
	 * @param $user_id
	 * @return int
	 */
	public function is_paying_customer( $user_id ){
		$paying_customer = get_user_meta( $user_id, 'paying_customer', true );

		if ( $paying_customer ) {
			return $paying_customer;
		} else {

			$user = get_userdata( $user_id );

			$args = array(
				'post_type'     => 'shop_order',
				'post_status'   => array( 'wc-processing', 'wc-completed' ),
				'fields' => 'ids',
				'posts_per_page'=> '-1',
				'meta_query' => array(
					array(
						'key' => '_billing_email',
						'value' => $user->user_email,
						'compare' => '=',
					),
				)
			);

			$query = new WP_Query( $args );
			$orders = $query->posts;

			if ( count( $orders ) ){
				return true;
			} else {
				return false;
			}
		}
	}

	/**
	 * Fired when user registers with WordPress. Check for past orders that have the
	 * same email address and set the 'paying_customer' meta.
	 *
	 * @param $user_id
	 */
	public function check_for_past_purchases( $user_id ) {

		if ( isset( $_POST['email'] ) ) {
			$search_email = wc_clean( $_POST['email'] );

			$args = array(
				'post_type'     => 'shop_order',
				'post_status'   => array( 'wc-processing', 'wc-completed' ),
				'fields' => 'ids',
				'posts_per_page'=> '-1',
				'meta_query' => array(
					array(
						'key' => '_billing_email',
						'value' => $search_email,
						'compare' => '=',
					),
				)
			);

			$query = new WP_Query( $args );
			$orders = $query->posts;

			if ( count( $orders ) ){
				update_user_meta( $user_id, 'paying_customer', true );
			}

		}

	}


}
