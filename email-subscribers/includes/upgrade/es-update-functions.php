<?php
/**
 * Functions for updating data, used by the background updater.
 */

defined( 'ABSPATH' ) || exit;


/* --------------------- ES 3.2.0 (Start)--------------------------- */
/**
 * To update sync email option to remove Commented user & it's group - ig_es_sync_wp_users
 * ES version 3.2 onwards
 */
function ig_es_update_320_add_sync_option() {

	$sync_subscribers = get_option( 'ig_es_sync_wp_users' );

	$es_unserialized_data = maybe_unserialize( $sync_subscribers );
	unset( $es_unserialized_data['es_commented'] );
	unset( $es_unserialized_data['es_commented_group'] );

	$es_serialized_data = serialize( $es_unserialized_data );
	update_option( 'ig_es_sync_wp_users', $es_serialized_data );
}

function ig_es_update_320_db_version() {
	ES_Install::update_db_version( '3.2.0' );
}

/* --------------------- ES 3.2.0 (End)--------------------------- */

/* --------------------- ES 3.2.7 (Start)--------------------------- */
/**
 * To rename a few terms in compose & reports menu
 * ES version 3.2.7 onwards
 */
function ig_es_update_327_change_email_type() {

	global $wpdb;

	// Compose table
	$template_table_exists = $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}es_templatetable'" );
	if ( $template_table_exists > 0 ) {
		$wpdb->query( "UPDATE {$wpdb->prefix}es_templatetable
						   SET es_email_type =
						   ( CASE
								WHEN es_email_type = 'Static Template' THEN 'Newsletter'
								WHEN es_email_type = 'Dynamic Template' THEN 'Post Notification'
								ELSE es_email_type
							 END ) " );
	}

	// Sent Details table
	$wpdb->query( "UPDATE {$wpdb->prefix}es_sentdetails
					   SET es_sent_type =
					   ( CASE
							WHEN es_sent_type = 'Instant Mail' THEN 'Immediately'
							WHEN es_sent_type = 'Cron Mail' THEN 'Cron'
							ELSE es_sent_type
						 END ),
						   es_sent_source =
					   ( CASE
							WHEN es_sent_source = 'manual' THEN 'Newsletter'
							WHEN es_sent_source = 'notification' THEN 'Post Notification'
							ELSE es_sent_source
					   END ) " );

	// Delivery Reports table
	$wpdb->query( "UPDATE {$wpdb->prefix}es_deliverreport
					   SET es_deliver_senttype =
					   ( CASE
							WHEN es_deliver_senttype = 'Instant Mail' THEN 'Immediately'
							WHEN es_deliver_senttype = 'Cron Mail' THEN 'Cron'
							ELSE es_deliver_senttype
						 END ) " );


}

function ig_es_update_327_db_version() {
	ES_Install::update_db_version( '3.2.7' );
}

/* --------------------- ES 3.2.7 (End)--------------------------- */

/* --------------------- ES 3.3.6 (Start)--------------------------- */
/**
 * To migrate Email Settings data from custom pluginconfig table to wordpress options table and to update user roles
 * ES version 3.3 onwards
 */
function ig_es_update_330_import_options() {
	global $wpdb;

	$settings_to_rename = array(
		'es_c_fromname'         => 'ig_es_fromname',
		'es_c_fromemail'        => 'ig_es_fromemail',
		'es_c_mailtype'         => 'ig_es_emailtype',
		'es_c_adminmailoption'  => 'ig_es_notifyadmin',
		'es_c_adminemail'       => 'ig_es_adminemail',
		'es_c_adminmailsubject' => 'ig_es_admin_new_sub_subject',
		'es_c_adminmailcontant' => 'ig_es_admin_new_sub_content',
		'es_c_usermailoption'   => 'ig_es_welcomeemail',
		'es_c_usermailsubject'  => 'ig_es_welcomesubject',
		'es_c_usermailcontant'  => 'ig_es_welcomecontent',
		'es_c_optinoption'      => 'ig_es_optintype',
		'es_c_optinsubject'     => 'ig_es_confirmsubject',
		'es_c_optincontent'     => 'ig_es_confirmcontent',
		'es_c_optinlink'        => 'ig_es_optinlink',
		'es_c_unsublink'        => 'ig_es_unsublink',
		'es_c_unsubtext'        => 'ig_es_unsubcontent',
		'es_c_unsubhtml'        => 'ig_es_unsubtext',
		'es_c_subhtml'          => 'ig_es_successmsg',
		'es_c_message1'         => 'ig_es_suberror',
		'es_c_message2'         => 'ig_es_unsuberror',
	);

	$options_to_rename = array(
		'es_c_post_image_size'      => 'ig_es_post_image_size',
		'es_c_sentreport'           => 'ig_es_sentreport',
		'es_c_sentreport_subject'   => 'ig_es_sentreport_subject',
		'es_c_rolesandcapabilities' => 'ig_es_rolesandcapabilities',
		'es_c_cronurl'              => 'ig_es_cronurl',
		'es_cron_mailcount'         => 'ig_es_cron_mailcount',
		'es_cron_adminmail'         => 'ig_es_cron_adminmail',
		'es_c_emailsubscribers'     => 'ig_es_sync_wp_users',
	);

	// Rename options that were previously stored
	foreach ( $options_to_rename as $old_option_name => $new_option_name ) {
		$option_value = get_option( $old_option_name );
		if ( $option_value ) {
			update_option( $new_option_name, $option_value );
			delete_option( $old_option_name );
		}
	}

	// Do not pull data for new users as there is no pluginconfig table created on activation
	$table_exists = $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}es_pluginconfig'" );

	if ( $table_exists > 0 ) {
		global $wpdb;
		// Pull out ES settings data of existing users and move them to options table
		$es_get_settings_data = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}es_pluginconfig WHERE es_c_id = %d", 1 );
		$settings_data        = $wpdb->get_row( $es_get_settings_data, ARRAY_A );

		if ( ! empty( $settings_data ) ) {
			foreach ( $settings_data as $name => $value ) {
				if ( array_key_exists( $name, $settings_to_rename ) ) {
					update_option( $settings_to_rename[ $name ], $value );
				}
			}
		}
	}

	//Update User Roles Settings
	$es_c_rolesandcapabilities = get_option( 'ig_es_rolesandcapabilities', 'norecord' );

	if ( $es_c_rolesandcapabilities != 'norecord' ) {
		$remove_roles = array( 'es_roles_setting', 'es_roles_help' );
		foreach ( $es_c_rolesandcapabilities as $role_name => $role_value ) {
			if ( in_array( $role_name, $remove_roles ) ) {
				unset( $es_c_rolesandcapabilities[ $role_name ] );
			}
		}
		update_option( 'ig_es_rolesandcapabilities', $es_c_rolesandcapabilities );
	}

}

