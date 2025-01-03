<?php 
session_start();
/**
 * Register our plugins menus
 *
 * @since unknown
 */
function user_tracking_plugin_menu() {
	global $blog_id;

	$capability = ( is_multisite() ) ? 'create_users' : 'delete_users';

	/**
	 * Filters the minimum capability needed to view options page.
	 *
	 * @since 4.3.0
	 *
	 * @param string $capability Minimal capability required.
	 */
	$minimum_cap = apply_filters( 'bp_registration_filter_minimum_caps', $capability );

	add_menu_page(
		__( 'Usage Tracking Report', 'user_tracking' ),
		__( 'Usage Tracking', 'user_tracking' ),
		$minimum_cap,
		'user_tracking',
		'user_tracking_tab_menu',
		'dashicons-groups'
	);

	/*$count = '<span class="update-plugins count-12"><span class="plugin-count">12</span></span>';
	*/
	add_submenu_page(
		'user_tracking',
		__( 'Options', 'user_tracking' ),
		__( 'Tracking Options', 'user_tracking' ),
		$minimum_cap,
		'tracking-options',
		'tracking_options_function'
	);

}
add_action( 'admin_menu', 'user_tracking_plugin_menu' );

/**
 * Adds our setting links to the BuddyPress member menu for our administrators.
 *
 * @since 1.0
 *
 * @return bool
 */
function pc_export_admin_bar_add() {
	global $wp_admin_bar, $bp;

	if ( ! bp_use_wp_admin_bar() || defined( 'DOING_AJAX' ) ) {
		return false;
	}

	if ( ! current_user_can( 'delete_users' ) ) {
		return false;
	}

	$general_settings  		= admin_url( 'admin.php?page=user_tracking' );
	$user_export_link  		= admin_url( 'admin.php?page=hits_by_page' );

	$wp_admin_bar->add_menu( array(
		'parent' => $bp->my_account_menu_id,
		'id'     => 'user-tracking',
		'title'  => __( 'User Tracking', 'user-tracking' ),
		'meta' => array( 'class' => 'menupop' ),
		'href'   => $general_settings,
	) );

	// Submenus.
	$wp_admin_bar->add_menu( array(
		'parent' => 'user-tracking',
		'id'     => 'user_tracking',
		'title'  => __( 'User Tracking', 'user-tracking' ),
		'href'   => $general_settings,
	) );

	return true;
}
add_action( 'bp_setup_admin_bar', 'pc_export_admin_bar_add', 300 );


/**
 * Create our tab navigation between setting pages
 *
 * @since  unknown
 *
 * @param string $page Page title to render.
 */
