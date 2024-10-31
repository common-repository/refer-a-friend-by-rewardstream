<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://rewardstream.com
 * @since      1.0.0
 *
 * @package    RewardStream
 * @subpackage RewardStream/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    RewardStream
 * @subpackage RewardStream/includes
 * @author     Daniel Espinoza <daniel@growdevelopment.com>
 */
class RewardStream {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      RewardStream_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $RewardStream    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'rewardstream';
		$this->custom_endpoint = 'my-referrals';
		$this->version = '0.5.11';



		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - RewardStream_Loader. Orchestrates the hooks of the plugin.
	 * - RewardStream_i18n. Defines internationalization functionality.
	 * - RewardStream_Admin. Defines all hooks for the admin area.
	 * - RewardStream_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-rewardstream-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-rewardstream-i18n.php';

		/**
		 * The class responsible for defining all actions that occur requiring the API
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-rewardstream-api.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-rewardstream-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-rewardstream-public.php';

		$this->loader = new RewardStream_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the RewardStream_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new RewardStream_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new RewardStream_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_filter( 'plugin_row_meta', $plugin_admin, 'add_support_link', 10, 2 );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_filter( 'plugin_action_links_rewardstream-for-woocommerce/rewardstream-for-woocommerce.php' , $plugin_admin, 'settings_link' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );

		// Add custom endpoint

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		// Get API Settings
		$program_domain = $this->get_option( 'program-domain' );
		$api_key = $this->get_option( 'api-key' );
		$api_secret = $this->get_option( 'api-secret' );
		$api_public = new RewardStream_API( $program_domain, $api_key, $api_secret );
		$plugin_public = new RewardStream_Public( $this->get_plugin_name(), $this->get_version(), $api_public, $this->custom_endpoint );

		$this->loader->add_action( 'init', $plugin_public, 'register_shortcode' );
		$this->loader->add_action( 'init', $plugin_public, 'add_endpoints', 10 );
		$this->loader->add_action( 'wp_loaded', $plugin_public, 'check_for_rewardstream_coupon', 5 );
		$this->loader->add_action( 'wp', $plugin_public, 'maybe_call_sync_member_data' );
		$this->loader->add_action( 'wp', $plugin_public, 'maybe_redirect_to_login', 10 );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'woocommerce_before_my_account', $plugin_public, 'add_refer_link' );
		$this->loader->add_action( 'woocommerce_order_status_completed', $plugin_public, 'complete_purchase' );
		$this->loader->add_action( 'woocommerce_thankyou', $plugin_public, 'order_received_html', 5 );
		$this->loader->add_action( 'woocommerce_account_' . $this->custom_endpoint .  '_endpoint', $plugin_public, 'endpoint_content' );
		$this->loader->add_filter( 'query_vars', $plugin_public, 'add_query_vars', 0 );
		$this->loader->add_filter( 'the_title', $plugin_public, 'endpoint_title', 0 );
		$this->loader->add_filter( 'woocommerce_account_menu_items', $plugin_public, 'new_menu_items' );
		$this->loader->add_filter( 'user_register', $plugin_public, 'check_for_past_purchases', 40 );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    RewardStream_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
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


	public static function create_page( $slug, $page_title='', $page_content = '', $post_parent = 0 ) {
		global $wpdb;

		$trashed_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status = 'trash' AND post_name = %s LIMIT 1;", $slug ) );

		if ( $trashed_page_found ) {
			$page_id   = $trashed_page_found;
			$page_data = array(
				'ID'          => $page_id,
				'post_status' => 'draft',
			);
			wp_update_post( $page_data );

		} else {

			$page_data = array(
				'post_status'    => 'draft',
				'post_type'      => 'page',
				'post_author'    => 1,
				'post_name'      => $slug,
				'post_title'     => $page_title,
				'post_content'   => $page_content,
				'post_parent'    => $post_parent,
				'comment_status' => 'closed'
			);
			$page_id = wp_insert_post( $page_data );
		}

		return $page_id;
	}

}
