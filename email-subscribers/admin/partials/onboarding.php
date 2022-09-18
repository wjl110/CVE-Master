<div id="slider-wrapper">
    <div id="slider">
        <div class="sp es-send-email-screen" >
            <h1> 👍 <?php _e('80% done!'); ?></h1>
            <strong><?php _e('We automatically:'); ?></strong>
            <ol>
                <li> <?php echo sprintf( __('Created two Lists - <a href="%s" target="_blank">Main</a> and <a href="%s" target="_blank">Test</a>', 'email-subscribers'), admin_url('admin.php?page=es_lists'), admin_url('admin.php?page=es_lists'));?></li>
                <li><?php _e('Added yourself', 'email-subscribers');?> <strong>(<?php echo get_option('admin_email'); ?>)</strong> <?php _e('to both lists', 'email-subscribers');?> </li>
                <li> <?php echo sprintf( __('Created a <a href="%s" target="_blank"> subscription / lead collection optin form</a>', 'email-subscribers'), admin_url('admin.php?page=es_forms') );?></li>
                <li> <?php echo sprintf( __('Added optin form to <a href="%s" target="_blank">default widget area</a> to show it on your site', 'email-subscribers'), admin_url('widgets.php'));?></li>
            </ol>
            <div class="es-form-wrapper">
                <div class="es-form-next"style="padding: 0.5em 0.8em; border-radius: 3px;">
                    <?php _e(' We will create "<strong>Newsletter Broadcast</strong>" and "<strong>New Post Notification</strong>" campaigns.  Next step is to test everything by <strong>sending test campaigns</strong>.<br />We\'ve already added you, but recommend adding another email to test.', 'email-subscribers'); ?>
                </div>
                <form id="es-send-email-form">
                    <label><strong><?php _e('Add an email to send a test to:', 'email-subscribers'); ?></strong></label><br/>
                    <input  type="email" placeholder="abc@gmail.com" name="es_test_email[]" class="es_email" required style="padding: 0.6em 0.5em; border: 1px solid #dcdcdc; "/>
                    <a id="button-send" class="button-send"><?php _e('Create and Send test camapigns', 'email-subscribers');?></a>
                    <img class="es-loader" src="<?php echo EMAIL_SUBSCRIBERS_URL ?>/public/images/spinner.gif"  style="display:none;"/>
                </form>
            </div>
        </div>
        <div class="sp es-success" >
            <h1><?php _e('Test emails sent, check your inbox'); ?></h1>
            <div class="es-sent-success">
                <div class="es-gray"><?php echo sprintf( __('We sent two Campaigns to %s and email you have added.', 'email-subscribers'), get_option('admin_email'));?></div>
                <div class="es-gray"><?php echo __('They may take a few minutes to arrive. But do confirm you received them.', 'email-subscribers');?></div>
            </div>
            <div class="emoji" style="text-align: center; font-size: 10em; opacity: 0.45"> 📨 </div>
            <div class="es-actions">
                <div class="button button-hero es-receive-success-btn" style="width: 100%; text-align: center;padding: 0"><?php _e('Yes, I received the test emails', 'email-subscribers'); ?></div>
                <div style="margin-top: 0.7em;"><a href="#" style="text-decoration: none;color: #737373" class="es-secondary es-receive-error-btn""><?php _e('No, I haven\'t received them yet', 'email-subscribers'); ?></a></div>
            </div>
        </div>
        <div class="sp es-receive-success">
            <h1> 👍 <?php _e('We\'re done!'); ?></h1>
            <div>
                <div class="" style="color: #737373; line-height: 1.75;"><?php _e('Everything is setup now. It\'s a perfect time to get better at email marketing now. Sign up below to get our highly acclaimed course for free.', 'email-subscribers');?> </div>
                <form name="klawoo_subscribe" action="#" method="POST" accept-charset="utf-8" class="es-onboarding" style="margin-right: 0; margin-top: 1em; /* text-align: center; */ ">
                    <input class="es-ltr" type="text" name="name" id="name" placeholder="Your Name"/> <br />
                    <input class="es-ltr" type="text" name="email" id="email" placeholder="Your Email" required/> <br />
                    <input type="hidden" name="list" value="hN8OkYzujUlKgDgfCTEcIA"/>
                    <input type="hidden" name="form-source" value="es_email_send_success"/>
                    <input type="checkbox" name="es-gdpr-agree" id ="es-gdpr-agree" value="1" required="required">
                    <label for="es-gdpr-agree" style="font-size: 0.9em; color: #777777; "><?php echo sprintf(__( 'I have read and agreed to your %s.', 'email-subscribers' ), '<a href="https://www.icegram.com/privacy-policy/" target="_blank">' . __( 'Privacy Policy', 'email-subscribers' ) . '</a>' ); ?></label>
                    <br />
                    <input type="submit" name="submit" id="submit" class="button button-hero" style="padding: 0; width: 320px; "value="<?php echo __( 'Signup and send me the course for free', 'email-subscribers' ); ?>">
                    <div style="text-align: center; width: 56%; margin-top: 0.5em;"><a class="es-skip" href="<?php echo admin_url('admin.php?page=es_dashboard&es_skip=1&option_name=email_send_success'); ?>"><?php _e('Skip and goto Dashboard', 'email-subscribers') ?></a></div>
                    <br>
                    <p id="klawoo_response"></p>
                </form>
            </div>
        </div>
        <div class="sp es-receive-error">
            <h1><?php _e('Check these few things below'); ?></h1>
            <ul>
                <li><?php _e('1. Check your spam or junk folder', 'email-subscribers')?></li>
                <li><?php echo sprintf(__('2. <a href="%s" target="_blank">Send another test email</a> with different email address ', 'email-subscribers'), admin_url('admin.php?page=es_settings#tabs-email_sending')); ?> </li>
                <li><?php echo '3. Is <strong>'. get_option('admin_email') . '</strong>'; ?> email is free/disposable?</li>
                <li><a href="https://www.icegram.com/documentation/reasons-why-you-havent-received-the-test-email/?utm_source=es&utm_medium=es_onboarding&utm_campaign=view_docs_help_page" target="_blank"  style="color: #387bff; font-weight: 500; "> <?php _e('Explore more', 'email-subscribers')?></a></li>
            </ul>
            <div class="">
                <div class="es-gray"><?php _e('Also, it\'s a perfect time to get better at email marketing now. Sign up below to get our highly acclaimed course for free.', 'email-subscribers'); ?></div>
                <form name="klawoo_subscribe" action="#" method="POST" accept-charset="utf-8" class="es-onboarding">
                    <input class="es-ltr" type="text" name="name" id="name" placeholder="Your Name"/> <br />
                    <input class="es-ltr" type="text" name="email" id="email" placeholder="Your Email" required/> <br />
                    <input type="hidden" name="list" value="hN8OkYzujUlKgDgfCTEcIA"/>
                    <input type="hidden" name="form-source" value="es_email_receive_error"/>
                    <input type="checkbox" name="es-gdpr-agree" id ="es-gdpr-agree" value="1" required="required">
                    <label for="es-gdpr-agree" style="font-size: 0.9em; color: #777777; "><?php echo sprintf(__( 'I have read and agreed to your %s.', 'email-subscribers' ), '<a href="https://www.icegram.com/privacy-policy/" target="_blank">' . __( 'Privacy Policy', 'email-subscribers' ) . '</a>' ); ?></label>
                    <br />
                    <input type="submit" name="submit" id="submit" class="button button-hero" value="<?php echo __( 'Signup and send me the course for free', 'email-subscribers' ); ?>">
                    <div style="text-align: center; width: 56%; margin-top: 0.5em;"><a class="es-skip" href="<?php echo admin_url('admin.php?page=es_dashboard&es_skip=1&option_name=email_receive_error'); ?>" ><?php _e('Skip and goto Dashboard', 'email-subscribers') ?></a></div>
                    <br>
                    <p id="klawoo_response"></p>
                </form>
            </div>
        </div>
        <div class="sp es-error" >
            <h1>⚠️ <?php _e('Problem sending emails - fix this first.'); ?></h1>
                <div style="font-weight: 500; font-size: 1.1em;"><?php _e('We faced some problems sending test Campaigns.'); ?></div>
                <div class="es-email-sending-error"></div>
                <div><a href="https://www.icegram.com/documentation/common-email-sending-problems/?utm_source=es&utm_medium=es_onboarding&utm_campaign=view_docs_help_page" target="_blank"  style="color: #387bff; font-weight: 500; "> <?php _e('Explore more about problems', 'email-subscribers')?></a></div>
                <div class="es-gray"><?php _e('Please solve these problems, without that email sending won\'t work.'); ?></div>
                <div style="margin-top: 1em;">
                    <div class="es-gray"><?php _e('Also, it\'s a perfect time to get better at email marketing now. Sign up below to get our highly acclaimed course for free.', 'email-subscribers'); ?></div>
                    <form name="klawoo_subscribe" action="#" method="POST" accept-charset="utf-8" class="es-onboarding">
                        <input class="es-ltr" type="text" name="name" id="name" placeholder="Your Name"/> <br />
                        <input class="es-ltr" type="text" name="email" id="email" placeholder="Your Email" required/> <br />
                        <input type="hidden" name="list" value="hN8OkYzujUlKgDgfCTEcIA"/>
                        <input type="hidden" name="form-source" value="es_email_send_error"/>
                        <input type="checkbox" name="es-gdpr-agree" id ="es-gdpr-agree" value="1" required="required">
                        <label for="es-gdpr-agree" style="font-size: 0.9em; color: #777777; "><?php echo sprintf(__( 'I have read and agreed to your %s.', 'email-subscribers' ), '<a href="https://www.icegram.com/privacy-policy/" target="_blank">' . __( 'Privacy Policy', 'email-subscribers' ) . '</a>' ); ?></label>
                        <br />
                        <input type="submit" name="submit" id="submit" class="button button-hero" value="<?php echo __( 'Signup and send me the course for free', 'email-subscribers' ); ?>">
                        <div style="text-align: center; width: 56%; margin-top: 0.5em;"><a class="es-skip" href="<?php echo admin_url('admin.php?page=es_dashboard&es_skip=1&option_name=email_send_error'); ?>"><?php _e('Skip and goto Dashboard', 'email-subscribers') ?></a></div>
                        <br>
                        <p id="klawoo_response"></p>
                    </form>
                </div>
        </div>
        
    </div>
</div>
