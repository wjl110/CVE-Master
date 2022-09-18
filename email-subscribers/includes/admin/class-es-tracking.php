<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Tracking' ) ) {
	/**
	 * Class ES_Tracking
	 *
	 * Track Activities like Subscribe, Open, Click, Unsubscribe
	 *
	 * @since 4.2.0
	 */
	class ES_Tracking {
		/**
		 * ES_Actions constructor.
		 *
		 * @since 4.2.0
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'init' ), 1 );
		}

		/**
		 * Track user interaction
		 *
		 * @since 4.2.0
		 */
		public function init() {

		}

	}
}




