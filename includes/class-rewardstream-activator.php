<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    RewardStream
 * @subpackage RewardStream/includes
 * @author     Grow Development <daniel@growdevelopment.com>
 */
class RewardStream_Activator {

	/**
	 * Add Pages needed for the plugin
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		$rewardstream_refer_page_id     = get_option( ' rewardstream_refer_page_id' );

		if ( ! is_int( $rewardstream_refer_page_id ) ) {

			$image_url = REWARDSTREAM_PLUGIN_DIR_URL . '/public/images/refer_a_friend.jpg';
			$link = get_bloginfo( 'url' ) . "/my-account/my-referrals";
			$page_content = "<div class=\"spark-refer-embed\"></div>";

			$new_page = RewardStream::create_page( 'refer', __( 'Refer A Friend', 'rewardstream' ), $page_content, 0 );

			if ( ! is_wp_error( $new_page ) ){
				update_option( 'rewardstream_refer_page_id', $new_page );
			}
		}

		flush_rewrite_rules();

	}

}