function ig_es_update_330_db_version() {
	ES_Install::update_db_version( '3.3.0' );
}

/* --------------------- ES 3.3.0 (End)--------------------------- */

/* --------------------- ES 3.3.6 (Start)--------------------------- */
/**
 * To alter templatable for extra slug column - to support new template designs
 * ES version 3.3.6 onwards
 */
function ig_es_update_336_add_template_slug() {

	global $wpdb;

	$template_table_exists = $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}es_templatetable'" );
	if ( $template_table_exists > 0 ) {

		// To check if column es_templ_slug exists or not
		$es_template_col      = "SHOW COLUMNS FROM {$wpdb->prefix}es_templatetable LIKE 'es_templ_slug' ";
		$results_template_col = $wpdb->get_results( $es_template_col, 'ARRAY_A' );
		$template_num_rows    = $wpdb->num_rows;

		// If column doesn't exists, then insert it
		if ( $template_num_rows != '1' ) {
			// Template table
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}es_templatetable
								ADD COLUMN es_templ_slug VARCHAR(255) NULL
								AFTER es_email_type" );
		}
	}
}

function ig_es_update_336_db_version() {
	ES_Install::update_db_version( '3.3.6' );
}

/* --------------------- ES 3.3.6 (End)--------------------------- */

/* --------------------- ES 3.4.0 (Start)--------------------------- */
/**
 * To convert Compose to Custom Post Type (to support new template designs) AND Converting keywords structure
 * ES version 3.4.0 onwards
 */
