<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'get_ig_es_db_version' ) ) {
	/**
	 * Get current db version
	 *
	 * @since 4.0.0
	 */
	function get_ig_es_db_version() {

		$option = get_option( 'ig_es_db_version', null );

		if ( ! is_null( $option ) ) {
			return $option;
		}

		$option = get_option( 'current_sa_email_subscribers_db_version', null );

		return $option;

	}
}

if ( ! function_exists( 'ig_es_maybe_define_constant' ) ) {
	/**
	 * Define constant
	 *
	 * @param $name
	 * @param $value
	 *
	 * @since 4.0.0
	 */
	function ig_es_maybe_define_constant( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}
}

if ( ! function_exists( 'get_ig_logger' ) ) {
	/**
	 * Get IG Logger
	 *
	 * @return IG_Logger|string|null
	 *
	 */
	function get_ig_logger() {

		static $logger = null;

		$class = 'IG_Logger';

		if ( null !== $logger && is_string( $class ) && is_a( $logger, $class ) ) {
			return $logger;
		}

		$implements = class_implements( $class );

		if ( is_array( $implements ) && in_array( 'IG_Logger_Interface', $implements, true ) ) {
			$logger = is_object( $class ) ? $class : new $class();
		} else {
			$logger = is_a( $logger, 'IG_Logger' ) ? $logger : new IG_Logger();
		}

		return $logger;
	}
}

if ( ! function_exists( 'ig_get_current_date_time' ) ) {
	/**
	 * Get current date time
	 *
	 * @return false|string
	 */
	function ig_get_current_date_time() {
		return gmdate( 'Y-m-d H:i:s' );
	}
}

if ( ! function_exists( 'ig_es_get_current_gmt_timestamp' ) ) {
	/**
	 * Get current date time
	 *
	 * @return false|string
	 *
	 * @since 4.2.0
	 */
	function ig_es_get_current_gmt_timestamp() {
		return strtotime( gmdate( 'Y-m-d H:i:s' ) );
	}
}

if ( ! function_exists( 'ig_es_get_current_date' ) ) {
	/**
	 * Get current date
	 *
	 * @return false|string
	 *
	 * @since 4.1.15
	 */
	function ig_es_get_current_date() {
		return gmdate( 'Y-m-d' );
	}
}

if ( ! function_exists( 'ig_es_get_current_hour' ) ) {
	/**
	 * Get current hour
	 *
	 * @return false|string
	 *
	 * @since 4.1.15
	 */
	function ig_es_get_current_hour() {
		return gmdate( 'H' );
	}
}

if ( ! function_exists( 'ig_es_format_date_time' ) ) {
	/**
	 * Format date time
	 *
	 * @param $date
	 *
	 * @return string
	 */
	function ig_es_format_date_time( $date ) {
		$local_timestamp = ( $date !== '0000-00-00 00:00:00' ) ? get_date_from_gmt( $date ) : '<i class="dashicons dashicons-es dashicons-minus"></i>';

		return $local_timestamp;
	}
}

if ( ! function_exists( 'ig_es_convert_space_to_underscore' ) ) {
	/**
	 * Convert Space to underscore
	 *
	 * @param $string
	 *
	 * @return mixed
	 */
	function ig_es_convert_space_to_underscore( $string ) {
		return str_replace( ' ', '_', $string );
	}
}

if ( ! function_exists( 'ig_es_clean' ) ) {
	/**
	 * Clean String or array using sanitize_text_field
	 *
	 * @param $variable Data to sanitize
	 *
	 * @return array|string
	 *
	 * @since 4.1.15
	 */
	function ig_es_clean( $var ) {
		if ( is_array( $var ) ) {
			return array_map( 'ig_es_clean', $var );
		} else {
			return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
		}
	}
}

if ( ! function_exists( 'ig_es_check_invalid_utf8' ) ) {
	/**
	 * Function ig_check_invalid_utf8 with recursive array support.
	 *
	 * @param string|array $var Data to sanitize.
	 *
	 * @return string|array
	 *
	 * @since 4.1.15
	 */
	function ig_es_check_invalid_utf8( $var ) {
		if ( is_array( $var ) ) {
			return array_map( 'ig_es_check_invalid_utf8', $var );
		} else {
			return wp_check_invalid_utf8( $var );
		}
	}
}


if ( ! function_exists( 'ig_es_get_data' ) ) {
	/**
	 * Get data from array
	 *
	 * @param array $array
	 * @param string $var
	 * @param string $default
	 * @param bool $clean
	 *
	 * @return array|string
	 *
	 * @since 4.1.15
	 */
	function ig_es_get_data( $array = array(), $var = '', $default = '', $clean = false ) {

		if ( ! empty( $var ) ) {
			$value = isset( $array[ $var ] ) ? wp_unslash( $array[ $var ] ) : $default;
		} else {
			$value = wp_unslash( $array );
		}

		if ( $clean ) {
			$value = ig_es_clean( $value );
		}
		return $value;
	}
}

