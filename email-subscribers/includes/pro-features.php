<?php

add_filter( 'ig_es_registered_settings', 'ig_es_add_upsale', 10, 2 );

// Add additional tab "Comments" in Audience > Sync
add_filter( 'ig_es_sync_users_tabs', 'ig_es_add_sync_users_tabs', 11, 1 );

add_action( 'ig_es_sync_users_tabs_comments', 'ig_es_add_comments_tab_settings' );
add_action( 'ig_es_sync_users_tabs_woocommerce', 'ig_es_add_woocommerce_tab_settings' );
add_action( 'ig_es_sync_users_tabs_cf7', 'ig_es_add_cf7_tab_settings' );
add_action( 'ig_es_sync_users_tabs_give', 'ig_es_add_give_tab_settings' );
add_action( 'ig_es_sync_users_tabs_wpforms', 'ig_es_add_wpforms_tab_settings' );
add_action( 'ig_es_sync_users_tabs_ninja_forms', 'ig_es_add_ninja_forms_tab_settings' );
add_action( 'ig_es_sync_users_tabs_edd', 'ig_es_add_edd_tab_settings' );


add_action( 'edit_form_advanced', 'add_spam_score_utm_link' );

function ig_es_add_upsale( $fields ) {
    global $ig_es_tracker;

	$es_premium  = 'email-subscribers-premium/email-subscribers-premium.php';
	$all_plugins = $ig_es_tracker::get_plugins();

	if ( ! in_array( $es_premium, $all_plugins ) ) {

		// Security settings
		$field_security['es_upsale_security'] = array(
			'id'   => 'ig_es_blocked_domains',
			'type' => 'html',
			'name' => '',
			'html' => '<div class="es-upsale-image" style=""><a target="_blank" href="https://www.icegram.com/managed-blacklists-captcha/?utm_source=in_app&utm_medium=es_security_settings&utm_campaign=es_upsale#blockspam"><img src="' . EMAIL_SUBSCRIBERS_URL . '/admin/images/es-captcha-2.png' . '"/></a></div>'
		);
		$fields['security_settings']          = array_merge( $fields['security_settings'], $field_security );

		// SMTP settings
		$field_smtp['es_upsale_smtp'] = array(
			'id'   => 'ig_es_blocked_domains',
			'type' => 'html',
			'name' => '<div class="es-smtp-label" style=""><a target="_blank" href="https://www.icegram.com/solid-email-delivery/?utm_source=in_app&utm_medium=es_smtp&utm_campaign=es_upsale#delivery"><img src="' . EMAIL_SUBSCRIBERS_URL . '/admin/images/es-smtp-label.png' . '"/></a></div>',
			'html' => '<div class="es-upsale-image es-smtp-image" style=""><a target="_blank" href="https://www.icegram.com/email-subscribers-starter/?utm_source=in_app&utm_medium=es_smtp&utm_campaign=es_upsale"><img src="' . EMAIL_SUBSCRIBERS_URL . '/admin/images/es-smtp.png' . '"/></a></div>'
		);
		$fields['email_sending']      = array_merge( $fields['email_sending'], $field_smtp );

	}

	return $fields;
}