function ig_es_update_340_migrate_templates_to_cpt() {

	global $wpdb;

	// MIGRATION OF TEMPLATE TABLE TO CTP
	$es_template_table_exists = $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}es_templatetable'" );
	if ( $es_template_table_exists > 0 ) {

		$es_migration_success = get_option( 'es_template_migration_done', 'nodata' );
		if ( $es_migration_success == 'yes' ) {
			return;
		}

		$sSql   = "SELECT es_tt.*,
							 IFNULL(es_not.es_note_id, '') as es_note_id
					FROM {$wpdb->prefix}es_templatetable AS es_tt
					LEFT JOIN {$wpdb->prefix}es_notification AS es_not
						ON(es_not.es_note_templ = es_tt.es_templ_id)";
		$arrRes = $wpdb->get_results( $sSql, ARRAY_A );

		if ( ! empty( $arrRes ) ) {

			$es_note_ids = array();

			foreach ( $arrRes as $tmpl ) {
				// Create post object
				$es_post = array(
					'post_title'   => wp_strip_all_tags( $tmpl['es_templ_heading'] ),
					'post_content' => $tmpl['es_templ_body'],
					'post_status'  => 'publish',
					'post_type'    => 'es_template',
					'meta_input'   => array(
						'es_template_type' => $tmpl['es_email_type']
					)
				);
				// Insert the post into the database
				$last_inserted_id = wp_insert_post( $es_post );

				if ( $tmpl['es_email_type'] == 'Post Notification' && ! empty( $tmpl['es_note_id'] ) ) {
					$es_note_ids[] = 'WHEN es_note_id = ' . $tmpl['es_note_id'] . ' THEN ' . $last_inserted_id;
				}
			}

			if ( ! empty( $es_note_ids ) ) {
				// To update the 'es_note_templ' ids
				$sSql = "UPDATE {$wpdb->prefix}es_notification SET es_note_templ = (CASE " . implode( " ", $es_note_ids ) . " END)";
				$wpdb->query( $sSql );
			}

		}

		update_option( 'es_template_migration_done', 'yes' );
	}
}

