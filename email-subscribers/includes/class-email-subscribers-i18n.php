<?php
/**
 * Localiztion
 *
 * @package Email Subscribers
 */

/**
 * Define the internationalization functionality
 *
 * Class Email_Subscribers_I18n
 */
class Email_Subscribers_I18n {

	/**
	 * Load plugin text domain
	 *
	 * @since 4.0.10
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'email-subscribers', false, dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}

}