function ig_es_add_sync_users_tabs( $tabs ) {
	global $ig_es_tracker;

	$es_premium  = 'email-subscribers-premium/email-subscribers-premium.php';
	$all_plugins = $ig_es_tracker::get_plugins();

	// Show integrations only if ES Premium is not installed.
	if ( ! in_array( $es_premium, $all_plugins ) ) {
		$tabs['comments'] = array(
			'name'             => __( 'Comments', 'email-subscribers' ),
			'indicator_option' => 'ig_es_show_sync_comment_users_indicator',
			'indicator_label'  => 'Starter'
		);

		$woocommerce_plugin = 'woocommerce/woocommerce.php';

		// Is WooCommmerce active? Show WooCommerce integration
		$active_plugins = $ig_es_tracker::get_active_plugins();
		if ( in_array( $woocommerce_plugin, $active_plugins ) ) {
			$tabs['woocommerce'] = array(
				'name'             => __( 'WooCommerce', 'email-subscribers' ),
				'indicator_option' => 'ig_es_show_sync_woocommerce_users_indicator',
				'indicator_label'  => 'Starter'
			);
		}

		// Is Contact Form 7 active? Show CF7 integration.
		$contact_form_7 = 'contact-form-7/wp-contact-form-7.php';
		if ( in_array( $contact_form_7, $active_plugins ) ) {
			$tabs['cf7'] = array(
				'name'             => __( 'Contact Form 7', 'email-subscribers' ),
				'indicator_option' => 'ig_es_show_sync_cf7_users_indicator',
				'indicator_label'  => 'Starter'
			);
		}

		$wpforms_plugin = 'wpforms-lite/wpforms.php';
		if ( in_array( $wpforms_plugin, $active_plugins ) ) {
			$tabs['wpforms'] = array(
				'name'             => __( 'WPForms', 'email-subscribers' ),
				'indicator_option' => 'ig_es_show_sync_wpforms_users_indicator',
				'indicator_label'  => 'Starter'
			);
		}

		// Show only if Give is installed & activated
		$give_plugin = 'give/give.php';
		if ( in_array( $give_plugin, $active_plugins ) ) {
			$tabs['give'] = array(
				'name'             => __( 'Give', 'email-subscribers' ),
				'indicator_option' => 'ig_es_show_sync_give_users_indicator',
				'indicator_label'  => 'Starter'
			);
		}

		// Show only if Ninja Forms is installed & activated
		$ninja_forms_plugin = 'ninja-forms/ninja-forms.php';
		if ( in_array( $ninja_forms_plugin, $active_plugins ) ) {
			$tabs['ninja_forms'] = array(
				'name'             => __( 'Ninja Forms', 'email-subscribers' ),
				'indicator_option' => 'ig_es_show_sync_ninja_forms_users_indicator',
				'indicator_label'  => 'Starter'
			);
		}
		
		// Show only if EDD is installed & activated
		$edd_plugin = 'easy-digital-downloads/easy-digital-downloads.php';
		if ( in_array( $edd_plugin, $active_plugins ) ) {
			$tabs['edd'] = array(
				'name'             => __( 'EDD', 'email-subscribers' ),
				'indicator_option' => 'ig_es_show_sync_edd_users_indicator',
				'indicator_label'  => 'Starter'
			);
		}

	}

	return $tabs;
}