function ig_es_update_340_migrate_keywords() {
	global $wpdb;
	// Keywords in Compose table
	$keywords_to_rename_in_compose = array(
		'###NAME###'               => '{{NAME}}',
		'###EMAIL###'              => '{{EMAIL}}',
		'###DATE###'               => '{{DATE}}',
		'###POSTTITLE###'          => '{{POSTTITLE}}',
		'###POSTIMAGE###'          => '{{POSTIMAGE}}',
		'###POSTDESC###'           => '{{POSTDESC}}',
		'###POSTFULL###'           => '{{POSTFULL}}',
		'###POSTAUTHOR###'         => '{{POSTAUTHOR}}',
		'###POSTLINK###'           => '{{POSTLINK}}',
		'###POSTLINK-ONLY###'      => '{{POSTLINK-ONLY}}',
		'###POSTLINK-WITHTITLE###' => '{{POSTLINK-WITHTITLE}}',
	);

	// Keywords in Settings
	$keywords_in_settings_to_rename = array(
		'###NAME###'      => '{{NAME}}',
		'###EMAIL###'     => '{{EMAIL}}',
		'###GROUP###'     => '{{GROUP}}',
		'###COUNT###'     => '{{COUNT}}',
		'###UNIQUE###'    => '{{UNIQUE}}',
		'###STARTTIME###' => '{{STARTTIME}}',
		'###ENDTIME###'   => '{{ENDTIME}}',
		'###LINK###'      => '{{LINK}}',
		'###DATE###'      => '{{DATE}}',
		'###SUBJECT###'   => '{{SUBJECT}}',
		'###DBID###'      => '{{DBID}}',
		'###GUID###'      => '{{GUID}}',
	);

	// Updating keywords in post_title column where `post_type` = 'es_template'
	$es_post_title_query = "UPDATE {$wpdb->prefix}posts SET post_title = REPLACE(post_title,'###POSTTITLE###','{{POSTTITLE}}') WHERE post_type = 'es_template'";
	$wpdb->query( $es_post_title_query );

	// Updating keywords in post_content column where `post_type` = 'es_template'
	$compose_keywords = array();
	foreach ( $keywords_to_rename_in_compose as $key => $value ) {
		$compose_keywords[] = "post_content = REPLACE(post_content,'" . $key . "','" . $value . "')";
	}

	$es_post_content_query = "UPDATE {$wpdb->prefix}posts SET " . implode( ", ", $compose_keywords ) . " WHERE post_type = 'es_template'";
	$wpdb->query( $es_post_content_query );

	// Updating keywords in options
	$es_admin_new_sub_content = get_option( 'ig_es_admin_new_sub_content', 'nodata' );
	$es_sent_report_content   = get_option( 'ig_es_sentreport', 'nodata' );
	$es_confirm_content       = get_option( 'ig_es_confirmcontent', 'nodata' );
	$es_welcome_content       = get_option( 'ig_es_welcomecontent', 'nodata' );
	$es_unsub_content         = get_option( 'ig_es_unsubcontent', 'nodata' );
	$es_cron_admin_mail       = get_option( 'ig_es_cron_adminmail', 'nodata' );
	$es_optin_link            = get_option( 'ig_es_optinlink', 'nodata' );
	$es_unsub_link            = get_option( 'ig_es_unsublink', 'nodata' );

	foreach ( $keywords_in_settings_to_rename as $key => $value ) {
		if ( $es_admin_new_sub_content != 'nodata' ) {
			$es_admin_new_sub_content = str_replace( $key, $value, $es_admin_new_sub_content );
			update_option( 'ig_es_admin_new_sub_content', $es_admin_new_sub_content );
		}

		if ( $es_sent_report_content != 'nodata' ) {
			$es_sent_report_content = str_replace( $key, $value, $es_sent_report_content );
			update_option( 'ig_es_sentreport', $es_sent_report_content );
		}

		if ( $es_confirm_content != 'nodata' ) {
			$es_confirm_content = str_replace( $key, $value, $es_confirm_content );
			update_option( 'ig_es_confirmcontent', $es_confirm_content );
		}

		if ( $es_welcome_content != 'nodata' ) {
			$es_welcome_content = str_replace( $key, $value, $es_welcome_content );
			update_option( 'ig_es_welcomecontent', $es_welcome_content );
		}

		if ( $es_unsub_content != 'nodata' ) {
			$es_unsub_content = str_replace( $key, $value, $es_unsub_content );
			update_option( 'ig_es_unsubcontent', $es_unsub_content );
		}

		if ( $es_cron_admin_mail != 'nodata' ) {
			$es_cron_admin_mail = str_replace( $key, $value, $es_cron_admin_mail );
			update_option( 'ig_es_cron_adminmail', $es_cron_admin_mail );
		}

		if ( $es_optin_link != 'nodata' ) {
			$es_optin_link = str_replace( $key, $value, $es_optin_link );
			update_option( 'ig_es_optinlink', $es_optin_link );
		}

		if ( $es_unsub_link != 'nodata' ) {
			$es_unsub_link = str_replace( $key, $value, $es_unsub_link );
			update_option( 'ig_es_unsublink', $es_unsub_link );
		}
	}

}

function ig_es_update_340_db_version() {
	ES_Install::update_db_version( '3.4.0' );


}

/* --------------------- ES 3.4.0 (End)--------------------------- */

/* --------------------- ES 3.5.16(Start)--------------------------- */
/**
 * Add es_subscriber_ips table to handle rate limit.
 * ES version 3.5.16 onwards
 */
function ig_es_update_3516_create_subscribers_ips_table() {

	global $wpdb;

	$charset_collate         = $wpdb->get_charset_collate();
	$es_subscriber_ips_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}es_subscriber_ips (
									ip varchar(45) NOT NULL, 
									created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
									PRIMARY KEY  (created_at, ip),
									KEY ip (ip)
							  ) $charset_collate";


	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $es_subscriber_ips_table );
}

function ig_es_update_3516_db_version() {
	ES_Install::update_db_version( '3.5.16' );
}

/* --------------------- ES 3.5.16 (End)--------------------------- */


/* --------------------- ES 4.0.0 (Start)--------------------------- */
function ig_es_update_400_delete_tables() {
	global $wpdb;

	$tables_to_delete = array(
		$wpdb->prefix . 'ig_blocked_emails',
		$wpdb->prefix . 'ig_campaigns',
		//$wpdb->prefix . 'ig_contacts',
		$wpdb->prefix . 'ig_contacts_ips',
		$wpdb->prefix . 'ig_forms',
		$wpdb->prefix . 'ig_lists',
		$wpdb->prefix . 'ig_lists_contacts',
		$wpdb->prefix . 'ig_mailing_queue',
		$wpdb->prefix . 'ig_sending_queue',
		$wpdb->prefix . 'ig_queue',
		$wpdb->prefix . 'ig_actions'
	);

	foreach ( $tables_to_delete as $table ) {
		$query = "DROP TABLE IF EXISTS {$table}";
		$wpdb->query( $query );
	}
}

