<?php

/**
 * Plugin Name: Liip Author Manager
 * Plugin URI:  http://liip.ch
 * Description: A plugin that enables Editors to create Virtual Authors (without a need for Author E-Mail). Such Authors would not be able to login however, they could be referenced by the Editor as Authors of specific Blog(s) - though not by themselves...
 * Version:     1.0.0
 * Author:      Liip AG
 * Author URI:  http://liip.ch
 * Text Domain: lam
 */

/**
 * NOTE: "LAM" IS JUST AN ACRONYM FOR: LIIP AUTHOR MANAGER
 */
define( 'LAM_VERSION' ,     '1.0.0' );
define( 'LAM_PLUGIN_FILE',  __FILE__ );
define( 'LAM_PLUGIN_URL',   untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'LAM_PLUGIN_DIR',   dirname( __FILE__ ) );

$GLOBALS['LAM']['AUTHOR']   = NULL;

function registerLamPostType(){
    $labels = array(
        'name'                => __( 'Managed Authors',         'lam' ),
        'singular_name'       => __( 'Managed Author',          'lam' ),
        'menu_name'           => __( 'Managed Authors',         'lam' ),
        'parent_item_colon'   => __( 'Parent Author',           'lam' ),
        'all_items'           => __( 'All Authors',             'lam' ),
        'view_item'           => __( 'View Author',             'lam' ),
        'add_new_item'        => __( 'Add New Author',          'lam' ),
        'add_new'             => __( 'Add New',                 'lam' ),
        'edit_item'           => __( 'Edit Author',             'lam' ),
        'update_item'         => __( 'Update Author',           'lam' ),
        'search_items'        => __( 'Search Author',           'lam' ),
        'not_found'           => __( 'Not Found',               'lam' ),
        'not_found_in_trash'  => __( 'Not found in Trash',      'lam' ),
    );

    $args   = array(
        'label'               => __( 'managed_authors', 'lam' ),
        'description'         => __( 'Manage Authors who are registered without Emails.', 'lam' ),
        'labels'              => $labels,
        'supports'            => array( 'title', 'revisions', 'custom-fields', ),       // 'editor', 'excerpt', 'author', 'thumbnail', 'comments',
        'taxonomies'          => [],
        'hierarchical'        => false,
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_admin_bar'   => true,
        'show_in_nav_menus'   => false,
        'menu_position'       => 5,
        'can_export'          => true,
        'has_archive'         => true,
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
        'capability_type'     => 'post',
    );
    register_post_type( 'managed_authors', $args );
}

function filterLamPostData( $data , $postarr ) {
    // GRAB THE NAME OF THE USER FROM THE $data VARIABLE
    $user_name      =   trim($data['post_title']);
    $fistLastName   =   explode(" ", $user_name);

    // CHECK THAT THE USERNAME DOES NOT ALREADY EXIST
    $user_id        = username_exists( $user_name );

    // SINCE WE DON'T CARE ABOUT THE REAL EMAILS, WE JUST ASSIGN EACH MANAGED AUTHOR AN EMPTY
    $user_email     = null;

    // NO NEED TO CHECK IF THE EMAIL EXISTS SINCE IT IS THE SAME FOR ALL MANAGED AUTHORS.
    if ( !$user_id) {           //  && email_exists($user_email) == false
        // NOW GENERATE SOME PASSWORDS (JUST TO SATISFY WORDPRESS)  - THEN CREATE THE USER AFTERWARDS.
        $random_password        = wp_generate_password( $length=12, $include_standard_special_chars=false );
        $user_id                = wp_create_user( $user_name, $random_password, $user_email );
    }
    $GLOBALS['LAM']['AUTHOR']   = $user_id;

    updateManagedAuthorMetaData($user_id, $fistLastName);
    return $data;
}

function updateManagedAuthorMetaData($user_id, $fistLastName){
    // UPDATE THE USER METADATA...
    $update_data                        = [];
    $update_data['user_url']            = "https://wordpress.org/ionurboz";
    $update_data['first_name']          = isset($fistLastName[0]) ? $fistLastName[0] :  "";
    $update_data['last_name']           = isset($fistLastName[1]) ? $fistLastName[1] :  "";
    $update_data['wp_capabilities']     = ['author'=>true];     //serialize();
    $update_data['wp_user_level']       = 8;

    foreach($update_data as $key => $value) {
        update_user_meta($user_id, $key, $value );
    }
}

function handleAfterDeletionProcessing() {
    add_action( 'before_delete_post',   'synchronizeUserData', 10 );
}

function synchronizeUserData($post_id){
    global $wpdb;
    $prefix         = $wpdb->prefix;
    $uid            = get_post_meta($post_id, 'lam_uid', true);

    // DO WE HAVE A USER WITH THE GIVEN USER-ID ($uid)?
    // IF WE DO, WE MAY HAVE TO DELETE THAT AS WELL....
    if ( $wpdb->get_var( $wpdb->prepare( 'SELECT ID FROM '      . $prefix . 'users WHERE ID = %d', $uid ) ) ) {
        $wpdb->query( $wpdb->prepare( 'DELETE FROM '            . $prefix . 'users WHERE ID = %d', $uid ) );
        // SIMILARLY, CHECK IF THE USER_META EXIST FOR THE GIVEN $uid AND DELETE IT AS WELL
        if ( $wpdb->get_var( $wpdb->prepare( 'SELECT user_id FROM ' . $prefix . 'usermeta WHERE user_id = %d', $uid ) ) ) {
            $wpdb->query( $wpdb->prepare( 'DELETE FROM '            . $prefix . 'usermeta WHERE user_id = %d', $uid ) );
        }
    }
}

function updateAuthorMeta($post_id){
    if (wp_is_post_revision($post_id)){return;}
    if(get_post_type($post_id) == "managed_authors" && ($aid=$GLOBALS['LAM']['AUTHOR'])){
        // UPDATE THE POST METADATA TO TAKE INTO ACCOUNT THE USER ID...
        update_post_meta( $post_id, 'lam_uid', $aid );
    }
}

function activateLAMPlugin(){
    // TODO
}

function deactivateLAMPlugin(){
    // TODO
}


add_action( 'save_post',            'updateAuthorMeta');
add_action( 'init',                 'registerLamPostType',  0 );
add_filter( 'wp_insert_post_data',  'filterLamPostData',    '99', 2 );

if ( is_admin() ) {
    add_action( 'admin_init',       'handleAfterDeletionProcessing' );
}

// REGISTER PLUGIN ACTIVATION HOOK
register_activation_hook(LAM_PLUGIN_FILE, 'activateLAMPlugin');

// REGISTER PLUGIN DEACTIVATION HOOK
register_deactivation_hook(LAM_PLUGIN_FILE, 'deactivateLAMPlugin');
