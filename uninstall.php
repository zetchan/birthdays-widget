<?php
//if uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	die();

Birthdays_Widget_Installer::deactivate_multisite();

/* //drop a custom db table
global $wpdb;
$table_name = $wpdb->prefix . "birthdays";
$wpdb->query( "DROP TABLE IF EXISTS `$table_name`;" );

//delete plugin's options
delete_option( 'birthdays_settings' );

//delete all of our user meta
$users = get_users( array( 'fields' => 'id' ) );
foreach ( $users as $id ) {
    delete_user_meta( $id, 'birthday_id' );
} */
?>