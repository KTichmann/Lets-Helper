<?php
/*
Plugin Name: Letsencrypt Helper
Plugin URI:  github.com/ktichmann/letsencrypt-helper
Description: A letsencrypt helper plugin that allows management of ssl certificates from the wordpress frontend.
Version:     0.1
Author:      Ktichmann
Author URI:  ktichmann.github.io
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: gcp
Domain Path: /languages
*/

defined( 'ABSPATH' ) or die( 'Sorry you can not access this file!'  );

function lets_helper_enqueue_script() {
    wp_register_style( 'bootstrap_admin_css', plugins_url('assets/css/bootstrap.min.css', __FILE__));
    wp_register_style( 'font_awesome_admin_css', plugins_url('assets/css/font-awesome.css', __FILE__));
    wp_register_script( 'bootsrap_admin_script', plugins_url('assets/js/bootstrap.min.js', __FILE__));
    wp_register_script( 'popper_admin_script', plugins_url('assets/js/popper.min.js', __FILE__));
    wp_register_script( 'letsencrypt_script', plugins_url('assets/js/letsencrypt.js', __FILE__ ));

    wp_enqueue_style( 'bootstrap_admin_css' );
    wp_enqueue_style( 'font_awesome_admin_css' );
    wp_enqueue_script( 'letsencrypt_script' );
    wp_enqueue_script( 'bootsrap_admin_script' );
    wp_enqueue_script( 'popper_admin_script' );
}
add_action( 'admin_enqueue_scripts', 'lets_helper_enqueue_script' );

require plugin_dir_path(__FILE__) . 'includes/Letsencryptor.php';

// Added per blog menu items https://developer.wordpress.org/reference/functions/add_menu_page/ 
function lh_add_menu_page() {
    if(is_super_admin()){
        add_menu_page('Lets Helper', 'Letsencrypt Helper', 'manage_network', 'lets_helper', 'lh_letsencrypt_init');
    }
}
register_activation_hook(__FILE__, 'letsencrypt_create_db_table');

add_action('admin_menu', 'lh_add_menu_page');