if ( ! function_exists( 'ig_es_get_request_data' ) ) {
	/**
	 * Get POST | GET data from $_REQUEST
	 *
	 * @param $var
	 *
	 * @return array|string
	 *
	 * @since 4.1.15
	 */
	function ig_es_get_request_data( $var = '', $default = '', $clean = true ) {
		return ig_es_get_data( $_REQUEST, $var, $default, $clean );
	}
}

if ( ! function_exists( 'ig_es_get_post_data' ) ) {
	/**
	 * Get POST data from $_POST
	 *
	 * @param $var
	 *
	 * @return array|string
	 *
	 * @since 4.1.15
	 */
	function ig_es_get_post_data( $var = '', $default = '', $clean = true ) {
		return ig_es_get_data( $_POST, $var, $default, $clean );
	}
}

if ( ! function_exists( 'ig_es_get_ip' ) ) {
	/**
	 * Get Contact IP
	 *
	 * @return mixed|string|void
	 *
	 * @since 4.2.0
	 */
	function ig_es_get_ip() {

		// Get real visitor IP behind CloudFlare network
		if ( isset( $_SERVER["HTTP_CF_CONNECTING_IP"] ) ) {
			$_SERVER['REMOTE_ADDR']    = $_SERVER["HTTP_CF_CONNECTING_IP"];
			$_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
		}

		$client  = @$_SERVER['HTTP_CLIENT_IP'];
		$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
		$remote  = $_SERVER['REMOTE_ADDR'];

		if ( filter_var( $client, FILTER_VALIDATE_IP ) ) {
			$ip = $client;
		} elseif ( filter_var( $forward, FILTER_VALIDATE_IP ) ) {
			$ip = $forward;
		} else {
			$ip = $remote;
		}

		return $ip;
	}
}

if ( ! function_exists( 'ig_es_encode_request_data' ) ) {
	/**
	 * Encode request data
	 *
	 * @param $data
	 *
	 * @return string
	 *
	 * @since 4.2.0
	 */
	function ig_es_encode_request_data( $data ) {
		return rtrim( base64_encode( json_encode( $data ) ), '=' );
	}
}

if ( ! function_exists( 'ig_es_decode_request_data' ) ) {
	/**
	 * Decode request data
	 *
	 * @param $data
	 *
	 * @return string
	 *
	 * @since 4.2.0
	 */
	function ig_es_decode_request_data( $data ) {
		$data = json_decode( base64_decode( $data ), true );
		if ( ! is_array( $data ) ) {
			$data = [];
		}

		return $data;
	}
}

if ( ! function_exists( 'ig_es_get_gmt_offset' ) ) {
	/**
	 * Get GMT Offset
	 *
	 * @param bool $in_seconds
	 * @param null $timestamp
	 *
	 * @return float|int
	 *
	 * @since 4.2.0
	 */
	function ig_es_get_gmt_offset( $in_seconds = false, $timestamp = null ) {

		$offset = get_option( 'gmt_offset' );

		if ( $offset == '' ) {
			$tzstring = get_option( 'timezone_string' );
			$current  = date_default_timezone_get();
			date_default_timezone_set( $tzstring );
			$offset = date( 'Z' ) / 3600;
			date_default_timezone_set( $current );
		}

		// check if timestamp has DST
		if ( ! is_null( $timestamp ) ) {
			$l = localtime( $timestamp, true );
			if ( $l['tm_isdst'] ) {
				$offset ++;
			}
		}

		return $in_seconds ? $offset * 3600 : (int) $offset;
	}
}

if ( ! function_exists( 'ig_es_get_upcoming_daily_datetime' ) ) {
	/**
	 * Get next daily run time
	 *
	 * @param $time
	 *
	 * @return false|int
	 *
	 * @since 4.2.0
	 */
	function ig_es_get_upcoming_daily_datetime( $time ) {

		$offset = ig_es_get_gmt_offset( true );
		$now    = time() + $offset;

		$year    = (int) date( 'Y', $now );
		$month   = (int) date( 'm', $now );
		$day     = (int) date( 'd', $now );
		$hour    = (int) date( 'H', $now );
		$minutes = (int) date( 'i', $now );
		$seconds = (int) date( 's', $now );

		$timestamp = ( $hour * 3600 ) + ( $minutes * 60 ) + $seconds;

		if ( $time < $timestamp ) {
			$day += 1;
		}

		$t = mktime( 0, 0, 0, $month, $day, $year ) + $time;

		return $t;
	}
}

if ( ! function_exists( 'ig_es_get_upcoming_weekly_datetime' ) ) {
	/**
	 * Get next weekly time
	 *
	 * @param $days_of_week
	 *
	 * @return false|int
	 *
	 * @since 4.2.0
	 */
	function ig_es_get_upcoming_weekly_datetime( $frequency_interval, $time ) {

		$week_days_map = array(
			0 => 'sunday',
			1 => 'monday',
			2 => 'tuesday',
			3 => 'wednesday',
			4 => 'thursday',
			5 => 'friday',
			6 => 'saturday'
		);

		$next_week_day_str = 'next ' . $week_days_map[ $frequency_interval ];

		$timestamp = strtotime( $next_week_day_str ) + $time;

		return $timestamp;

	}
}

