<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://rewardstream.com
 * @since      1.0.0
 *
 * @package    RewardStream
 * @subpackage RewardStream/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    RewardStream
 * @subpackage RewardStream/admin
 * @author     Grow Development <daniel@growdevelopment.com>
 */
class RewardStream_Admin {

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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in RewardStream_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The RewardStream_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/rewardstream-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in RewardStream_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The RewardStream_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/rewardstream-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Add support link on the plugin screen.
	 *
	 * @param $links
	 * @param $file
	 *
	 * @return array
	 */
	public function add_support_link( $links, $file ) {

		if ( $file == REWARDSTREAM_PLUGIN_BASENAME ) {
			$row_meta = array(
				'support' => '<a href="http://www.rewardstream.com/woocommerce/support" title="' . esc_attr( __( 'Refer A Friend Support', 'rewardstream' ) ) . '">' . __( 'Refer A Friend Support', 'rewardstream' ) . '</a>',
			);

			return array_merge( $links, $row_meta );
		}

		return (array) $links;
	}

	/**
	 * Adds a link to the plugin settings page
	 *
	 * @since 		1.0.0
	 * @param 		array 		$links 		The current array of links
	 * @return 		array 					The modified array of links
	 */
	public function settings_link( $links ) {

		$settings_link = sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=' . $this->plugin_name ), __( 'Settings', 'rewardstream' ) );
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Adds a settings page link to a menu
	 *
	 * @since 		1.0.0
	 * @return 		void
	 */
	public function add_menu() {
		add_submenu_page(
			'woocommerce',
			apply_filters( $this->plugin_name . '-settings-page-title', __( 'RewardStream Settings', 'rewardstream' ) ),
			apply_filters( $this->plugin_name . '-settings-menu-title', __( 'Referrals', 'rewardstream' ) ),
			'manage_options',
			$this->plugin_name,
			array( $this, 'options_page' )
		);
	}


	/**
	 *  Register settings for the plugin.
	 *
	 * The mapping section is dynamic and depends on defined membership levels and defined tags.
	 *
	 * @since       1.0.0
	 * @return      void
	 */
	public function register_settings() {

		register_setting(
			$this->plugin_name . '-options',
			$this->plugin_name . '-options',
			array( $this, 'validate_options' )
		);
		add_settings_section(
			$this->plugin_name . '-display-options',
			apply_filters( $this->plugin_name . '-display-section-title', __( 'Plugin Settings', 'rewardstream' ) ),
			array( $this, 'display_options_section' ),
			$this->plugin_name
		);
		add_settings_field(
			'program-domain',
			apply_filters( $this->plugin_name . '-display-program-domain', __( 'Program Domain', 'rewardstream' ) ),
			array( $this, 'display_options_program_domain' ),
			$this->plugin_name,
			$this->plugin_name . '-display-options'
		);
		add_settings_field(
			'api-key',
			apply_filters( $this->plugin_name . '-display-api-key', __( 'API Key', 'rewardstream' ) ),
			array( $this, 'display_options_api_key' ),
			$this->plugin_name,
			$this->plugin_name . '-display-options'
		);
		add_settings_field(
			'api-secret',
			apply_filters( $this->plugin_name . '-display-api-secret', __( 'API Secret', 'rewardstream' ) ),
			array( $this, 'display_options_api_secret' ),
			$this->plugin_name,
			$this->plugin_name . '-display-options'
		);
		add_settings_field(
			'optional-html',
			apply_filters( $this->plugin_name . '-display-optional-html', __( 'Checkout Success Message', 'rewardstream' ) ),
			array( $this, 'display_options_optional_html' ),
			$this->plugin_name,
			$this->plugin_name . '-display-options'
		);
		add_settings_field(
			'my-referrals-page-content',
			apply_filters( $this->plugin_name . '-display-purchase-required-notification', __( 'My Referrals Page Content', 'rewardstream' ) ),
			array( $this, 'display_options_my_referrals_page_content' ),
			$this->plugin_name,
			$this->plugin_name . '-display-options'
		);
		add_settings_field(
			'require-purchase',
			apply_filters( $this->plugin_name . '-display-require-purchase', __( 'Require Purchase', 'rewardstream' ) ),
			array( $this, 'display_options_require_purchase' ),
			$this->plugin_name,
			$this->plugin_name . '-display-options'
		);
		add_settings_field(
			'purchase-required-notification',
			apply_filters( $this->plugin_name . '-display-purchase-required-notification', __( 'Purchase Required Notification', 'rewardstream' ) ),
			array( $this, 'display_options_purchase_required' ),
			$this->plugin_name,
			$this->plugin_name . '-display-options'
		);
	}

	/**
	 * Creates the options page
	 *
	 * @since 		1.0.0
	 * @return 		void
	 */
	public function options_page() {
		?><div class="wrap"><h1><?php echo esc_html( get_admin_page_title() ); ?></h1></div><?php
		$program_domain = $this->get_option( 'program-domain' );
		$api_secret = $this->get_option( 'api-secret' );
		if ( 0 < strlen( $program_domain ) ) {
		    ?>
            <p>Your referral program is active. Log in to view progress and modify your settings.</p>
            <form method="post" target="_blank" action="<?php echo trailingslashit('https://' . $program_domain ) ?>portal/sso.pg">
            <input type="hidden" name="apisecret" value="<?php echo $api_secret ?>">
            <button type="submit" class="button button-primary"><?php echo __( 'RewardStream Management Portal', 'rewardstream' ) ?></button>
            </form><?php
		}
        ?>
        <p><?php echo __( 'Need help? Check out our support articles at ','rewardstream') ?><a href="https://www.rewardstream.com/woocommerce/support">https://www.rewardstream.com/woocommerce/support</a></p>
		<form action="options.php" method="post"><?php
		settings_fields( 'rewardstream-options' );
		do_settings_sections( $this->plugin_name );
		submit_button( 'Save Settings' );
		?></form><?php
	}

	/**
	 * Validates saved options
	 *
	 * If API Key and API Secret are supplied set the plugin-added pages to published.
	 *
	 * @since 		1.0.0
	 * @param 		array 		$input 			array of submitted plugin options
	 * @return 		array 						array of validated plugin options
	 */
	public function validate_options( $input ) {

		if ( isset($input['api-key']) && isset($input['api-secret']) &&
		     ( 0 < strlen($input['api-key']) ) && ( 0 < strlen($input['api-secret']) ) ) {

			$refer_page_id     = get_option( 'rewardstream_refer_page_id' );
			if ( 0 < intval( $refer_page_id ) ) {
				$page_data = array(
					'ID'          => $refer_page_id,
					'post_status' => 'publish',
				);
				wp_update_post( $page_data );
			}
		}

		return $input;
	}

	/**
	 * Creates a settings section
	 *
	 * @since 		1.0.0
	 * @param 		array 		$params 		Array of parameters for the section
	 * @return 		mixed 						The settings section
	 */
	public function display_options_section( $params ) {
        echo '<p>' . __('Adjust settings for the RewardStream for WooCommerce plugin.', 'rewardstream') . '</p>';
	}

	/**
	 * Creates a settings input for the API key.
	 *
	 * @since 		1.0.0
	 * @return 		mixed 			The settings field
	 */
	public function display_options_api_key() {
		$api_key = $this->get_option( 'api-key' );

		?><input type="text" class="rs-text" id="<?php echo $this->plugin_name; ?>-options[api-key]" name="<?php echo $this->plugin_name; ?>-options[api-key]" value="<?php echo esc_attr( $api_key ); ?>" /><br/>
		<p class="description"><a href="http://www.rewardstream.com/woocommerce/setup" target="_blank"><?php echo __( 'Get your RewardStream API Key', 'rewardstream' ); ?></a></p><?php
	}

	/**
	 * Creates a settings input for the API secret.
	 *
	 * @since 		1.0.0
	 * @return 		mixed 	The settings field
	 */
	public function display_options_api_secret() {
		$api_secret = $this->get_option( 'api-secret' );
		?><input type="password" class="rs-text" id="<?php echo $this->plugin_name; ?>-options[api-secret]" name="<?php echo $this->plugin_name; ?>-options[api-secret]" value="<?php echo esc_attr( $api_secret ); ?>" /><br/>
		<p class="description"><?php echo __( 'This is a base64 encoded string.', 'rewardstream' ); ?></p><?php
	}

	/**
	 * Creates a settings input for the API secret.
	 *
	 * @since 		1.0.0
	 * @return 		mixed 	The settings field
	 */
	public function display_options_program_domain() {
		$program_domain = $this->get_option( 'program-domain' );
		?><input type="text" class="rs-text" id="<?php echo $this->plugin_name; ?>-options[program-domain]" name="<?php echo $this->plugin_name; ?>-options[program-domain]" value="<?php echo esc_attr( $program_domain ); ?>" /><br/><?php
	}

	/**
	 * Creates a settings input for the Optional HTML.
	 *
	 * @since 		1.0.0
	 * @return 		mixed 	The settings field
	 */
	public function display_options_optional_html() {
		$optional_html = $this->get_option( 'optional-html' );

		if ( 0 == strlen( $optional_html ) ){
			$link = get_bloginfo( 'url' ) . "/my-account/my-referrals";
			$optional_html = "<div class=\"spark-refer-embed\"></div><br>";

		}
		?><textarea id="<?php echo $this->plugin_name; ?>-options[optional-html]"
		            class="rs-textarea"
		            name="<?php echo $this->plugin_name; ?>-options[optional-html]"><?php echo esc_attr( $optional_html ); ?></textarea><br/>
		<p><?php echo __( 'This optional HTML will appear on your checkout success page above the Order Details section.', 'rewardstream' ); ?></p><?php
	}

	/**
	 * Creates a settings input for the Require Purchase checkbox.
	 *
	 * @since 		1.0.0
	 * @return 		mixed 	The settings field
	 */
	public function display_options_require_purchase() {
		$require_purchase = $this->get_option( 'require-purchase' );

		?><input name="<?php echo $this->plugin_name; ?>-options[require-purchase]" type="checkbox" id="<?php echo $this->plugin_name; ?>-options[require-purchase]" value="1" <?php checked( $require_purchase )?>>
		<label for="<?php echo $this->plugin_name; ?>-options[require-purchase]"><?php echo __('Require customers to make an approved purchase before they can send a referral.','rewardstream'); ?></label>
		<p><?php echo __( 'We recommend enabling this parameter to reduce the potential for fraud in your referral program.', 'rewardstream'); ?></p>
		<?php
	}

	/**
	 * Creates a settings input for the Purchase Required Notification.
	 *
	 * @since 		1.0.0
	 * @return 		mixed 	The settings field
	 */
	public function display_options_purchase_required() {
		$purchase_required_notification = $this->get_option( 'purchase-required-notification' );

		if ( 0 == strlen( $purchase_required_notification ) ) {
			$purchase_required_notification = "<p>You must make a purchase before you can make a referral.</p>";
		}
		?><textarea id="<?php echo $this->plugin_name; ?>-options[purchase-required-notification]"
		            class="rs-textarea"
		            name="<?php echo $this->plugin_name; ?>-options[purchase-required-notification]"><?php echo esc_attr( $purchase_required_notification ); ?></textarea><br/>
		<p><?php echo __( 'This message will appear on the My Account and My Referrals page if Require Purchase is enabled and the customer has not placed an order.', 'rewardstream' ); ?></p><?php
	}

	/**
	 * Creates a settings input for the My Referrals Page Content
	 *
	 * @since 		1.0.0
	 * @return 		mixed 	The settings field
	 */
	public function display_options_my_referrals_page_content() {
		$my_referrals_page_content = $this->get_option( 'my-referrals-page-content' );

		if ( 0 == strlen( $my_referrals_page_content ) ) {
			$my_referrals_page_content = '<!-- Embeds the referral dashboard here -->
<div class="section-first">
<h3>Send A Referral</h3>
<!-- Referral interface embedded here using spark-refer-embed class -->
[rewardstream_my_referrals]
</div>
<br />

<!-- Embeds a button for your customers to check their referral history -->
<div class="section-second">
<h3>Referral History</h3>
<p>See a list of referrals you&rsquo;ve made and the status of each referral:</p>
<!-- Referral activity statement opens here using spark-statement class -->
<a class="spark-statement"><button class="button" title="Referral History" type="submit">Referral History</button></a>
</div>';

		}

		$editor_id = 'my-referrals-page-content';
		//$editor_id = $this->plugin_name . '-options[my-referrals-page-content]';
		$content = $my_referrals_page_content;
		$settings = array(
			'wpautop' => false,
			'media_buttons' => false,
			'textarea_name' => $this->plugin_name . '-options[my-referrals-page-content]',
		);

		wp_editor( $content, $editor_id, $settings );
		?>


<?php /*		<textarea id="<?php echo $this->plugin_name; ?>-options[my-referrals-page-content]"
		            class="rs-textarea"
		            name="<?php echo $this->plugin_name; ?>-options[my-referrals-page-content]"><?php echo esc_attr( $my_referrals_page_content ); ?></textarea><br/> */ ?>
		<p><?php echo __( 'This is the HTML content that will appear on the My Referrals page where your customers can make referrals and check their referral history.', 'rewardstream' ); ?></p><?php
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

}
