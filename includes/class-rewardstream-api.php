<?php

/**
 * RewardStream API functionality
 *
 * @since      1.0.0
 * @package    RewardStream_API
 * @subpackage RewardStream_API/includes
 * @author     Daniel Espinoza <daniel@growdevelopment.com>
 */
class RewardStream_API {

	/** @var string API version */
	private $version = 'v2';

	/** @var string API endpoint */
	private $program_domain;

	/** @var string  */
	private $api_key;

	/** @var string  */
	private $api_secret;

	/** @var  Object */
	private $customer_data;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since    1.0.0
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 */
	public function __construct( $program_domain, $api_key, $api_secret ) {

		$this->program_domain = "https://" . $program_domain;
		$this->api_key        = $api_key;
		$this->api_secret     = $api_secret;

	}

	/**
	 * Send customer info to RS and receive access_token.
	 * Save access_token to user meta.
	 *
	 * @param int $user_id
	 * @return string|WP_Error $access_token
	 */
	public function call_sync_member_data( $user_id=0 ){

		if ( ! $user_id ) {
			$user    = wp_get_current_user();
			$user_id = $user->ID;
		} else {
			$user = get_user_by( 'ID', $user_id );
		}

		$access_token = '';

		if ( $user ) {
			$billing    = get_user_meta( $user->ID , '', true );

			/**
			 * We'll get the first and last name from these places in order:
			 * 1. WooCommerce Billing field
			 * 2. WooCommerce Shipping field
			 * 3. WordPress account details
			 * 4. Email address as first name, last name blank.
			 *
			 */
			if ( isset( $billing['billing_first_name'][0] ) && ( 0 <  strlen( $billing['billing_first_name'][0] ) ) ) {
				$first_name = $billing['billing_first_name'][0];
			} elseif ( isset( $billing['shipping_first_name'][0] ) && ( 0 <  strlen( $billing['shipping_first_name'][0] ) ) ) {
				$first_name = $billing['shipping_first_name'][0];
			} elseif ( 0 < strlen( $user->first_name )) {
				$first_name = $user->first_name;
			} else {
				$first_name = $user->user_email;
			}

			if ( isset( $billing['billing_last_name'][0] ) && ( 0 <  strlen( $billing['billing_last_name'][0] ) ) ) {
				$last_name = $billing['billing_last_name'][0];
			} elseif ( isset( $billing['shipping_last_name'][0] ) && ( 0 <  strlen( $billing['shipping_last_name'][0] ) ) ) {
				$last_name = $billing['shipping_last_name'][0];
			} elseif ( 0 < strlen( $user->first_name )) {
				$last_name = $user->last_name;
			} else {
				$last_name = '';
			}

			$email      = empty( $billing['billing_email'][0] ) ? $user->user_email : $billing['billing_email'][0];
			if ( empty( $email ) )
			{
			    $this->log( 'Could not find email for user id: ' . $user_id );
			    return '';
            }

			//TODO Determine the country code reliably and only send Canadian and American addresses...or add more countries and provinces?
//			$street1    = isset( $billing['billing_address_1'][0] ) ? $billing['billing_address_1'][0] : '';
//			$street2    = isset( $billing['billing_address_2'][0] ) ? $billing['billing_address_2'][0] : '';
//			$city       = isset( $billing['billing_city'][0] ) ? $billing['billing_city'][0] : '';
//			$state      = isset( $billing['billing_state'][0] ) ? $billing['billing_state'][0] : '';
//			$country    = isset( $billing['billing_country'][0] ) ? $billing['billing_country'][0] : '';
//			$zip        = isset( $billing['billing_postcode'][0] ) ? $billing['billing_postcode'][0] : '';

			$registered = date( 'Ymd', strtotime($user->data->user_registered ));
			$customer_args = array(
				'FirstName' => $first_name,
				'LastName' => $last_name,
				'EmailAddress' => $email,
				'Account' => array(
					'Number' => $user_id,
					'ActivationDate' => $registered,
				),
//				'Address' => array(
//					'StreetLine1' => $street1,
//					'StreetLine2' => $street2,
//					'City' => $city,
//					'State' => $state,
//					'Country' => $country,
//					'ZipCode' => $zip,
//				),
			);

			$json_args = json_encode( $customer_args );

			$request_args = array(
				'headers' => array(
					'Authorization' => 'Basic ' . $this->api_secret,
				),
				'body'    => $json_args,
				'timeout' => 10,
			);

			$url = $this->get_base_url() . 'custom/syncMemberData';

			$response = wp_remote_post( $url, $request_args );

			if ( ! is_wp_error( $response ) ) {
				$response = json_decode( wp_remote_retrieve_body( $response ) );

				if ( isset( $response->Error ) ) {
					$this->log( 'Error calling syncMemberData: ' . $response->Error->Code .
						' ' . $response->Error->Message );
					$access_token = '';
					return new WP_Error( $response->Error->Code, $response->Error->Message );
				} else {

					$this->log( "RESPONSE (call_sync_member_data): \n" . print_r( $response, true ) );

					$access_token = $response->access_token;
				}
			} else {
				$this->log( 'Error calling syncMemberData: ' . $response->get_error_message() );
				return $response;
			}

		} else {
			$this->log( 'User not logged in' );
		}

		return $access_token;

	}

