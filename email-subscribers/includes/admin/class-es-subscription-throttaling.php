<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Subscription_Throttaling {

	static function throttle() {

		global $wpdb;

		if ( ! ( is_user_logged_in() && is_super_admin() ) ) {
			$subscriber_ip = ig_es_get_ip();

			$whitelist_ips = array();
			$whitelist_ips = apply_filters( 'ig_es_whitelist_ips', $whitelist_ips );

			$blacklist_ips = array();
			$blacklist_ips = apply_filters( 'ig_es_blacklist_ips', $blacklist_ips );

			if ( ! ( empty( $subscriber_ip ) || ( is_array( $whitelist_ips ) && count( $whitelist_ips ) > 0 && in_array( $subscriber_ip, $whitelist_ips ) ) ) ) {

				if ( is_array( $blacklist_ips ) && count( $blacklist_ips ) > 0 && in_array( $subscriber_ip, $blacklist_ips ) ) {
					return MINUTE_IN_SECONDS * 10;
				}

				$query       = "SELECT count(*) as count from " . IG_CONTACTS_IPS_TABLE . " WHERE ip = %s AND ( `created_on` >= NOW() - INTERVAL %s SECOND )";
				$subscribers = $wpdb->get_var( $wpdb->prepare( $query, $subscriber_ip, DAY_IN_SECONDS ) );

				if ( $subscribers > 0 ) {
					$timeout = MINUTE_IN_SECONDS * pow( 2, $subscribers - 1 );

					$query       = "SELECT count(*) as count from " . IG_CONTACTS_IPS_TABLE . " WHERE ip = %s AND ( `created_on` >= NOW() - INTERVAL %s SECOND ) LIMIT 1";
					$subscribers = $wpdb->get_var( $wpdb->prepare( $query, $subscriber_ip, $timeout ) );

					if ( $subscribers > 0 ) {
						return $timeout;
					}
				}

				// Add IP Address.
				$query = "INSERT INTO " . IG_CONTACTS_IPS_TABLE . " (`ip`) VALUES ( %s )";
				$wpdb->query( $wpdb->prepare( $query, $subscriber_ip ) );

				// Delete older entries
				$query = "DELETE FROM " . IG_CONTACTS_IPS_TABLE . " WHERE (`created_on` < NOW() - INTERVAL %s SECOND )";
				$wpdb->query( $wpdb->prepare( $query, DAY_IN_SECONDS ) );
			}
		}

		return false;
	}

}