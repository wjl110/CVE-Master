<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'IG_Logger' ) ) {

	class IG_Logger implements IG_Logger_Interface {

		protected $handlers;

		protected $threshold;

		public function __construct( $handlers = null, $threshold = null ) {

			$handlers = array( new IG_Log_Handler_File() );

			$register_handlers = array();

			if ( ! empty( $handlers ) && is_array( $handlers ) ) {
				foreach ( $handlers as $handler ) {
					$implements = class_implements( $handler );

					if ( is_object( $handler ) && is_array( $implements ) && in_array( 'IG_Log_Handler_Interface', $implements, true ) ) {
						$register_handlers[] = $handler;
					} else {
					}
				}
			}

			if ( null !== $threshold ) {
				$threshold = IG_Log_Levels::get_level_severity( $threshold );
			} elseif ( defined( 'IG_LOG_THRESHOLD' ) && IG_Log_Levels::is_valid_level( IG_LOG_THRESHOLD ) ) {
				$threshold = IG_Log_Levels::get_level_severity( IG_LOG_THRESHOLD );
			} else {
				$threshold = null;
			}

			$this->handlers  = $register_handlers;
			$this->threshold = $threshold;
		}

		protected function should_handle( $level ) {
			if ( null === $this->threshold ) {
				return true;
			}

			return $this->threshold <= IG_Log_Levels::get_level_severity( $level );
		}

		/**
		 * Add a log entry.
		 *
		 * This is not the preferred method for adding log messages. Please use log() or any one of
		 * the level methods (debug(), info(), etc.). This method may be deprecated in the future.
		 *
		 * @param string $handle File handle.
		 * @param string $message Message to log.
		 * @param string $level Logging level.
		 *
		 * @return bool
		 */
		public function add( $handle, $message, $level = IG_Log_Levels::NOTICE ) {
			$message = apply_filters( 'ig_logger_add_message', $message, $handle );
			$this->log(
				$level,
				$message,
				array(
					'source'  => $handle,
					'_legacy' => true,
				)
			);

			return true;
		}

		/**
		 * Add a log entry.
		 *
		 * @param string $level One of the following:
		 *     'emergency': System is unusable.
		 *     'alert': Action must be taken immediately.
		 *     'critical': Critical conditions.
		 *     'error': Error conditions.
		 *     'warning': Warning conditions.
		 *     'notice': Normal but significant condition.
		 *     'info': Informational messages.
		 *     'debug': Debug-level messages.
		 * @param string $message Log message.
		 * @param array $context Optional. Additional information for log handlers.
		 */
		public function log( $level, $message, $context = array() ) {

			if ( $this->should_handle( $level ) ) {
				$timestamp = current_time( 'timestamp', 1 );
				$message   = apply_filters( 'ig_logger_log_message', $message, $level, $context );

				foreach ( $this->handlers as $handler ) {
					$handler->handle( $timestamp, $level, $message, $context );
				}
			}
		}

		/**
		 * Adds an emergency level message.
		 *
		 * System is unusable.
		 *
		 * @see IG_Logger::log
		 *
		 * @param string $message Message to log.
		 * @param array $context Log context.
		 */
		public function emergency( $message, $context = array() ) {
			$this->log( IG_Log_Levels::EMERGENCY, $message, $context );
		}

		/**
		 * Adds an alert level message.
		 *
		 * Action must be taken immediately.
		 * Example: Entire website down, database unavailable, etc.
		 *
		 * @see IG_Logger::log
		 *
		 * @param string $message Message to log.
		 * @param array $context Log context.
		 */
		public function alert( $message, $context = array() ) {
			$this->log( IG_Log_Levels::ALERT, $message, $context );
		}

		/**
		 * Adds a critical level message.
		 *
		 * Critical conditions.
		 * Example: Application component unavailable, unexpected exception.
		 *
		 * @see IG_Logger::log
		 *
		 * @param string $message Message to log.
		 * @param array $context Log context.
		 */
		public function critical( $message, $context = array() ) {
			$this->log( IG_Log_Levels::CRITICAL, $message, $context );
		}

		/**
		 * Adds an error level message.
		 *
		 * Runtime errors that do not require immediate action but should typically be logged
		 * and monitored.
		 *
		 * @see IG_Logger::log
		 *
		 * @param string $message Message to log.
		 * @param array $context Log context.
		 */
		public function error( $message, $context = array() ) {
			$this->log( IG_Log_Levels::ERROR, $message, $context );
		}

		/**
		 * Adds a warning level message.
		 *
		 * Exceptional occurrences that are not errors.
		 *
		 * Example: Use of deprecated APIs, poor use of an API, undesirable things that are not
		 * necessarily wrong.
		 *
		 * @see IG_Logger::log
		 *
		 * @param string $message Message to log.
		 * @param array $context Log context.
		 */
		public function warning( $message, $context = array() ) {
			$this->log( IG_Log_Levels::WARNING, $message, $context );
		}

		/**
		 * Adds a notice level message.
		 *
		 * Normal but significant events.
		 *
		 * @see IG_Logger::log
		 *
		 * @param string $message Message to log.
		 * @param array $context Log context.
		 */
		public function notice( $message, $context = array() ) {
			$this->log( IG_Log_Levels::NOTICE, $message, $context );
		}

		/**
		 * Adds a info level message.
		 *
		 * Interesting events.
		 * Example: User logs in, SQL logs.
		 *
		 * @see IG_Logger::log
		 *
		 * @param string $message Message to log.
		 * @param array $context Log context.
		 */
		public function info( $message, $context = array() ) {
			$this->log( IG_Log_Levels::INFO, $message, $context );
		}


		function trace( $message, $context = array() ) {

			$e = new \Exception();

			$trace = explode( "\n", $e->getTraceAsString() );

			// reverse array to make steps line up chronologically

			$trace = array_reverse( $trace );

			array_shift( $trace ); // remove {main}
			array_pop( $trace ); // remove call to this method

			$length = count( $trace );
			$result = array();

			for ( $i = 0; $i < $length; $i ++ ) {
				$result[] = ( $i + 1 ) . ')' . substr( $trace[ $i ], strpos( $trace[ $i ], ' ' ) ); // replace '#someNum' with '$i)', set the right ordering
			}

			$result = implode( "\n", $result );
			$result = "\n" . $result . "\n";

			$message .= $result;

			$this->log( IG_Log_Levels::INFO, $message, $context );

		}

		/**
		 * Adds a debug level message.
		 *
		 * Detailed debug information.
		 *
		 * @see IG_Logger::log
		 *
		 * @param string $message Message to log.
		 * @param array $context Log context.
		 */
		public function debug( $message, $context = array() ) {
			$this->log( IG_Log_Levels::DEBUG, $message, $context );
		}

		/**
		 * Clear entries for a chosen file/source.
		 *
		 * @param string $source Source/handle to clear.
		 *
		 * @return bool
		 */
		public function clear( $source = '' ) {
			if ( ! $source ) {
				return false;
			}
			foreach ( $this->handlers as $handler ) {
				if ( is_callable( array( $handler, 'clear' ) ) ) {
					$handler->clear( $source );
				}
			}

			return true;
		}


		/**
		 * Clear all logs older than a defined number of days. Defaults to 30 days.
		 *
		 * @since 3.4.0
		 */
		public function clear_expired_logs() {
			$days      = absint( apply_filters( 'ig_logger_days_to_retain_logs', 30 ) );
			$timestamp = strtotime( "-{$days} days" );

			foreach ( $this->handlers as $handler ) {
				if ( is_callable( array( $handler, 'delete_logs_before_timestamp' ) ) ) {
					$handler->delete_logs_before_timestamp( $timestamp );
				}
			}
		}
	}
}
