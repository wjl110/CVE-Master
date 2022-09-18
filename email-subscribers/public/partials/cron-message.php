<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$es_page_request = ig_es_get_request_data( 'es' );

$blogname = get_option( 'blogname' );
$noerror  = true;
$home_url = home_url( '/' );
?>
    <html>
    <head>
        <title><?php echo $blogname; ?></title>
		<?php do_action( 'es_message_head' ); ?>

        <style type="text/css">
            .es_center_info {
                margin: auto;
                width: 50%;
                padding: 10px;
                text-align: center;
            }
        </style>
    </head>
    <body>
    <div class="es_center_info es_successfully_subscribed">
        <p> <?php echo $message; ?> </p>
        <table class="table">
            <tr>
                <td><?php _e( 'Total Emails Sent', 'email-subscribers' ); ?></td>
                <td><?php echo $total_emails_sent; ?></td>
            </tr>
            <tr>
                <td><?php _e( 'Total Emails In Queue', 'email-subscribers' ); ?></td>
                <td><?php
					echo $total_emails_to_be_sent;
					if ( $total_emails_to_be_sent > 0 ) {
						echo ' ' . $send_now_text; ?>
					<?php } ?>
                </td>

            </tr>

        </table>

    </div>
    </body>
    </html>
<?php

die();