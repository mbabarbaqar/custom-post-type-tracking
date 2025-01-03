<?php
	global $wpdb;
	$table_name = $wpdb->prefix . "culture_user_tracking";
	$my_products_db_version = '1.0.0';
	$charset_collate = $wpdb->get_charset_collate();

	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) != $table_name ) {
	    $sql = "CREATE TABLE $table_name (
		  `ID` mediumint(9) NOT NULL AUTO_INCREMENT,
		  `user_id` int(11) DEFAULT NULL,
		  `group_id` int(11) DEFAULT NULL,
		  `culture_name` varchar(200) DEFAULT NULL,
		  `culture_id` int(11) DEFAULT NULL,
		  `parent_culture_id` int(11) NOT NULL,
		  `browser` text DEFAULT NULL,
		  `type` enum('groups','category') DEFAULT NULL,
		  `datetime` datetime NOT NULL,
		  `dated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		  PRIMARY KEY (`ID`)
		)    $charset_collate;";
	    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	    dbDelta( $sql );
	    add_option('my_db_version', $my_products_db_version );
	}

?>