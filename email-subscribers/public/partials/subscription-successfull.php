<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

	$es_page_request = ig_es_get_request_data('es');

	$blogname = get_option( 'blogname' );
	$noerror  = true;
	$home_url = home_url( '/' );
	?>
    <html>
    <head>
        <title><?php echo $blogname; ?></title>
        <meta http-equiv="refresh" content="10; url=<?php echo $home_url; ?>" charset="<?php echo esc_attr( get_option( 'blog_charset' ) ); ?>"/>
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
        <h1> <?php echo $message; ?> </h1>
    </div>
    </body>
    </html>
	<?php

die();