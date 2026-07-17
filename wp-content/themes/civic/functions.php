<?php
function civic_is_civic_manager_user() {
    $user = wp_get_current_user();

    return $user && in_array( 'civic_manager', (array) $user->roles, true );
}

function civic_is_civic_admin_page() {
    if ( ! is_admin() || empty( $_GET['page'] ) ) {
        return false;
    }

    $page = sanitize_key( wp_unslash( $_GET['page'] ) );

    return 0 === strpos( $page, 'civic-' );
}

add_filter( 'admin_body_class', function( $classes ) {
    if ( civic_is_civic_admin_page() ) {
        $classes .= ' civic-manager-admin';
    }

    return $classes;
} );

// add_action( 'admin_enqueue_scripts', function() {
//     if ( ! civic_is_civic_admin_page() ) {
//         return;
//     }

//     wp_enqueue_style(
//         'civic-manager-admin',
//         get_stylesheet_directory_uri() . '/assets/css/civic-manager-admin.css',
//         array(),
//         '1.0.0'
//     );
// } );

add_filter( 'generate_copyright', 'civic_generate_copyright' );

function civic_generate_copyright( $copyright ) {
    return sprintf(
        '&copy; %1$s %2$s. All Rights Reserved.',
        date( 'Y' ),
        get_bloginfo( 'name' )
    );
}

add_filter( 'the_author', 'custom_hide_author_name' );
add_filter( 'get_the_author_display_name', 'custom_hide_author_name' );

function custom_hide_author_name( $name ) {
    if ( ! is_admin() ) {
        return '';
    }
    return $name;
}

?>