function ig_es_add_comments_tab_settings( $tab_options ) {

	// If you want to hide once shown. Set it to 'no'
	// If you don't want to hide. do not use following code or set value as 'yes'
	/*
	if ( ! empty( $tab_options['indicator_option'] ) ) {
		update_option( $tab_options['indicator_option'], 'yes' ); // yes/no
	}
	*/

	$info = array(
		'type' => 'info'
	);

	ob_start();
	?>
    <div class="">
        <h2><?php _e( 'Sync Comment Users', 'email-subscribers' ) ?></h2>
        <p><?php _e( 'Quickly add to your mailing list when someone post a comment on your website.', 'email-subscribers' ) ?></p>
        <h2><?php _e( 'How to setup?', 'email-subscribers' ) ?></h2>
        <p><?php _e( 'Once you upgrade to ', 'email-subscribers' ) ?><a href="https://www.icegram.com/email-subscribers-starter/?utm_source=in_app&utm_medium=comment_sync&utm_campaign=es_upsale#sync_comment_users"><?php _e( 'Email Subscribers Starter', 'email-subscribers' ) ?></a>, <?php _e( 'you will have settings panel where you need to enable Comment user sync and select the list in which you want to add people whenever someone post a
            comment.', 'email-subscribers' ) ?></p>
        <hr>
        <p class="help"><?php _e( 'Checkout ', 'email-subscribers' ) ?><a href="https://www.icegram.com/email-subscribers-pricing/?utm_source=in_app&utm_medium=comment_sync&utm_campaign=es_upsale#sync_comment_users"><?php _e( 'Email Subscribers Starter', 'email-subscribers' ) ?></a> <?php _e( 'now', 'email-subscribers' ) ?></p>
    </div>
	<?php

	$content = ob_get_clean();

	?>
    <a target="_blank" href="https://www.icegram.com/quickly-add-people-to-your-mailing-list-whenever-someone-post-a-comment/?utm_source=in_app&utm_medium=es_comment_upsale&utm_campaign=es_upsale#sync_comment_users">
        <img src=" <?php echo EMAIL_SUBSCRIBERS_URL . '/admin/images/es-comments.png' ?> "/>
    </a>
	<?php
	ES_Common::prepare_information_box( $info, $content );
}

function ig_es_add_woocommerce_tab_settings( $tab_options ) {

	$info = array(
		'type' => 'info',
	);

	ob_start();
	?>
    <div class="">
        <h2><?php _e( 'Sync WooCommerce Customers', 'email-subscribers' ) ?></h2>
        <p><?php _e( 'Are you using WooCommerce for your online business? You can use this integration to add to a specific list whenever someone make a purchase from you', 'email-subscribers' ) ?></p>
        <h2><?php _e( 'How to setup?', 'email-subscribers' ) ?></h2>
        <p><?php _e( 'Once you upgrade to ', 'email-subscribers' ) ?><a href="https://www.icegram.com/email-subscribers-starter/?utm_source=in_app&utm_medium=woocommerce_sync&utm_campaign=es_upsale#sync_woocommerce_customers"><?php _e( 'Email Subscribers Starter', 'email-subscribers' ) ?></a>, <?php _e( 'you will have settings panel where you need to enable WooCommerce sync and select the list in which you want to add people whenever they
            purchase something
            from you.', 'email-subscribers' ) ?></p>
        <hr>
        <p class="help"><?php _e( 'Checkout ', 'email-subscribers' ) ?><a href="https://www.icegram.com/email-subscribers-pricing/?utm_source=in_app&utm_medium=woocommerce_sync&utm_campaign=es_upsale#sync_woocommerce_customers">Email Subscribers Starter</a> Now</p>
    </div>
	<?php $content = ob_get_clean(); ?>

    <a target="_blank" href="https://www.icegram.com/quickly-add-customers-to-your-mailing-list/?utm_source=in_app&utm_medium=woocommerce_sync&utm_campaign=es_upsale#sync_woocommerce_customers">
        <img src=" <?php echo EMAIL_SUBSCRIBERS_URL . '/admin/images/woocommerce-sync.png' ?> "/>
    </a>

	<?php

	ES_Common::prepare_information_box( $info, $content );

	?>

	<?php
}

function ig_es_add_cf7_tab_settings( $tab_options ) {

	$info = array(
		'type' => 'info',
	);

	ob_start();
	?>
    <div class="">
        <h2><?php _e( 'Sync Contact Form 7 users', 'email-subscribers' ) ?></h2>
        <p><?php _e( 'Are you using Contact Form 7 for your list building? You can use this integration to add to a specific list whenever new subscribers added from Contact Form 7', 'email-subscribers' ) ?></p>
        <h2><?php _e( 'How to setup?', 'email-subscribers' ) ?></h2>
        <p><?php _e( 'Once you upgrade to ', 'email-subscribers' ) ?><a href="https://www.icegram.com/email-subscribers-starter/?utm_source=in_app&utm_medium=cf7_sync&utm_campaign=es_upsale#sync_cf7_subscribers"><?php _e( 'Email Subscribers Starter',
					'email-subscribers' ) ?></a>, <?php _e( 'you will have settings panel where you need to enable Contact form 7 sync and select the list in which you want to add people whenever they fill any of the Contact Form.', 'email-subscribers' ) ?></p>
        <hr>
        <p class="help"><?php _e( 'Checkout ', 'email-subscribers' ) ?><a href="https://www.icegram.com/email-subscribers-pricing/?utm_source=in_app&utm_medium=cf7_sync&utm_campaign=es_upsale#sync_cf7_subscribers">Email Subscribers Starter</a> Now</p>
    </div>
	<?php $content = ob_get_clean(); ?>

    <a target="_blank" href="https://www.icegram.com/add-people-to-your-mailing-list-whenever-they-submit-any-of-the-contact-form-7-form/?utm_source=in_app&utm_medium=cf7_sync&utm_campaign=es_upsale#sync_cf7_subscribers">
        <img src=" <?php echo EMAIL_SUBSCRIBERS_URL . '/admin/images/cf7-sync.png' ?> "/>
    </a>

	<?php

	ES_Common::prepare_information_box( $info, $content );

	?>

	<?php
}

function ig_es_add_give_tab_settings( $tab_options ) {

	$info = array(
		'type' => 'info',
	);

	ob_start();
	?>
    <div class="">
        <h2><?php _e( 'Sync Donors', 'email-subscribers' ) ?></h2>
        <p><?php _e( 'We found that you are using Give WordPress plugin to collect donations. Now, with this integration, you can add your donors to any of your subscriber list and send them Newsletters in future.', 'email-subscribers' ) ?></p>
        <h2><?php _e( 'How to setup?', 'email-subscribers' ) ?></h2>
        <p><?php _e( 'Once you upgrade to ', 'email-subscribers' ) ?><a target="_blank" href="https://www.icegram.com/email-subscribers-starter/?utm_source=in_app&utm_medium=give_sync&utm_campaign=es_upsale#sync_give_donors"><?php _e( 'Email Subscribers Starter',
					'email-subscribers' ) ?></a>, <?php _e( 'you will have settings panel where you need to enable Give integration and select the list in which you want to add people whenever they make donation.', 'email-subscribers' ) ?></p>
        <hr>
        <p class="help"><?php _e( 'Checkout ', 'email-subscribers' ) ?><a target="_blank" href="https://www.icegram.com/email-subscribers-pricing/?utm_source=in_app&utm_medium=give_sync&utm_campaign=es_upsale#sync_give_donors">Email Subscribers Starter</a> Now</p>
    </div>
	<?php $content = ob_get_clean(); ?>

    <a target="_blank" href="https://www.icegram.com/email-subscribers-starter/?utm_source=in_app&utm_medium=give_sync&utm_campaign=es_upsale#sync_give_donors">
        <img src=" <?php echo EMAIL_SUBSCRIBERS_URL . '/admin/images/give-sync.png' ?> "/>
    </a>

	<?php

	ES_Common::prepare_information_box( $info, $content );

	?>

	<?php
}

function ig_es_add_wpforms_tab_settings( $tab_options ) {

	$info = array(
		'type' => 'info',
	);

	ob_start();
	?>
    <div class="">
        <h2><?php _e( 'Sync Donors', 'email-subscribers' ) ?></h2>
        <p><?php _e( 'Are you using Give WordPress plugin to collect donations? Want to send Thank You email to them? You can use this integration to be in touch with them.', 'email-subscribers' ) ?></p>
        <h2><?php _e( 'How to setup?', 'email-subscribers' ) ?></h2>
        <p><?php _e( 'Once you upgrade to ', 'email-subscribers' ) ?><a target="_blank" href="https://www.icegram.com/email-subscribers-starter/?utm_source=in_app&utm_medium=wpforms_sync&utm_campaign=es_upsale#sync_wpforms_contacts"><?php _e( 'Email Subscribers Starter',
					'email-subscribers' ) ?></a>, <?php _e( 'you will have settings panel where you need to enable Give sync and select the list in which you want to add people whenever they make donation.', 'email-subscribers' ) ?></p>
        <hr>
        <p class="help"><?php _e( 'Checkout ', 'email-subscribers' ) ?><a target="_blank" href="https://www.icegram.com/email-subscribers-pricing/?utm_source=in_app&utm_medium=wpforms_sync&utm_campaign=es_upsale#sync_wpforms_contacts">Email Subscribers Starter</a> Now</p>
    </div>
	<?php $content = ob_get_clean(); ?>

    <a target="_blank" href="https://www.icegram.com/email-subscribers-starter/?utm_source=in_app&utm_medium=wpforms_sync&utm_campaign=es_upsale#sync_wpforms_contacts">
        <img src=" <?php echo EMAIL_SUBSCRIBERS_URL . '/admin/images/wpforms-sync.png' ?> "/>
    </a>

	<?php

	ES_Common::prepare_information_box( $info, $content );

	?>

	<?php
}

function ig_es_add_ninja_forms_tab_settings( $tab_options ) {

	$info = array(
		'type' => 'info',
	);

	ob_start();
	?>
    <div class="">
        <h2><?php _e( 'Sync Contacts', 'email-subscribers' ) ?></h2>
        <p><?php _e( 'We found that you are using Ninja Forms. Want to add your contact to a mailing list? You can use this integration to add your contact to add into mailing list', 'email-subscribers' ) ?></p>
        <h2><?php _e( 'How to setup?', 'email-subscribers' ) ?></h2>
        <p><?php _e( 'Once you upgrade to ', 'email-subscribers' ) ?><a target="_blank" href="https://www.icegram.com/email-subscribers-starter/?utm_source=in_app&utm_medium=ninja_forms_sync&utm_campaign=es_upsale#sync_ninja_forms_contacts"><?php _e( 'Email Subscribers Starter',
					'email-subscribers' ) ?></a>, <?php _e( 'you will have settings panel where you need to enable Give sync and select the list in which you want to add people whenever they make donation.', 'email-subscribers' ) ?></p>
        <hr>
        <p class="help"><?php _e( 'Checkout ', 'email-subscribers' ) ?><a target="_blank" href="https://www.icegram.com/email-subscribers-pricing/?utm_source=in_app&utm_medium=ninja_forms_sync&utm_campaign=es_upsale#sync_ninja_forms_contacts">Email Subscribers Starter</a> Now</p>
    </div>
	<?php $content = ob_get_clean(); ?>

    <a target="_blank" href="https://www.icegram.com/email-subscribers-starter/?utm_source=in_app&utm_medium=ninja_forms_sync&utm_campaign=es_upsale#sync_ninja_forms_contacts">
        <img src=" <?php echo EMAIL_SUBSCRIBERS_URL . '/admin/images/ninja-forms-sync.png' ?> "/>
    </a>

	<?php

	ES_Common::prepare_information_box( $info, $content );

	?>

	<?php
}

function ig_es_add_edd_tab_settings( $tab_options ) {

	$info = array(
		'type' => 'info',
	);

	ob_start();
	?>
    <div class="">
        <h2><?php _e( 'Sync Customers', 'email-subscribers' ) ?></h2>
        <p><?php _e( 'We found that you are using EDD to sell digital goods online. You can use this integration to send Newsletters/ Post Notifications to your customers.', 'email-subscribers' ) ?></p>
        <h2><?php _e( 'How to setup?', 'email-subscribers' ) ?></h2>
        <p><?php _e( 'Once you upgrade to ', 'email-subscribers' ) ?><a target="_blank" href="https://www.icegram.com/email-subscribers-starter/?utm_source=in_app&utm_medium=edd_sync&utm_campaign=es_upsale#sync_edd_customers"><?php _e( 'Email Subscribers Starter',
					'email-subscribers' ) ?></a>, <?php _e( 'you will have settings panel where you need to enable EDD sync and select the list in which you want to add people whenever they purchase something from you.', 'email-subscribers' ) ?></p>
        <hr>
        <p class="help"><?php _e( 'Checkout ', 'email-subscribers' ) ?><a target="_blank" href="https://www.icegram.com/email-subscribers-pricing/?utm_source=in_app&utm_medium=edd_sync&utm_campaign=es_upsale#sync_edd_customers">Email Subscribers Starter</a> Now</p>
    </div>
	<?php $content = ob_get_clean(); ?>

    <a target="_blank" href="https://www.icegram.com/email-subscribers/?utm_source=in_app&utm_medium=edd_sync&utm_campaign=es_upsale#sync_edd_customers">
        <img src=" <?php echo EMAIL_SUBSCRIBERS_URL . '/admin/images/edd-sync.png' ?> "/>
    </a>

	<?php

	ES_Common::prepare_information_box( $info, $content );

	?>

	<?php
}


function add_spam_score_utm_link() {
	global $post, $pagenow, $ig_es_tracker;
	if ( $post->post_type !== 'es_template' ) {
		return;
	}
	$es_premium  = 'email-subscribers-premium/email-subscribers-premium.php';
	$all_plugins = $ig_es_tracker::get_plugins();

	if ( ! in_array( $es_premium, $all_plugins ) ) {
		?>
        <script>
			jQuery('#submitdiv').after('<div class="es_upsale"><a style="text-decoration:none;" target="_blank" href="https://www.icegram.com/documentation/how-ready-made-template-in-in-email-subscribers-look/?utm_source=in_app&utm_medium=es_template&utm_campaign=es_upsale"><img title="Get readymade templates" style="width:100%;border:0.3em #d46307 solid" src="<?php echo EMAIL_SUBSCRIBERS_URL?>/admin/images/starter-tmpl.png"/><p style="background: #d46307; color: #FFF; padding: 4px; width: 100%; text-align:center">Get readymade beautiful email templates</p></a></div>');
        </script>
		<?php
	}
}