function user_tracking_tab_menu($cron_user_id = NULL, $reportStartDate = NULL, $reportEndDate = NULL) {
	global $session;
	// Define dates to default if not selected
	$export = FALSE;
	$userID = NULL;
	$sendReport = FALSE;
	$emailReportToUser = FALSE;
	if(!empty($cron_user_id) && !empty($reportStartDate) && !empty($reportEndDate)){
		#If function running from cron job side
		$export = TRUE;
		$userID = $cron_user_id;
		$startDate = date('Y-m-d', strtotime($reportStartDate));
		$endDate = date("Y-m-d H:i:s", strtotime($reportEndDate));
		$emailReportToUser = TRUE;
	}else{
		$startDate = date('Y-m-d', strtotime('-30 days'));
		$endDate = date("Y-m-d H:i:s", strtotime('+1 days'));
		if(isset($_POST['export_tracking_data']) && $_POST['export_tracking_data'] == 'true'){
			$export = TRUE;
		}
		
		if(isset($_POST['user_id'])){
			$userID = $_POST['user_id'];
			$_SESSION['user_id'] = $userID;
		}else{
			if(isset($_SESSION['user_id'])){
				$userID = $_SESSION['user_id'];
			}
		}
		if(isset($_POST['start_date']) && !empty($_POST['start_date'])){
			$startDate = $_POST['start_date'];
			$_SESSION['start_date'] = $startDate;
		}else{
			if(isset($_SESSION['start_date'])){
				$startDate = $_SESSION['start_date'];
			}
		}
		if(isset($_POST['end_date']) && !empty($_POST['end_date'])){
			$endDate = $_POST['end_date'];
			$_SESSION['end_date'] = $endDate;
		}else{
			if(isset($_SESSION['end_date'])){
				$endDate = $_SESSION['end_date'];
			}
		}
	}
	
	if(isset($_POST['send_report']) && $userID){
		$sendReport = TRUE;
		$export = TRUE;
	}
	
	
	
	if($startDate){
		$startDate = $startDate . " 00:00:00";
		$startDate = date('Y-m-d H:i:s', strtotime($startDate));
	}
	
	$filterForm = "";
	$exportForm = "";
	
	// Extract new records using dates
	$filterForm .= "<form action='' method='POST'>
		<input type='date' required='true' name='start_date' value='".date('Y-m-d', strtotime($startDate))."' class='date-fields' />
		<input type='date' required='true' name='end_date' value='".date('Y-m-d', strtotime($endDate))."' class='date-fields' />";
	$filterForm .= prepare_dropdown_users_list($userID);
	$filterForm .= "<div id='send_report_container' style='display:none;'><br /><input type='checkbox' name='send_report' id='send_report' value='1' > <label for='send_report'>Send report to this user.</label></div>";
	$filterForm .= "<br />";
	$filterForm .= "<input type='submit' name='resource-export-button' value='View Hits' class='view-hits-button' />";
	$filterForm	.= "</form>";
	
	// Export Form
	$exportForm .= "<form action='' method='POST'>
		<input type='hidden' required='true' name='export_tracking_data' value='true' class='date-fields' />";
	$exportForm .= "<input type='submit' name='resource-export-button' value='Export Excel' class='data-export-button' />";
	$exportForm	.= "</form>";

	//Tabs view for page
	$tabsSection = '<h2 class="nav-tab-wrapper">
		<a class="nav-tab nav-tab-active" data-id="#user_tracking" href="javascript:;">Total Hit Count</a>
		<a class="nav-tab" href="javascript:;" data-id="#hits_by_page">Hits by Page</a>
	</h2>';
		
	$firstSheetTop = "";
	$userName = NULL;
	$userEmail = NULL;
	if($userID){
		if(function_exists('get_user_by')){
			$user = get_user_by("ID", $userID);
			$userName = $user->data->display_name;
			$userEmail = $user->data->user_email;
		}else{
			global $wpdb;
			$prefix = $wpdb->prefix;
			$trackingTable = $prefix . "users";
			$user = $wpdb->get_results( "
				SELECT display_name FROM $trackingTable 
				WHERE ID = '$userID'
			", ARRAY_A);
			if(isset($user[0])){
				$userName = $user[0]['display_name'];
				$userEmail = $user[0]['user_email'];
			}
		}
		if($userName){
			$firstSheetTop .= "<div style='padding-top: 15px;'><span><b>Selected User:</b></span> <span>" .$userName. "</span></div>";
		}
	}
	if(!empty($startDate)){
		$firstSheetTop .= "<div style='padding-top: 15px;'>
		<span><b>Start Date:</b></span> <span>" .date("F j, Y", strtotime($startDate)). "</span></div>";
	}
	if(!empty($endDate)){
		$firstSheetTop .= "<div style='padding-top: 15px;'>
		<span><b>End Date:</b></span> <span>" .date("F j, Y", strtotime($endDate)). "</span></div>";
	}
		
	/**
	* Get reports data all
	*/
	$reportsItems = array();
	$reportsItems['groups'] = array();
	$reportsItems['category'] = array();
	
	$savedHits = getReportsData($startDate, $endDate, $userID);
	
	if(isset($savedHits['total']['total'])){
		$firstSheetTop .= "<div style='padding-top: 15px;'>
		<span><b>Total Hit Count:</b></span> <span>" .$savedHits['total']['total']. "</span></div>";
	}
		
	$firstSheetTop .= "<div class='export_container'>". $exportForm . "</div>";
	//End Firstsheet top area
	
	foreach($savedHits['report_data'] as $key => $value){
		$dataArr = array(
			"user_id" => $value['user_id'],
			"culture_name" => $value['culture_name'],
			"culture_id" => $value['culture_id'],
			"date" => $value['datetime'],
			"count" => $value['count'],
		);
		
		$reportsItems[$value['type']][] = $dataArr;
	}
		
	$firstSheet = "";
	$firstSheet .= "<br />";
	$firstSheet .= "<div id='user_tracking' class='content_box'>";
	if(count($savedHits['report_data']) > 0){
		foreach($reportsItems as $key => $value){
			$firstSheet .= "<div><table border=1 class='wp-list-table widefat fixed striped table-view-list tags'>";
				$firstSheet .= "<tr><td width='80%'><b>".ucwords($key)."</b></td><td width='20%'><b>PageHits</b></td></tr>";
				foreach($reportsItems[$key] as $key2 => $value2){
					$prepareName = str_replace("-", " ", $value2['culture_name']);
					$prepareName = ucwords($prepareName);
					$firstSheet .= "<tr><td width='80%'>$prepareName</td><td width='20%'>".$value2['count']."</td></tr>";
				}
				
			$firstSheet .= "</table></div>";
			$firstSheet .= "<br />";
		}
	}else{
		$sendReport = FALSE;
		$export = FALSE;
		$firstSheet .= "<div class='message-box'>The selected user have no records to export</div>";
	}
	$firstSheet .= "</div>";
	#End First tab
	
	/**
	* Start second tab functionality
	* Seacond tap for page hits
	* Prepare page hits data
	*/
	
	
	/**
	* To display horizontally
	* Get all visited categories from above data
	*/
	$allCategories = array();
	if(count($reportsItems['category']) > 0){
		$allCategories = $reportsItems['category'];
	}
		
	/**
	* Prepare groups to display vertically
	*/
	$groupsAll = array();
	$all_groups_json = plugin_dir_path( __FILE__ ) . "../temp/all_groups.json";
	if(file_exists($all_groups_json)){
		$data = @file_get_contents($all_groups_json);
		if($data){
			$groupsAll = json_decode($data);
		}
	}
	if(!$groupsAll){
		$cultur_groups = get_terms('culturescategoriess', 
		array(
            'parent'    => 3,
            'order'  => 'ASC',
            'orderby'=> 'title',
            'hide_empty' => false
        ) );
        $groupsAll = array();
        foreach($cultur_groups as $key => $value){
			$groups = get_terms('culturescategoriess', 
			array(
	            'parent'    => $value->term_id,
	            'order'  => 'ASC',
	            'orderby'=> 'title',
	            'hide_empty' => false
	        ) );
	        $groupsAll = array_merge($groupsAll,$groups);
		}
		
		if($groupsAll){
			$groupsAllData = json_encode($groupsAll);
			$myfile2 = fopen($all_groups_json, "w");
			fwrite($myfile2, $groupsAllData);
			fclose($myfile2);
		}
	}
    
	$pageHitsData = array();
	foreach($savedHits['report_data_page'] as $key => $value){
		if($value['type'] == "groups"){
			$pageHitsData['groups'][$value['culture_id']] = $value;
		}else{
			$parentGroupId = $value['group_id'];
			$titleSlug = str_replace(" ", "-", $value['culture_name']);
			$pageHitsData['categories'][$parentGroupId][$titleSlug] = $value;
		}
	}
	
	$groupCategories = array();
	if(isset($pageHitsData['categories'])){
		$groupCategories = $pageHitsData['categories'];
	}
	
	#Useable data for display
	#1. $allCategories (Horizontal)
	#2. $groupsAll (Vertical)
	#3. $groupCategories (To get exact count by group id and cat title)
	$secondSheet = "";
	$DisplaySecond = "";
	$DisplaySecond .= "<div id='hits_by_page' class='content_box' style='display:none;'>";
	if(count($savedHits['report_data']) > 0){
		$secondSheet = "<table border=1 class='wp-list-table widefat fixed striped table-view-list tags'>";
			#Display Heading top
			$secondSheet .= "<tr>";
			$secondSheet .= "<td style='width:10%;text-align: right;'> </td>";
			foreach($allCategories as $key => $value){
				$secondSheet .= "<td style='vertical-align: bottom;text-align: right;'><span class='bottom-top'><b>{$value['culture_name']}</b></span></td>";
			}
			$secondSheet .= "</tr>";
			
			#Display all groups vertically
			foreach($groupsAll as $key2 => $value2){
				$groupName = $value2->name;
				$groupID = $value2->term_id;
				$secondSheet .= "<tr>";
					$secondSheet .= "<td>$groupName</td>";
					
					#display category count data horizontally
					foreach($allCategories as $key3 => $value3){
						$titleSlug = str_replace(" ", "-", $value3['culture_name']);
						
						#Fetch single category of each group
						$count = 0;
						if(isset($groupCategories[$groupID][$titleSlug])){
							$groupCat = $groupCategories[$groupID][$titleSlug];
							$count = $groupCat['cat_count'];
						}
						$secondSheet .= "<td style='text-align: right;'>$count</td>";
					}
				$secondSheet .= "</tr>";
			}
			
		$secondSheet .= "</table>";
		
		$DisplaySecond .= "<div>";
		$DisplaySecond .= $secondSheet;
		$DisplaySecond .= "</div>";
	}else{
		$DisplaySecond .= "<div class='message-box'>The selected user have no records to export</div>";
	}
	$DisplaySecond .= "</div>";
	
	//Display all content here
	if(!$emailReportToUser){
		?>
		<h1><?php esc_html_e( 'Usage Tracking', 'user_tracking' ); ?></h1>
		<?php
		echo $filterForm;
		echo $tabsSection;
		echo $firstSheetTop;
		echo $firstSheet;
		echo $DisplaySecond;			
	}

	
	pc_wp_export_data();
	
	$htmlArr = array();
	if($export == TRUE){
		$firstSheet .= $firstSheetTop;
		$excelData = array(
			"file_name" => "tracking_reports_{$userID}",
			"sheets" => array(
				array(
					"name" => "Total Hits Count",
					"content" => $firstSheet,
				),
				array(
					"name" => "Hits By Page",
					"content" => $secondSheet,
				),
			)
		);
		$filePath = NULL;
		$fileUrl = NULL;
		include(HS_ROOT_PATH . 'tracking-export.php');
		if(($emailReportToUser || $sendReport) && $userEmail && $userName){
			if(file_exists($filePath)){
				$adminEmail = get_option('admin_email');
				if(get_option('tracking_email_subject')){
					$subject = esc_attr( get_option('tracking_email_subject'));
				}else{
					$subject = "Usage Tracking Report";
				}
				if(get_option('tracking_email_body')){
					$body = esc_attr( get_option('tracking_email_body'));
					$body = str_replace("[user-name]", $userName, $body);
				}else{
					$body = "Hi \r\n";
					$body = "Tracking report attached \r\n";
					$body .= "Regarding \r\n CR Culture Vision";
				}
				
				if(get_option('tracking_email_bcc')){
					$emailString = esc_attr( get_option('tracking_email_bcc'));
					foreach(explode(",",$emailString) as $key3 => $bccEmail){
						$bccEmail = trim($bccEmail);
						$headers[] = "Bcc: $bccEmail";
					}
				}else{
					$headers[] = 'Bcc: muhammad.babar@hashehouse.com';
				}
				
				$attachments = array($filePath);
				$headers[] = "From: CR Culture Vision <$adminEmail>";
				
				
				
				wp_mail($userEmail, $subject, $body, $headers, $attachments);					
			}
		}
		
		//Download generated file File
		if($fileUrl && !$emailReportToUser && !$sendReport){
			echo "<script>window.location.href = '".$fileUrl."';</script>";
			exit();
		}
	}
	
	if($export != TRUE){
		if(isset($session['DELETEABLE_FOLDER'])){
			echo "Delete Folde \r\n";
			if(file_exists($session['DELETEABLE_FOLDER'])){
				recursive_rmdir($session['DELETEABLE_FOLDER']);
				unset($session['DELETEABLE_FOLDER']);
			}
		}
	}
	
	
	?>
<?php 
}

/**
* Remove a folder along with inner files and folders
* @param String $dir
* @return
*/
function recursive_rmdir($dir){
    if( is_dir($dir) ) { 
      $objects = array_diff( scandir($dir), array('..', '.') );
      foreach ($objects as $object) { 
        $objectPath = $dir."/".$object;
        if( is_dir($objectPath) )
          recursive_rmdir($objectPath);
        else
          unlink($objectPath); 
      } 
      rmdir($dir); 
    } 
}

/**
* Tab contents
*/
function pc_wp_export_data(){
	
	//form section starts here
	/*$postCount = wp_count_posts();
	$published_posts = $postCount->publish;*/
	
	echo "
		<style>
			.export-types{
				padding:20px; 
				margin-top:5px;
				color: #10585a;
			}
			.export-counts{
				color: #d07609;
			}
			.bottom-top{
				  writing-mode: vertical-rl;
				  transform: rotate(180deg);
				  text-align: right;
			}
			.export_container{
				display: block;
			    width: auto;
			    float: right;
			    margin-top: -25px;
			}
			.export_container .data-export-button{
				width: 150px;
			    padding: 5px;
			    cursor: pointer;
			}
			.view-hits-button{
				margin-top:10px;
			}
		</style>
	";
	scriptLoader();
	
}
/**
* Script Loader
* 
* @return
*/
function scriptLoader(){
	
	echo "
		<script>
			jQuery('.user-to-export').change(function(e){
                e.preventDefault();
                window.location.href = 'admin.php?page=hits_by_page&selected='+jQuery(this).val();
                //jQuery('#ms-usermeta .ms-selectable li.system').hide();
            });
			jQuery('.nav-tab').click(function(e){
                e.preventDefault();
                var contentID = jQuery(this).attr('data-id');
				
				//Add active class to menu
				jQuery('.nav-tab').removeClass('nav-tab-active');
                jQuery(this).addClass('nav-tab-active');
                
                //Open related content box
                jQuery('.content_box').hide();
                jQuery(contentID).show();
            });
			jQuery('.exportBtn').click(function(e){
                e.preventDefault();
                //jQuery('#ms-usermeta .ms-selectable li.system').hide();
            });
			jQuery('#user-list').change(function(e){
                e.preventDefault();
				var selectedVal = jQuery(this).val();
				if(selectedVal == '0'){
                	jQuery('#send_report_container').hide();
				}else{
					jQuery('#send_report_container').show();
				}
            });
            var selectedVal = jQuery('#user-list').val();
			if(selectedVal == '0'){
            	jQuery('#send_report_container').hide();
			}else{
				jQuery('#send_report_container').show();
			}
		</script>
		<style type='text/css'>
			.dropdown_types {
			    width: 250px;
			    float: left;
			    margin-top: 20px;
			    margin-left: 22px;
			    color: #10585a;
			}
			.dropdown_button {
			    width: 95%;
			    float: left;
			    margin-top: 20px;
			    padding-left: 20px;
			}
			.message-box {
			    padding: 20px;
			    color: #ab6510;
			    background-color: gainsboro;
			    font-size: 15px;
			    margin-left: 20px;
			    width: auto;
			    max-width: 90%;
			}
			.export-counts{
				color: #d07609;
			}
		</style>
	";
}


/**
* Options Page and functionality
* 
* @return
*/
function register_user_tracking_options() {
	/**
	* Step 1
	* Register all fields
	*/
	register_setting('user-tracking-options', 'cron_fequency');
	register_setting('user-tracking-options', 'tracking_email_subject');
	register_setting('user-tracking-options', 'tracking_email_body');
	register_setting('user-tracking-options', 'tracking_email_bcc');
}
add_action( 'admin_init', 'register_user_tracking_options' );
function tracking_options_function(){
	?>
	
	<h1><?php esc_html_e( 'Usage Tracking Options', 'user_tracking' ); ?></h1>
	
	<h2 class="nav-tab-wrapper">
		<a class="nav-tab nav-tab-active" data-id="#user_tracking" href="javascript:;"><?php esc_html_e( 'General Options', 'user_tracking' ); ?></a>
	</h2>
	
	<div class="" style="padding-top: 20px;">
		<p>
			This function will send reports to users of the specific period selected from the list below. 
			<br />Cron URL: [tracking_cron?cron_tracking_emails=running]
		</p>
	 	<form method="post" action="options.php">
	 		<?php 
	 			/**
	 			 * Step 2
				 * Initialize form settings
				 * User these two lines to initialize form options
				 */
		 		settings_fields('user-tracking-options'); 
		 		do_settings_sections('user-tracking-options');
	 		?>
	 		<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="blogname">Select Reports time period </label>
						</th>
						<td>
							<?php 
								$selected = esc_attr( get_option('cron_fequency'));
								
							?>
							<select name="cron_fequency">
					 			<option value="daily" <?php echo (($selected == 'daily')? "selected='selected'":"") ?>>Daily</option>
					 			<option value="weekly" <?php echo (($selected == 'weekly')? "selected='selected'":"") ?>>Weekly</option>
					 			<option value="two-weeks" <?php echo (($selected == 'two-weeks')? "selected='selected'":"") ?>>Every Two Weeks</option>
					 			<option value="monthly" <?php echo (($selected == 'monthly')? "selected='selected'":"") ?>>Monthly</option>
					 		</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="blogname">Tracking Email Subject </label>
						</th>
						<td>
							<?php 
								$selectedSubject = esc_attr( get_option('tracking_email_subject'));
								
							?>
							<div class="form-field form-required term-name-wrap">
								<input name="tracking_email_subject" id="tracking_email_subject" type="text" value="<?php echo $selectedSubject; ?>">
								<p>This text will be used as subject in usage tracking email.</p>
							</div>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="blogname">Tracking Email Body </label>
						</th>
						<td>
							<?php 
								$selectedText = esc_attr( get_option('tracking_email_body'));
								
							?>
							<div class="form-field form-required term-name-wrap">
								<textarea rows="10" name="tracking_email_body" id="tracking_email_body"><?php echo $selectedText; ?></textarea>
								<p>This text will be used as body of usage tracking email.</p>
							</div>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="blogname">Tracking Email BCC Emails </label>
						</th>
						<td>
							<?php 
								$selectedBcc = esc_attr( get_option('tracking_email_bcc'));
								
							?>
							<div class="form-field form-required term-name-wrap">
								<textarea rows="2" name="tracking_email_bcc" id="tracking_email_bcc"><?php echo $selectedBcc; ?></textarea>
								<p>Add BCC emails to track this email functionality. Use comma separator(,) for multiple emails.</p>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
	 		<?php 
	 			/**
				 * Step 3
				 * Add wp options default button
				 */
	 			submit_button(); 
	 		?>
	 	</form>
	</div>
	<!--Show Head info-->
	<?php 
}

/**
* Babar
* Download culture tracking function for frontend when a culture is visited
* Will set new record in table as well date and time
**/
function setResourceDownloadReports($userId, $cultureCat){
	//error_reporting( E_ALL );
	global $wpdb;
	$tableName = $wpdb->prefix . "culture_user_tracking";
	$blogtime = date("Y-m-d H:i:s");//current_time('mysql')
	
	$cultureId = $cultureCat->term_id;
	$cultureName = $cultureCat->name;
	if(empty($cultureId) && empty($cultureName)){
		return;
	}
	
	$culType = NULL;
	$parent = get_ancestors($cultureId, 'culturescategoriess');
	
	$parentCatId = $parent[0];
    if(count($parent) == 2){
		$culType = "groups";
	}elseif(count($parent) == 3 || count($parent) == 4){
		$culType = "category";
	}//Get second level id, or get group id 
	if(count($parent) > 2){
		$group_id_index = (count($parent) - 3);
		$group_id = $parent[$group_id_index];
	}else{
		$group_id = 0;
	}
	
	echo "<script>console.log(".$group_id.");</script>";
	
	if(!empty($userId) && !empty($culType)){
		$wpdb->insert($tableName, 
		array(
		  'user_id'       	   => $userId,
		  'group_id'       	   => $group_id,
		  'culture_id'         => $cultureId,
		  'parent_culture_id'  => $parentCatId,
		  'culture_name'       => $cultureName,
		  'browser'            => json_encode($_SERVER['HTTP_USER_AGENT']),
		  'type'       		   => $culType,
		  'datetime'           => $blogtime
		),
		array('%s','%s','%s','%s','%s','%s','%s') 
		); 		
	}

	return "Visit Tracked";
}

/**
* 
* @param $startDate
* @param $endDate
* ececute query between two dates and extract records, joining two tables
* @return array of records
*/
function getReportsData($startDate = NULL, $endDate = NULL, $user_id = NULL){
	global $wpdb;
	$prefix = $wpdb->prefix;
	$trackingTable = $prefix . "culture_user_tracking";
	$records = array();
	$whereArr = array();
	$where = "";
	if(!empty($startDate)){
		$whereArr[] = "`datetime` >= '$startDate'";
	}
	if(!empty($endDate)){
		$whereArr[] = "`datetime` <= '$endDate' ";
	}
	
	if(!empty($user_id)){
		$whereArr[] = "user_id = '$user_id'";
	}
	if($whereArr){
		$where = " WHERE " . implode(" AND ", $whereArr);
	}
	$records = array();
	$records['total'] = $wpdb->get_row( "
		SELECT COUNT(ID) as total FROM $trackingTable 
		$where
	", ARRAY_A);
	$records['report_data'] = $wpdb->get_results( "
		SELECT *, COUNT(ID) as count FROM $trackingTable 
		$where
		GROUP BY culture_name
		ORDER BY culture_name ASC
	", ARRAY_A);
	$records['report_data_page'] = $wpdb->get_results( "
		SELECT *, COUNT(ID) AS cat_count FROM $trackingTable 
		$where
		GROUP BY group_id,culture_name, type
		ORDER BY parent_culture_id, culture_name ASC
	", ARRAY_A);
	return $records;
}


function prepare_dropdown_users_list($selected){
	// Dropdown for Users list using specific parameters
	$args = array(
    'show_option_all'         => 'All', // string
    'show_option_none'        => '', // string
    'hide_if_only_one_author' => null, // string
    'orderby'                 => 'display_name',
    'order'                   => 'ASC',
    'include'                 => null, // string
    'exclude'                 => null, // string
    'multi'                   => true,
    'show'                    => 'display_name',
    'echo'                    => false,
    'selected'                => $selected,
    'include_selected'        => false,
    'name'                    => 'user_id', // string
    'id'                      => 'user-list', // integer
    'class'                   => 'date-fields', // string 
    'blog_id'                 => $GLOBALS['blog_id'],
    'who'                     => null, // string,
    'role'                    => null, // string|array,
    'role__in'                => null, // array    
    'role__not_in'            => null, // array        
	);
	
	return wp_dropdown_users($args);
}

function show_list_authors_with_args(){
	$args = array(
	    'orderby'       => 'name', 
	    'order'         => 'ASC', 
	    'number'        => null,
	    'optioncount'   => true, 
	    'exclude_admin' => false, 
	    'show_fullname' => true,
	    'hide_empty'    => true,
	    'echo'          => true,
	    'feed'          => null, 
	    'feed_image'    => null,
	    'feed_type'     => null,
	    'style'         => 'list',
	    'html'          => true,
	    'exclude'       => null,
	    'include'       => null
	);
	wp_list_authors($args);
}


/**
* Runtime hook to check page is visited, if visited then count as tracked
*/
add_action('wp_head', 'my_cookie_check', 9);
function my_cookie_check() {
	if (is_user_logged_in()) {  
		$term = get_queried_object();
		if(isset($term->taxonomy) && $term->taxonomy == "culturescategoriess"){
        	$userId = get_current_user_id();
        	/**
			* Save tracking for cultures
			*/
			setResourceDownloadReports($userId, $term);
			echo "<script>console.log('Page Visited..')</script>";
		}
        
	}
 // Your logic
}