	/**
	 * Call RewardStream API to check validity of the code
	 *
	 * @param string $code
	 * @return array|WP_Error $valid
	 */
	public function call_get_offer( $code ){
		$request_args = array(
			'headers' => array(
				'Authorization' => 'Basic ' . $this->api_secret,
			)
		);

		$args = array(
			'api_key' => $this->api_key,
			'code' => $code
		);
		$url = add_query_arg( $args, $this->get_base_url() . 'custom/getOffer' );

		$this->log( "REQUEST (call_get_offer): \n" . $url );

		$response = wp_remote_get( $url, $request_args );

		return json_decode($response['body']);

	}

	/**
	 * Call RewardStream API to get certificate based on reward code
	 *
	 * @param string $code
	 * @return object
	 */
	public function call_get_certificate( $code ){
		$request_args = array(
			'headers' => array(
				'Authorization' => 'Basic ' . $this->api_secret,
			)
		);

		$url = $this->get_base_url() . 'members/all/certificates?j=GoodsId%20as%20Certificate&i=*,Certificate:*&q=' . urlencode('CertificateNumber="' . $code . '"');
		$this->log( "REQUEST (call_get_certificate): \n" . $url );
		$response = wp_remote_get( $url, $request_args );

		return json_decode($response['body']);

	}

	/**
	 * POST certificate with incremented RedeemedAmount(after coupon is used)
	 *
	 * @param string $object
	 */
	public function call_redeem_reward( $code ){
		$object = $this->call_get_certificate($code);
		//Increase redeemedAmount in JSON
		$incrementedRedeemedAmount = $object->records[0]->RedeemedAmount + 1;
		$userId = $object->records[0]->UserId;
		$certId =  $object->records[0]->Id;

		$args = array(
			'RedeemedAmount' => $incrementedRedeemedAmount
		);

		$json_args = json_encode( $args );

		$request_args = array(
			'headers' => array(
				'Authorization' => 'Basic ' . $this->api_secret,
			),
			'body'    => $json_args,
			'timeout' => 10,
		);

		$url = $this->get_base_url() . 'users/' . $userId . '/certificates/' .  $certId ;

		$this->log( "REQUEST (call_redeem_reward): \n" . print_r( $request_args, true ) );

		$response = wp_remote_post( $url, $request_args );
		return $response;
	}


	/**
	 * Call API to report that a code has been used for a purchase
	 *
	 * @param string $code
	 * @param WC_Order $order
	 * @return object|WP_Error
	 */
	public function call_redeem_code( $code, $order ){
		$date_used = date( 'Y-m-d\TH:i:s.000P', time() );

		$order_items = array();
		$products = $order->get_items();

		foreach( $products as $product ) {
			$_product = wc_get_product( $product['product_id'] );
			$item = array();
			$item['Product']  = $_product->get_sku();
			$item['Quantity'] = $product['qty'];
			$item['Amount']   = number_format( $product['line_subtotal'], 2) ;

			$order_items[] = $item;
		}

		if ( 0 == $order->customer_user ) {
			// guest purchase
			$user_id = "guestorder#" . $order->get_order_number();
		} else {
			$user_id = $order->customer_user;
		}

		$args = array(
			'Code' => $code,
			'DateUsed' => $date_used,
			'Account' => array(
				'Number' => $user_id,
				'ActivationDate' => '',
				'InternalIdentifier' => '',
				'Type' => '',
				'SubType' => '',
				'Status' => '',
				'ProductLine' => '',
			),
			'Purchase' => array(
				'PurchaseNumber' => strval( $order->get_order_number() ),
				'SubTotal' => $order->get_subtotal(),
				'Items' => $order_items,
			),
		);

		$json_args = json_encode( $args );

		$request_args = array(
			'headers' => array(
				'Authorization' => 'Basic ' . $this->api_secret,
			),
			'body'    => $json_args,
			'timeout' => 10,
		);

		$url = $this->get_base_url() . 'custom/redeemOffer';

		$this->log( "REQUEST (call_redeem_code): \n" . print_r( $request_args, true ) );

		$response = wp_remote_post( $url, $request_args );

		if ( ! is_wp_error( $response ) ) {
			$response = json_decode( wp_remote_retrieve_body( $response ) );
		}

		$this->log( "RESPONSE (call_redeem_code): \n" . print_r( $response, true ) );

		return $response;

	}

	/**
	 * @return string
	 */
	public function get_instance_url(){
		return $this->program_domain;
	}

	/**
	 * @return string
	 */
	public function get_base_url(){
		return trailingslashit( $this->program_domain ) . 'api/' . trailingslashit( $this->version );
	}

	/**
	 * @return string
	 */
	public function get_version(){
		return $this->version;
	}

	public function get_key(){
		return $this->api_key;
	}

	/**
	 * @param $customer_data
	 */
	public function set_customer_data( $customer_data ){
		$this->customer_data = $customer_data;
	}

	/**
	 * @param $message
	 */
	public function log( $message ){

		if ( defined( 'WP_DEBUG' ) && true == WP_DEBUG ) {
			if ( class_exists( 'WC_Logger' ) ) {
				$log = new WC_Logger();
				$log->add( 'rewardstream', $message );
			}
		}
	}


}