function ig_es_update_400_create_tables() {
	global $wpdb;

	$wpdb->hide_errors();
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( ES_Install::get_ig_es_400_schema() );

}

function ig_es_update_400_import_options() {

	$options = ES_Install::get_options();
	foreach ( $options as $option => $data ) {
		if ( ! empty( $data['old_option'] ) ) {
			$value = get_option( $data['old_option'], '--NOT-FOUND--' );

			// Don't find the value? Get default.
			if ( '--NOT-FOUND--' === $value ) {
				$value = $data['default'];
			}

			if ( ! empty( $data['action'] ) && $data['action'] === 'convert_space_to_underscore' ) {
				$value = strtolower( ig_es_convert_space_to_underscore( $value ) );
			}

			update_option( $option, $value );
		}
	}
}

function ig_es_update_400_migrate_lists() {
	/**
	 *  Get Unique groups from es_emaillist table and import it into ig_lists table
	 */
	global $wpdb;

	// Collect list name from Email list
	$sql   = "SELECT distinct(es_email_group) FROM " . ES_EMAILLIST_TABLE;
	$lists = $wpdb->get_col( $sql );

	// Collect list name from notification table
	$sql      = "SELECT distinct(es_note_group) FROM " . EMAIL_SUBSCRIBERS_NOTIFICATION_TABLE;
	$ps_lists = $wpdb->get_col( $sql );

	if ( count( $lists ) > 0 || count( $ps_lists ) > 0 ) {
		$all_lists = array_unique( array_merge( $lists, $ps_lists ) );
		ES()->lists_db->add_lists( $all_lists );
	}

}

// Import contacts from es_emaillist table to ig_contacts and ig_lists_contacts table
function ig_es_update_400_migrate_subscribers() {
	ES_DB_Contacts::migrate_subscribers_from_older_version();
}

function ig_es_update_400_migrate_post_notifications() {
	ES()->campaigns_db->migrate_post_notifications();
}


function ig_es_update_400_migrate_notifications() {
	/**
	 * - Migrate notifications from es_notification to ig_es_mailing_queue table
	 * es_notification => ig_es_mailing_queue
	 */

	ES_DB_Mailing_Queue::migrate_notifications();
}

function ig_es_update_400_migrate_reports_data() {

	/**
	 * - Migrate individual notification data from es_deliverreport to ig_es_sending_queue table
	 * es_deliverreport => ig_es_sending_queue
	 */
	//@ini_set( 'max_execution_time', 0 );
	//ES_DB_Sending_Queue::migrate_reports_data();

}

function ig_es_update_400_migrate_group_selectors_forms() {
	ES()->forms_db->migrate_advanced_forms();
}

function ig_es_update_400_db_version() {
	ES_Install::update_db_version( '4.0.0' );
}

/* --------------------- ES 4.0.0 (End)--------------------------- */

function ig_es_update_401_migrate_newsletters() {
	// Migrate newsletters from es_sentdetails table
	ES()->campaigns_db->migrate_newsletters();
}

function ig_es_update_401_db_version() {
	ES_Install::update_db_version( '4.0.1' );
}

/* --------------------- ES 4.0.1 (End)--------------------------- */

/**
 * Change es_template_type from "Post Notification" to "post_notification"
 */
function ig_es_update_402_migrate_post_notification_es_template_type() {
	ES_DB_Notifications::migrate_post_notification_es_template_type();
}

function ig_es_update_402_db_version() {
	ES_Install::update_db_version( '4.0.2' );
}

/* --------------------- ES 4.0.2(End)--------------------------- */

function ig_es_update_403_alter_campaigns_table() {
	global $wpdb;

	$query = "ALTER TABLE {$wpdb->prefix}ig_campaigns MODIFY `name` varchar(255) DEFAULT NULL";
	$wpdb->query( $query );

	$query = "ALTER TABLE {$wpdb->prefix}ig_campaigns MODIFY `slug` varchar(255) DEFAULT NULL";

	$wpdb->query( $query );
}

function ig_es_update_403_alter_mailing_queue_table() {
	global $wpdb;

	$query = "ALTER TABLE {$wpdb->prefix}ig_mailing_queue MODIFY `subject` text DEFAULT ''";
	$wpdb->query( $query );
}

