<?php
function civic_is_civic_manager_user() {
    $user = wp_get_current_user();

    return $user && in_array( 'civic_manager', (array) $user->roles, true );
}

add_filter( 'admin_body_class', function( $classes ) {
    if ( civic_is_civic_manager_user() ) {
        $classes .= ' civic-manager-admin';
    }

    return $classes;
} );

add_action( 'admin_enqueue_scripts', function() {
    if ( ! civic_is_civic_manager_user() ) {
        return;
    }

    wp_enqueue_style(
        'civic-manager-admin',
        get_stylesheet_directory_uri() . '/assets/css/civic-manager-admin.css',
        array(),
        '1.0.0'
    );
} );

add_filter( 'generate_copyright', 'civic_generate_copyright' );

function civic_generate_copyright( $copyright ) {
    return sprintf(
        '&copy; %1$s %2$s. All Rights Reserved.',
        date( 'Y' ),
        get_bloginfo( 'name' )
    );
}

?>