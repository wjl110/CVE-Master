<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function header_part() {
	$screen = get_current_screen();
	if ( $screen->parent_base == 'es_dashboard' || $screen->id == 'es_template' || $screen->parent_base == 'admin_page_es_template_preview' ) {
		?>

        <div class="headerpart">
            <div class="esbgheader">
                <h1>Email Subscribers V4.0</h1>
            </div>
        </div>
		<?php
	}
}

// add_action( 'admin_notices', 'header_part' );


?>