function ig_es_update_403_db_version() {
	ES_Install::update_db_version( '4.0.3' );
}

/* --------------------- ES 4.0.3(End)--------------------------- */

function ig_es_update_405_alter_forms_table() {
	global $wpdb;

	$query = "ALTER TABLE {$wpdb->prefix}ig_forms MODIFY `name` varchar(255) DEFAULT NULL";
	$wpdb->query( $query );
}

function ig_es_update_405_alter_lists_table() {
	global $wpdb;

	$query = "ALTER TABLE {$wpdb->prefix}ig_lists MODIFY `name` varchar(255) DEFAULT NULL";
	$wpdb->query( $query );

	$query = "ALTER TABLE {$wpdb->prefix}ig_lists MODIFY `slug` varchar(255) DEFAULT NULL";

	$wpdb->query( $query );
}

function ig_es_update_405_migrate_widgets() {
	ES_Common::migrate_widgets();
}


function ig_es_update_405_db_version() {
	ES_Install::update_db_version( '4.0.5' );
}

/* --------------------- ES 4.0.5(End)--------------------------- */
function ig_es_update_4010_update_sending_status() {
	global $wpdb;

	$sql   = "UPDATE {$wpdb->prefix}ig_sending_queue SET status = %s WHERE status = %s";
	$query = $wpdb->prepare( $sql, array( 'Sent', 'Nodata' ) );
	$wpdb->query( $query );
}

function ig_es_update_4010_db_version() {
	ES_Install::update_db_version( '4.0.10' );
}

/* --------------------- ES 4.0.10(End)--------------------------- */

function ig_es_update_4011_migrate_newsletter_es_template_type() {
	ES_DB_Notifications::migrate_newsletter_es_template_type();
}

function ig_es_update_4011_update_campaign_id_in_mailing_queue() {
	ES()->campaigns_db->update_campaign_id_in_mailing_queue();
}

function ig_es_update_4011_db_version() {
	ES_Install::update_db_version( '4.0.11' );
}

/* --------------------- ES 4.0.11(End)--------------------------- */

function ig_es_update_4015_alter_blocked_emails_table() {
	global $wpdb;

	$query = "ALTER TABLE {$wpdb->prefix}ig_blocked_emails MODIFY `id` int(10) NOT NULL AUTO_INCREMENT";
	$wpdb->query( $query );
}

function ig_es_update_4015_db_version() {
	ES_Install::update_db_version( '4.0.15' );
}

/* --------------------- ES 4.0.15(End)--------------------------- */
function ig_es_update_411_alter_contacts_table() {
	global $wpdb;

	$query = "ALTER TABLE {$wpdb->prefix}ig_contacts MODIFY `source` varchar(50) DEFAULT NULL";
	$wpdb->query( $query );
}

function ig_es_update_411_db_version() {
	ES_Install::update_db_version( '4.1.1' );
}

/* --------------------- ES 4.1.1(End)--------------------------- */
function ig_es_update_417_alter_campaigns_table() {
	global $wpdb;

	$campaigns_table = IG_CAMPAIGNS_TABLE;

	$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$campaigns_table}" );
	if ( ! in_array( 'meta', $cols ) ) {
		$query = "ALTER TABLE {$campaigns_table} ADD COLUMN meta longtext NULL AFTER `status`";
		$wpdb->query( $query );
	}
}

function ig_es_update_417_alter_mailing_queue_table() {
	global $wpdb;

	$mailing_queue_table = IG_MAILING_QUEUE_TABLE;

	$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$mailing_queue_table}" );
	if ( ! in_array( 'meta', $cols ) ) {
		$query = "ALTER TABLE {$mailing_queue_table} ADD COLUMN meta longtext NULL AFTER `finish_at`";
		$wpdb->query( $query );
	}
}

function ig_es_update_417_db_version() {
	ES_Install::update_db_version( '4.1.7' );
}

/* --------------------- ES 4.1.7(End)--------------------------- */

/**
 * Migrate categories names into category ids
 *
 * @since 4.1.13
 */
