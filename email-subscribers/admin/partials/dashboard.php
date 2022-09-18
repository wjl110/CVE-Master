<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$current_user = wp_get_current_user();
$current_user_email =  $current_user->user_email;
?>
<!-- <div class="wrap"> -->
	<?php do_action( 'es_before_dashboard' ) ?>
	<div class="about-header">
		<div class="es-upper">
			<div class="es-info">
				<div class="es-about">
					<?php 
					$es_upgrade_to_4 = get_option('ig_es_db_version', '' );
					$ig_es_onboarding_complete = get_option('ig_es_onboarding_complete', 'no' );
					if(empty($es_upgrade_to_4)){?>
						<h2><?php echo __( "Congratulations! You've successfully upgraded to " . ES_PLUGIN_VERSION , 'email-subscribers' ); ?></h2>
						<ul><strong><?php _e('Here\'s a quick look at changes within the plugin:', 'email-subscribers'); ?></strong>
							<li class="es-notify-about-new-post-2"><?php echo sprintf( __( '1. Newsletters are now <b>Broadcasts</b>. Broadcasts and Post notifications are now merged in <a href="%s" target="_blank">Campaigns</a>', 'email-subscribers' ), admin_url( 'admin.php?page=es_campaigns' )); ?></li>
							<li class="es-notify-about-new-post-2"><?php echo sprintf( __( '2. Subscribers are now called <b>Contacts</b> and part of an <a href="%s" target="_blank">Audience</a>', 'email-subscribers' ), admin_url( 'admin.php?page=es_subscribers' )); ?></li>
							<li class="es-notify-about-new-post-2"><?php echo sprintf( __( '3. Groups are now called <a href="%s" target="_blank">Lists</a>', 'email-subscribers' ), admin_url( 'admin.php?page=es_lists' )); ?></li>
							<li class="es-notify-about-new-post-2"><?php echo sprintf( __( '4. Find <a href="%s" target="_blank">Forms</a> here', 'email-subscribers' ), admin_url( 'admin.php?page=es_forms' )); ?></li>
						</ul>
						<a href="https://www.icegram.com/email-subscribers-plugin-redesign/?utm_source=es&utm_medium=in_app&utm_campaign=es_4" target="_blank" class="button button-main"><?php _e('Explore all changes', 'email-subscribers'); ?></a>
					<?php }else if( 'yes' === $ig_es_onboarding_complete ){?>
						<h2><?php echo __( "You are all setup 👍", 'email-subscribers' ); ?></h2>
						<div class="es-notify-about-new-post-1"><b><?php echo __( 'Here are the things you can do next', 'email-subscribers' ); ?></b></div>
						<ul>
							<li class="es-notify-about-new-post-2"><?php echo sprintf( __( '1.Check <a href="%s" target="blank" >optin form</a> on your homepage', 'email-subscribers' ), home_url()); ?></li>
							<li class="es-notify-about-new-post-2"><?php echo sprintf( __( '2. <a href="%s" target="blank" ><b>Review and rearrange the form from Widgets.</b></a> You can also learn about <a href="%s" target="_blank">adding a form to another place from this help article.</a>', 'email-subscribers' ), admin_url( 'widgets.php' ), 'https://www.icegram.com/documentation/how-to-create-form-in-email-subscribers/'  ); ?></li>
							<li class="es-notify-about-new-post-2"><?php echo sprintf( __( '3. Go ahead and take a look around. Tweak settings, review <a href="%s" target="_blank">design templates</a> or go through the documentation.', 'email-subscribers' ), admin_url('edit.php?post_type=es_template') ); ?></li>
							<li class="es-notify-about-new-post-2"><?php echo __( '4. And if you haven\'t already, signup for the free course below.', 'email-subscribers' ); ?></li>
						</ul>
					<?php }else{?>
						<h2><?php echo __( 'Hello! 👋', 'email-subscribers' ); ?></h2>
						<div class="es-about-line"><?php _e('Email Subscribers is a complete newsletter plugin that lets you collect leads, send automated new blog post notification emails, send newsletters and more. It’s your tool to build an audience and engage with them ongoingly.', 'email-subscribers')?></div>
						<div class="es-about-text"><?php echo __( 'We’ve setup the basics to save you time.', 'email-subscribers' ); ?></div>
						<div class="es-notify-about-new-post-1"><b><?php echo __( 'Please read this carefully and make changes as needed', 'email-subscribers' ); ?></b></div>
						<ul>
							<li class="es-notify-about-new-post-2"><?php echo __( '1. We created two lists - <b>Main List</b> and <b>Test List</b>. Also added yourself to the Test List. That way you can test campaigns on the Test List before sending them to the Main List ;-)', 'email-subscribers' ); ?></li>
							<li class="es-notify-about-new-post-2"><?php echo sprintf( __( '2. We also created a lead collection / subscription form and added it the default widget area on your site. <a href="%s" target="blank" ><b>Review and rearrange the form from Widgets.</b></a> You can also learn about <a href="%s" target="_blank">adding a form to another place from this help article.</a>', 'email-subscribers' ), admin_url( 'widgets.php' ), 'https://www.icegram.com/documentation/how-to-create-form-in-email-subscribers/'  ); ?></li>
							<li class="es-notify-about-new-post-2"><?php echo sprintf( __( '3. BTW, we also sent a few test emails to the Test List. <a href="%s" target="_blank">We sent both those campaigns</b> </a>(newsletter and new post notification).<b> So check your email "', 'email-subscribers' ). $admin_email . __( '" and confirm you got those test  emails.</b>', 'email-subscribers' ) , admin_url( 'admin.php?page=es_campaigns' ) ); ?></li>
							<li class="es-notify-about-new-post-2"><?php echo sprintf( __( '4. Go ahead and take a look around. Tweak settings, review <a href="%s" target="_blank">design templates</a> or go through the documentation.', 'email-subscribers' ), admin_url('edit.php?post_type=es_template') ); ?></li>
							<li class="es-notify-about-new-post-2"><?php echo __( '5. And don’t forget to signup for the free course below.', 'email-subscribers' ); ?></li>
						</ul>

					<?php }?>
			    </div>
				<div class="wrap klawoo-form">
					<table class="form-table"  >
						<tr><td colspan="3" class="es-optin-headline"><?php echo __( 'Free Course - Email Marketing Mastery', 'email-subscribers' ); ?></td></tr>
						<tr>
		                    <td class="es-emm-image"><img alt="Email Marketing Mastery" src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>images/email-marketing-mastery.png" /></td>
							<td class="es-emm-text">
		                        <b><?php echo __( 'In short 5 weeks: build your list and succeed with email marketing', 'email-subscribers' ); ?></b> <br /><br />
								<?php echo __( 'Do you want to build your list, keep off spam, write emails that people open and click through? Do you want to build your brand and nurture an amazing tribe?
												Enter your name and email on the form to get it all.', 'email-subscribers' ); ?><br /><br />
		                    </td>
							<td class="es-emm-optin">
								<form name="klawoo_subscribe" action="#" method="POST" accept-charset="utf-8">
									<input class="es-ltr" type="text" name="name" id="name" placeholder="Your Name" />
									<input class="es-ltr" type="text" name="email" id="email" placeholder="Your Email" required value="<?php echo $current_user_email;?>"/> <br />
									<input type="hidden" name="list" value="hN8OkYzujUlKgDgfCTEcIA"/>
		                            <input type="checkbox" name="es-gdpr-agree" id ="es-gdpr-agree" value="1" required="required">
		                            <label for="es-gdpr-agree"><?php echo sprintf(__( 'I have read and agreed to your %s.', 'email-subscribers' ), '<a href="https://www.icegram.com/privacy-policy/" target="_blank">' . __( 'Privacy Policy', 'email-subscribers' ) . '</a>' ); ?></label>
		                            <br /><br />
									<input type="submit" name="submit" id="submit" class="button button-hero" value="<?php echo __( 'Subscribe', 'email-subscribers' ); ?>">
									<br><br>
		                        	<p id="klawoo_response"></p>
								</form>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="es-right">
				<div class="es-quick-stats" >
					<?php $sub_count = ES_DB_Contacts::count_active_subscribers_by_list_id(); 
						$total_forms = ES()->forms_db->count_forms();
						$total_lists = ES()->lists_db->count_lists();
						$total_campaigns = ES()->campaigns_db->get_total_campaigns();
					?>
				</div>
				<div class="es-quick-links-wrapper" >
					<h3 class="es-right-headline"><?php _e('Overview', 'email-subscribers'); ?></h3>
					<li class="es-quick-links"><a target="_blank" href="<?php echo admin_url( 'admin.php?page=es_subscribers&filter_by_status=subscribed' )?>" ><?php _e('Active Contacts', 'email-subscribers')?></a> <span class="es-count"><?php echo '- '.$sub_count; ?> </span></li>
					<li class="es-quick-links"><a target="_blank" href="<?php echo admin_url( 'admin.php?page=es_forms' )?>" ><?php _e('Forms', 'email-subscribers')?></a><span class="es-count"><?php echo '- '.$total_forms; ?> </span></li>
					<li class="es-quick-links"><a target="_blank" href="<?php echo admin_url( 'admin.php?page=es_campaigns' )?>" ><?php _e('Campaigns', 'email-subscribers')?></a><span class="es-count"><?php echo '- '.$total_campaigns; ?> </span></li>
					<li class="es-quick-links"><a target="_blank" href="<?php echo admin_url( 'admin.php?page=es_lists' )?>" ><?php _e('Lists', 'email-subscribers')?></a><span class="es-count"><?php echo '- '.$total_lists; ?> </span></li>
				</div>
				<div class="es-docs-wrapper" >
					<h3 class="es-right-headline"><?php _e('Help & How to\'s', 'email-subscribers'); ?></h3>
					<li class="es-doc-links"><a target="_blank" href="https://www.icegram.com/documentation/how-to-create-form-in-email-subscribers/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php _e('How to create and show subscription forms.', 'email-subscribers'); ?></a></li>
					<li class="es-doc-links"><a target="_blank" href="https://www.icegram.com/documentation/es-how-to-create-and-send-post-notification-emails-when-new-posts-are-published/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php _e('How to create a new campaign for new blog post ', 'email-subscribers'); ?></a></li>
					<li class="es-doc-links"><a target="_blank" href="https://www.icegram.com/documentation/how-to-create-new-template-for-post-notification-or-broadcast/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php _e('How to create new template for Post Notification or Broadcast', 'email-subscribers'); ?></a></li>
					<li class="es-doc-links"><a target="_blank" href="https://www.icegram.com/documentation/es-how-to-create-and-send-newsletter-emails/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php _e('How to Create and Send Broadcasts Emails', 'email-subscribers'); ?></a></li>
					<li class="es-doc-links"><a target="_blank" href="https://www.icegram.com/documentation/es-how-does-sync-work/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php _e('How to add WordPress users to your lists', 'email-subscribers'); ?></a></li>
					<li class="es-doc-links"><a target="_blank" href="https://www.icegram.com/knowledgebase_category/email-subscribers/?utm_source=es&utm_medium=in_app&utm_campaign=view_docs_help_page"><?php _e('All Documentation', 'email-subscribers'); ?></a></li>
				</div>
				<div class="es-facebook column">
	            	<p><strong><?php _e('<span>Join our</span> Email Subscribers Secret Club!','email-subscribers'); ?></strong></p>
	                <p><?php _e('Be a part of growing Email Subscribers community. Share your valuable feedback and get quick help from community. It\'s a great way to connect with real people', 'email-subscribers'); ?></p>
	                <p  style="text-align: center;"  ><a style="text-decoration: none"  target="_blank" href="https://www.facebook.com/groups/2298909487017349/"><i class="dashicons dashicons-es dashicons-facebook"></i></a></p>
	            </div>
				<div class="es-lower">
					<div class="es-version">
						<h3><?php echo __( 'Questions? Need Help?', 'email-subscribers' ); ?></h3>
						<a href="https://wordpress.org/support/plugin/email-subscribers" target="_blank"><?php echo __( 'Contact Us', 'email-subscribers' ); ?></a>
						<h5 class="es-badge"><?php echo sprintf( __( 'Version: %s', 'email-subscribers' ), $es_current_version ); ?></h5>
					</div>
				</div>
			</div>
			
		</div>

	</div>
<!-- </div> -->