if ( ! function_exists( 'ig_es_get_upcoming_monthly_datetime' ) ) {
	/**
	 * Get next monthly time
	 *
	 * @param $day_of_month
	 *
	 * @return false|int
	 *
	 * @since 4.2.0
	 */
	function ig_es_get_upcoming_monthly_datetime( $day, $time ) {

		$month = (int) date( 'm', time() );
		$year  = (int) date( 'Y', time() );

		$expected_time = strtotime( date( 'Y-m-d' ) ) + $time;

		if ( $expected_time < time() ) {

			$month = $month + 1;

			$expected_time = mktime( 0, 0, 0, $month, $day, $year ) + $time;

		}

		return $expected_time;

	}
}

if ( ! function_exists( 'ig_es_get_next_future_schedule_date' ) ) {
	/**
	 * Get future schedule date
	 *
	 * @param $utc_start
	 * @param $interval
	 * @param $time_frame
	 * @param array $weekdays
	 * @param bool $in_future
	 *
	 * @return false|float|int
	 *
	 * @since 4.2.0
	 */
	function ig_es_get_next_future_schedule_date( $data ) {

		$weekdays_array = array( '0', '1', '2', '3', '4', '5', '6' );

		$utc_start   = ! empty( $data['utc_start'] ) ? $data['utc_start'] : 0;
		$interval    = ! empty( $data['interval'] ) ? $data['interval'] : 1;
		$time_frame  = ! empty( $data['time_frame'] ) ? $data['time_frame'] : 'week';
		$weekdays    = ! empty( $data['weekdays'] ) ? $data['weekdays'] : $weekdays_array;
		$force       = ! empty( $data['force'] ) ? $data['force'] : false;
		$in_future   = ! empty( $data['in_future'] ) ? $data['in_future'] : true;
		$time_of_day = ! empty( $data['time_of_day'] ) ? $data['time_of_day'] : 32400;

		$offset    = ig_es_get_gmt_offset( true );
		$now       = time() + $offset;
		$utc_start += $offset;
		$times     = 1;

		$next_date        = '';
		$change_next_date = true;
		/**
		 * Start time should be in past
		 */
		if ( ( $in_future && $utc_start - $now < 0 ) || $force ) {
			// get how many $time_frame are in the time between now and the starttime
			switch ( $time_frame ) {
				case 'year':
					$count = date( 'Y', $now ) - date( 'Y', $utc_start );
					break;
				case 'month':
					$count = ( date( 'Y', $now ) - date( 'Y', $utc_start ) ) * 12 + ( date( 'm', $now ) - date( 'm', $utc_start ) );
					break;
				case 'week':
					$count = floor( ( ( $now - $utc_start ) / 86400 ) / 7 );
					break;
				case 'day':
					$count = floor( ( $now - $utc_start ) / 86400 );
					break;
				case 'hour':
					$count = floor( ( $now - $utc_start ) / 3600 );
					break;
				case 'immediately':
					$time_frame       = 'day';
					$next_date        = $now;
					$interval         = $count = 1;
					$change_next_date = false;
					break;
				case 'daily':
					$time_frame       = 'day';
					$next_date        = ig_es_get_upcoming_daily_datetime( $time_of_day );
					$interval         = $count = 1;
					$change_next_date = false;
					break;
				case 'weekly':
					$time_frame       = 'day';
					$next_date        = ig_es_get_upcoming_weekly_datetime( $interval, $time_of_day );
					$interval         = $count = 1;
					$change_next_date = false;
					break;
				case 'monthly':
					$time_frame       = 'day';
					$next_date        = ig_es_get_upcoming_monthly_datetime( $interval, $time_of_day );
					$interval         = $count = 1;
					$change_next_date = false;
					break;
				default:
					$count = $interval;
					break;
			}

			$times = $interval ? ceil( $count / $interval ) : 0;
		}

		// We have already got the next date for weekly & monthly
		if ( empty( $next_date ) ) {
			$next_date = strtotime( date( 'Y-m-d H:i:s', $utc_start ) . ' +' . ( $interval * $times ) . " {$time_frame}" );
		}

		// add a single entity if date is still in the past or just now
		if ( $in_future && ( $next_date - $now < 0 || $next_date == $utc_start ) && $change_next_date ) {
			$next_date = strtotime( date( 'Y-m-d H:i:s', $utc_start ) . ' +' . ( $interval * $times + $interval ) . " {$time_frame}" );
		}

		if ( ! empty( $weekdays ) && count( $weekdays ) < 7 ) {

			$day_of_week = date( 'w', $next_date );

			$i = 0;
			if ( ! $interval ) {
				$interval = 1;
			}

			/**
			 * If we can't send email to the specific weekday, schedule for next possible day.
			 */
			while ( ! in_array( $day_of_week, $weekdays ) ) {

				if ( 'week' == $time_frame ) {
					$next_date = strtotime( '+1 day', $next_date );
				} else {
					$next_date = strtotime( "+{$interval} {$time_frame}", $next_date );
				}

				$day_of_week = date( 'w', $next_date );

				// Force break
				if ( $i > 500 ) {
					break;
				}

				$i ++;
			}
		}

		// return as UTC
		return $next_date - $offset;

	}
}