function ig_es_update_4113_migrate_categories_in_campaigns_table() {
	global $wpdb;
	$campaigns_table       = IG_CAMPAIGNS_TABLE;
	$query                 = "SELECT id, categories FROM {$campaigns_table} where type='post_notification' ";
	$campaign_category_map = $wpdb->get_results( $query, ARRAY_A );

	if ( ! empty( $campaign_category_map ) ) {
		foreach ( $campaign_category_map as $value ) {
			$categories_str = ES_Common::prepare_categories_migration_string( $value['categories'] );
			//remove category with 0
			$categories_str = str_replace( '##0', '', $categories_str );
			$categories[]   = " WHEN categories = '" . esc_sql( $value['categories'] ) . "' THEN '" . $categories_str . "'";
		}

		$update_query = "UPDATE {$campaigns_table} SET categories = (CASE " . implode( ' ', $categories ) . " ELSE categories END)";
		$wpdb->query( $update_query );
	}
}

/**
 * Create different files.
 *
 * @since 4.1.13
 */
function ig_es_update_4113_create_files() {
	ES_Install::create_files();
}

/**
 * Add DB update time
 *
 * @since 4.1.13
 */
function ig_es_update_4113_db_version() {
	ES_Install::update_db_version( '4.1.13' );
}

/* --------------------- ES 4.1.13(End)--------------------------- */

/**
 * Migrate db_updated_at options into new structure
 *
 * @since 4.1.15
 */
function ig_es_update_4115_migrate_db_update_history() {

	$db_update_history_option = 'db_update_history';
	$db_update_history_data   = ES_Common::get_ig_option( $db_update_history_option, array() );

	/**
	 * We have already created these many options to store date and time of individual
	 * version installation. Now, we are merging into single option
	 */
	$db_update_at_options = array(
		'4.0.0'  => '400_db_updated_at',
		'4.0.1'  => '401_db_updated_at',
		'4.0.2'  => '402_db_updated_at',
		'4.0.3'  => '403_db_updated_at',
		'4.0.5'  => '405_db_updated_at',
		'4.0.10' => '4010_db_updated_at',
		'4.0.11' => '4011_db_updated_at',
		'4.0.15' => '4015_db_updated_at',
		'4.1.1'  => '411_db_updated_at',
		'4.1.7'  => '417_db_updated_at',
		'4.1.13' => '4113_db_updated_at'
	);

	foreach ( $db_update_at_options as $version => $option ) {
		$value = ES_Common::get_ig_option( $option, false );
		if ( $value ) {
			$db_update_history_data[ $version ] = $value;

			// As we are migrating to new structure
			// Delete existing option
			ES_Common::delete_ig_option( $option );
		}
	}

	ES_Common::set_ig_option( $db_update_history_option, $db_update_history_data );

}

/**
 * add form success message according to opt-in type
 *
 * @since 4.1.15
 */
function ig_es_update_4115_add_form_submission_option() {
	$ig_es_option_type = get_option( 'ig_es_optin_type' );
	$message           = ( $ig_es_option_type == 'double_opt_in' ) ? __( 'Your subscription was successful! Kindly check your mailbox and confirm your subscription. If you don\'t see the email within a few minutes, check the spam/junk folder.', 'email-subscribers' ) : __( 'Successfully Subscribed.', 'email-subscribers' );
	update_option( 'ig_es_form_submission_success_message', $message );
}

/**
 * Update DB Update history
 *
 * @since 4.1.15
 */
function ig_es_update_4115_db_version() {
	ES_Install::update_db_version( '4.1.15' );
}

/* --------------------- ES 4.1.15(End)--------------------------- */
/**
 * Alter campaigns table
 *
 * @since 4.2.0
 */
function ig_es_update_420_alter_campaigns_table() {
	global $wpdb;

	$wpdb->hide_errors();

	$campaigns_table = IG_CAMPAIGNS_TABLE;

	$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$campaigns_table}" );

	// Add `parent_id`
	if ( ! in_array( 'parent_id', $cols ) ) {
		$query = "ALTER TABLE {$wpdb->prefix}ig_campaigns ADD COLUMN `parent_id` int(10) unsigned DEFAULT NULL AFTER `type`";
		$wpdb->query( $query );
	}

	// Add `parent_type`
	if ( ! in_array( 'parent_type', $cols ) ) {
		$query = "ALTER TABLE {$wpdb->prefix}ig_campaigns ADD COLUMN `parent_type` varchar(50) DEFAULT NULL AFTER `parent_id`";
		$wpdb->query( $query );
	}

	// Add `subject`
	if ( ! in_array( 'subject', $cols ) ) {
		$query = "ALTER TABLE {$wpdb->prefix}ig_campaigns ADD COLUMN `subject` varchar(255) DEFAULT NULL AFTER `parent_type`";
		$wpdb->query( $query );
	}

	// Add `body`
	if ( ! in_array( 'body', $cols ) ) {
		$query = "ALTER TABLE {$wpdb->prefix}ig_campaigns ADD COLUMN `body` longtext DEFAULT NULL AFTER `subject`";
		$wpdb->query( $query );
	}

	// Drop `sequence_ids`
	if ( in_array( 'sequence_ids', $cols ) ) {
		$query = "ALTER TABLE {$wpdb->prefix}ig_campaigns DROP COLUMN `sequence_ids`";
		$wpdb->query( $query );
	}
}

