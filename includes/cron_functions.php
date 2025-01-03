<?php
/**
* Cron functions for user tracking emails
*/
if(isset($_GET['test'])){
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}


$parsePages = explode("/", $_SERVER['REDIRECT_URL']);
if(in_array('tracking_cron', $parsePages)){
	global $session;
	$rootPath = $_SERVER['DOCUMENT_ROOT'];
	
	$extraFolder = $_SERVER['REDIRECT_URL'];
	$extraFolder = str_replace("/tracking_cron", "", $extraFolder);
	$rootPath .= $extraFolder; 
	if(isset($_GET['cron_tracking_emails'])){
		global $wpdb;
		
		//require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
		require_once $rootPath . '/wp-includes/pluggable.php';
		
		$cronStartDate = date('Y-m-d H:i:s', strtotime('-30 days'));
		$cronEndDate = date("Y-m-d H:i:s", strtotime('+1 days'));
		$selected = esc_attr( get_option('cron_fequency'));
		if($selected == "daily"){
			$cronStartDate = date("Y-m-d H:i:s", strtotime("-1 day"));
		}elseif($selected == "weekly"){
			$cronStartDate = date("Y-m-d H:i:s", strtotime("-1 week"));
		}elseif($selected == "two-weeks"){
			$cronStartDate = date("Y-m-d H:i:s", strtotime("-2 week"));
		}elseif($selected == "monthly"){
			$cronStartDate = date("Y-m-d H:i:s", strtotime("-1 month"));
		}
		
		$prefix = $wpdb->prefix;
		$trackingTable = $prefix . "culture_user_tracking";
		$users = $wpdb->get_results( "
			SELECT user_id FROM $trackingTable 
			WHERE `datetime` >= '$cronStartDate' AND `datetime` <= '$cronEndDate'
			GROUP BY user_id
		", ARRAY_A);
		
		if(count($users) > 0){
			foreach($users as $key => $user){
				$userId = $user['user_id'];
				user_tracking_tab_menu($userId, $cronStartDate, $cronEndDate);
			}
		}
		
		//Delete able folder after Attachments
		//$session['DELETEABLE_FOLDER']
		
		if(isset($session['DELETEABLE_FOLDER'])){
			echo "Delete Folde \r\n";
			if(file_exists($session['DELETEABLE_FOLDER'])){
				recursive_rmdir($session['DELETEABLE_FOLDER']);
				unset($session['DELETEABLE_FOLDER']);
			}
		}
		exit('Cron Completed');
	}else{
		die("Parameter Undefined.");
	}
	
} 

?>