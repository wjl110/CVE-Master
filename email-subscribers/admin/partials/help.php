<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wrap">
    <h1><?php echo __( 'Help & Info', 'email-subscribers' ); ?></h1>

    <div class="help-info-content">
        <div class="left-blog">
            <div class="feature-overview">
                <h3><?php echo __( 'Feature Overview', 'email-subscribers' ); ?></h3>
                <ul>
                    <li><?php echo __( 'Collect customer emails by adding a subscription box (Widget/Shortcode/PHP Code).', 'email-subscribers' ); ?></li>
                    <li><?php echo __( 'Configure double Opt-In and Single Opt-In facility for subscribers.', 'email-subscribers' ); ?></li>
                    <li><?php echo __( 'Send automatic welcome email to subscribers.', 'email-subscribers' ); ?></li>
                    <li><?php echo __( 'Send new post notification emails to subscribers when new posts are published on your website.', 'email-subscribers' ); ?></li>
                    <li><?php echo __( 'Send email notification to admin when a new user signs up.', 'email-subscribers' ); ?></li>
                    <li><?php echo __( 'Automatically add Unsubscribe link in the email.', 'email-subscribers' ); ?></li>
                    <li><?php echo __( 'Easily migrate subscribers from another app using Import & Export.', 'email-subscribers' ); ?></li>
                    <li><?php echo __( 'Use HTML editor to create broadcast (Newsletters) and post notifications.', 'email-subscribers' ); ?></li>
                    <li><?php echo __( 'Send broadcast to different lists.', 'email-subscribers' ); ?></li>
                    <li><?php echo __( 'Get detailed sent email reports.', 'email-subscribers' ); ?></li>
                    <li><?php echo __( 'Supports localization and internationalization.', 'email-subscribers' ); ?></li>
                </ul>
            </div>
        </div>
        <div class="right-blog">

			<?php if ( $enable_manual_update ) {

				$info = array(
					'type'      => 'info',
					'center'    => false,
					'show_icon' => false
				);

				ob_start();
				?>

                <div class="database-migration">
                    <h3><?php echo __( 'Database Migration', 'email-subscribers' ); ?></h3>

                    <p><?php echo __( 'If you found duplicate campaigns, lists, forms, reports after upgrading from Email Subscribers 3.5.x to 4.x and want to run the database migration again to fix this, please click the below <b>Run the updater</b> button.', 'email-subscribers' ); ?></p>

                    <p><?php echo __( 'Once you click on <b>Run the updater</b> button, it will run the migration process from 3.5.x once again. So, if you have created new campaigns, forms or lists after migration to 4.x earlier, you will lose those data. So, make sure you have a backup with you.', 'email-subscribers' ); ?></p>

                    <p class="submit">
                        <a href="<?php echo esc_url( $update_url ); ?>" class="es-update-now button-primary"><?php echo __( 'Run the updater', 'email-subscribers' ); ?></a>
                    </p>

                </div>

				<?php
				$content = ob_get_clean();

				ES_Common::prepare_information_box( $info, $content );

			} ?>

            <div class="subscribe-form">
                <h4><?php echo __( 'Additional form settings', 'email-subscribers' ); ?></h4>
                <ul>
                    <li><a target="_blank" href="https://www.icegram.com/documentation/es-how-to-redirect-subscribers-to-a-new-page-url-after-successful-sign-up/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php echo __( 'How to Redirect Subscribers to a new page/url after successful sign up?', 'email-subscribers' ); ?></a></li>
                    <li><a target="_blank" href="https://www.icegram.com/documentation/es-how-to-add-captcha-in-subscribe-form-of-email-subscribers/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php echo __( 'How to add captcha in Subscribe form of Email Subscribers?', 'email-subscribers' ); ?></a></li>
                </ul>
            </div>
            <div class="general-plugin-configuration">
                <h3><?php echo __( 'General Plugin Configuration', 'email-subscribers' ); ?></h3>
                <ul>
                    <li><?php echo __( 'Modify ', 'email-subscribers' ); ?><a target="_blank" href="https://www.icegram.com/documentation/es-general-plugin-settings/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php echo __( 'default text, email contents',
								'email-subscribers' ); ?></a><?php echo __( ' (like Confirmation, Welcome, Admin emails), Cron Settings and Assign User Roles', 'email-subscribers' ); ?></li>
                    <li><a target="_blank" href="https://www.icegram.com/documentation/es-how-does-sync-work/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php echo __( 'How does Sync work?', 'email-subscribers' ); ?></a></li>
                    <li><a target="_blank" href="https://www.icegram.com/documentation/es-how-to-import-or-export-email-addresses/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php echo __( 'How to Import or Export Email Addresses?', 'email-subscribers' ); ?></a></li>
                    <li><a target="_blank" href="https://www.icegram.com/documentation/es-how-to-add-update-existing-subscribers-group/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php echo __( 'How to Add/Update Existing Subscribers List & Status?', 'email-subscribers' ); ?></a></li>
                    <li><a target="_blank" href="https://www.icegram.com/documentation/es-how-to-change-update-translate-any-texts-from-email-subscribers/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php echo __( 'How to change/update/translate any texts from the plugin?', 'email-subscribers' ); ?></a></li>
                    <li><a target="_blank" href="https://www.icegram.com/documentation/es-how-to-add-unsubscribe-link-in-emails/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php echo __( 'How to add Unsubscribe link in emails?', 'email-subscribers' ); ?></a></li>
                    <li><a target="_blank" href="https://www.icegram.com/documentation/es-how-to-check-sent-emails/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php echo __( 'How to check sent emails?', 'email-subscribers' ); ?></a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="feature-section">
        <div class="feature-blog">
            <h3><?php echo __( 'Newsletters', 'email-subscribers' ); ?></h3>
            <ul>
                <li><a target="_blank" href="https://www.icegram.com/documentation/es-how-to-create-and-send-newsletter-emails/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php echo __( 'Create and Send Newsletter Emails', 'email-subscribers' ); ?></a></li>
                <li><a target="_blank" href="https://www.icegram.com/documentation/es-what-are-the-available-keywords-in-the-newsletters/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php echo __( 'Keywords in the Newsletters', 'email-subscribers' ); ?></a></li>
            </ul>
        </div>
        <div class="feature-blog">
            <h3><?php echo __( 'Cron Job Setup', 'email-subscribers' ); ?></h3>
            <ul>
                <li><a target="_blank" href="https://www.icegram.com/documentation/es-how-to-schedule-cron-emails/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php echo __( 'What is Cron and how to Schedule Cron Emails?', 'email-subscribers' ); ?></a></li>
                <li><a target="_blank" href="https://www.icegram.com/documentation/es-how-to-schedule-cron-emails-in-cpanel/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php echo __( 'Schedule Cron Emails in cPanel', 'email-subscribers' ); ?></a></li>
                <li><a target="_blank" href="https://www.icegram.com/documentation/es-how-to-schedule-cron-emails-in-parallels-plesk/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php echo __( 'Schedule Cron Emails in Parallels Plesk', 'email-subscribers' ); ?></a></li>
                <li><a target="_blank" href="https://www.icegram.com/documentation/es-what-to-do-if-hosting-doesnt-support-cron-jobs/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php echo __( 'Hosting doesn’t support Cron Jobs?', 'email-subscribers' ); ?></a></li>
            </ul>
        </div>
        <div class="feature-blog">
            <h3><?php echo __( '[GDPR] Email Subscribers', 'email-subscribers' ); ?></h3>
            <ul>
                <li><a target="_blank" href="https://www.icegram.com/documentation/es-gdpr-how-to-enable-consent-checkbox-in-the-subscription-form/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php echo __( 'How to enable consent checkbox in the subscribe form?', 'email-subscribers' ); ?></a></li>
                <li><a target="_blank" href="https://www.icegram.com/documentation/es-gdpr-what-data-email-subscribers-stores-on-your-end/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php echo __( 'What data Email Subscribers stores on your end?', 'email-subscribers' ); ?></a></li>
            </ul>
        </div>
        <div class="feature-blog">
            <h3><?php echo __( 'Post Notifications', 'email-subscribers' ); ?></h3>
            <ul>
                <li><a target="_blank" href="https://www.icegram.com/documentation/es-how-to-create-and-send-post-notification-emails-when-new-posts-are-published/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php echo __( 'Create and Send Post Notification Emails when new posts are published', 'email-subscribers' ); ?></a></li>
                <li><a target="_blank" href="https://www.icegram.com/documentation/es-what-are-the-available-keywords-in-the-post-notifications/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php echo __( 'Keywords in the Post Notifications', 'email-subscribers' ); ?></a></li>
                <li><a target="_blank" href="https://www.icegram.com/documentation/es-how-to-send-a-sample-new-post-notification-email-to-testgroup-myself/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php echo __( 'Send a test post notification email to myself/testgroup', 'email-subscribers' ); ?></a></li>
            </ul>
        </div>
        <div class="feature-blog">
            <h3><?php echo __( 'Troubleshooting Steps', 'email-subscribers' ); ?></h3>
            <ul>
                <li><a target="_blank" href="https://www.icegram.com/documentation/es-subscribers-are-not-receiving-emails/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php echo __( 'Subscribers are not receiving Emails?', 'email-subscribers' ); ?></a></li>
                <li><a target="_blank" href="https://www.icegram.com/documentation/es-css-help/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php echo __( 'CSS Help', 'email-subscribers' ); ?></a></li>
                <li><a target="_blank" href="https://www.icegram.com/documentation/es-faq/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php echo __( "FAQ's", 'email-subscribers' ); ?></a></li>
            </ul>
        </div>
    </div>
    <div class="feature-section feature-section-last">
        <div class="feature-header"><h2><?php echo __( "Want to do more? Here's how..", 'email-subscribers' ); ?></h2></div>
        <div class="feature-blog-wrapper">
            <div class="feature-blog">
                <h3><?php echo __( 'Show your subscribe form inside attractive popups', 'email-subscribers' ); ?></h3>
                <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>/images/es-ig-integration.png" alt="feature-img">
                <p><?php echo __( "Don't limit your subscriber form to a widget. Embed it within popups, hello bars, slide-ins, sidebars, full screen popups etc.", 'email-subscribers' ); ?></p>
                <p><?php echo __( 'Using Email Subscribers you can achieve this easily with our <b>free</b> plugin <a target="_blank" class="es-cta" href="https://wordpress.org/plugins/icegram/">Icegram</a>', 'email-subscribers' ); ?></p>
                <p><?php echo __( "Icegram's beautiful designs instantly capture user attention and help increase sign-ups to your WordPress website.", 'email-subscribers' ); ?></p>
                <p><?php echo __( 'How to <a href="https://www.icegram.com/documentation/es-how-to-show-subscribe-form-inside-a-popup/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page" target="_blank">show subscribe form inside a popup</a>', 'email-subscribers' ); ?></p>
            </div>
            <div class="feature-blog">
                <h3><?php echo __( 'Get beautiful and elegant form styles', 'email-subscribers' ); ?></h3>
                <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>/images/es-rm-integration.png" alt="feature-img">
                <p><?php echo __( 'Email subscribers easily integrates with another <b>free</b> plugin <a class="es-cta" target="_blank" href="https://wordpress.org/plugins/icegram-rainmaker/">Rainmaker</a>', 'email-subscribers' ); ?></p>
                <p><?php echo __( 'Rainmaker extends the core features of Email Subscribers and provides elegant form styles.', 'email-subscribers' ); ?></p>
                <p><?php echo __( 'These styles are well designed and beautify your subscription form making it more appealing.', 'email-subscribers' ); ?></p>
                <p><?php echo __( 'How to <a href="https://www.icegram.com/documentation/es-how-to-use-rainmakers-form-in-email-subscribers/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page" target="_blank">add Rainmaker’s form in Email Subscribers</a>', 'email-subscribers' ); ?></p>
            </div>
        </div>
    </div>
</div>