/**
 * Create New Tables
 *
 * @since 4.2.0
 */
function ig_es_update_420_create_tables() {

	global $wpdb;

	$wpdb->hide_errors();
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( ES_Install::get_ig_es_420_schema() );
}

/**
 * Migrate mailer and SMTP options
 *
 * @since 4.2.0
 */
function ig_es_update_420_migrate_mailer_options() {
	$es_email_type                   = get_option( 'ig_es_email_type' );
	$default_mailer                  = ( $es_email_type === 'php_html_mail' || $es_email_type === 'php_plaintext_mail' ) ? 'phpmail' : 'wpmail';
	$ig_es_mailer_settings['mailer'] = $default_mailer;
	//smtp settings default option
	$enable_smtp                     = get_option( 'ig_es_enable_smtp', 'no' );
	$ig_es_mailer_settings['mailer'] = ( $enable_smtp === 'yes' ) ? 'smtp' : $default_mailer;
	$smtp_host                       = get_option( 'ig_es_smtp_host', '' );
	$smtp_port                       = get_option( 'ig_es_smtp_port', 25 );
	$smtp_encryption                 = get_option( 'ig_es_smtp_encryption', 'tls' );
	$smtp_auth                       = get_option( 'ig_es_smtp_authentication', 'yes' );
	$smtp_username                   = get_option( 'ig_es_smtp_username', '' );
	$smtp_password                   = get_option( 'ig_es_smtp_password', '' );

	$ig_es_mailer_settings['smtp']['enable_smtp']         = $enable_smtp;
	$ig_es_mailer_settings['smtp']['smtp_host']           = $smtp_host;
	$ig_es_mailer_settings['smtp']['smtp_port']           = $smtp_port;
	$ig_es_mailer_settings['smtp']['smtp_encryption']     = $smtp_encryption;
	$ig_es_mailer_settings['smtp']['smtp_authentication'] = $smtp_auth;
	$ig_es_mailer_settings['smtp']['smtp_username']       = $smtp_username;
	$ig_es_mailer_settings['smtp']['smtp_password']       = $smtp_password;
	update_option( 'ig_es_mailer_settings', $ig_es_mailer_settings );

}


/**
 * Update DB Update history
 *
 * @since 4.2.0
 */
function ig_es_update_420_db_version() {
	ES_Install::update_db_version( '4.2.0' );
}

/* --------------------- ES 4.2.0(End)--------------------------- */

function ig_es_update_421_drop_tables() {
	global $wpdb;

	/**
	 * Note: Still we are not using ig_contact_meta table.
	 * So, it's ok to drop ig_contact_meta table now
	 * Because we want to use WordPress' metadata api and for that we need ig_contactmeta table
	 * Which we are going to create in 'ig_es_update_421_create_table' function.
	 */
	$tables_to_drop = array(
		$wpdb->prefix . 'ig_contact_meta',
		$wpdb->prefix . 'ig_actions'
	);

	foreach ( $tables_to_drop as $table ) {
		$query = "DROP TABLE IF EXISTS {$table}";
		$wpdb->query( $query );
	}
}

/**
 * Create New Table
 *
 * @since 4.2.1
 */
function ig_es_update_421_create_tables() {
	global $wpdb;

	$wpdb->hide_errors();
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( ES_Install::get_ig_es_421_schema() );
}

/**
 * Update DB Update history
 *
 * @since 4.2.1
 */
function ig_es_update_421_db_version() {
	ES_Install::update_db_version( '4.2.1' );
}

/* --------------------- ES 4.2.1(End)--------------------------- */