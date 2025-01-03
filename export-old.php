<?php
require_once(plugin_dir_path( __FILE__ ) .'PHPExcel/PHPExcel.php');
require_once(plugin_dir_path( __FILE__ ) .'PHPExcel/PHPExcel/IOFactory.php');


function exportDataToExcel(){
	// Create new PHPExcel object
	$objPHPExcel = new PHPExcel();

	// Create a first sheet, representing sales data
	$objPHPExcel->setActiveSheetIndex(0);
	$objPHPExcel->getActiveSheet()->setCellValue('A1', 'Something');

	// Rename sheet
	$objPHPExcel->getActiveSheet()->setTitle('Name of Sheet 1');

	// Create a new worksheet, after the default sheet
	$objPHPExcel->createSheet();

	// Add some data to the second sheet, resembling some different data types
	$objPHPExcel->setActiveSheetIndex(1);
	$objPHPExcel->getActiveSheet()->setCellValue('A1', 'More data');

	// Rename 2nd sheet
	$objPHPExcel->getActiveSheet()->setTitle('Second sheet');

	// Redirect output to a client’s web browser (Excel5)
	header('Content-Type: application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="name_of_file.xls"');
	header('Cache-Control: max-age=0');
	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	$objWriter->save('php://output');
}

	/**
	* Babar
	* Export All Core Post types like Posts, pages and comments etc.
	* @return excel save/open popup to export data
	*/
	function exportDataFunction2(){
		global $wpdb, $bp;
			$bp = buddypress();
		
		///////////////////
		if($_GET['selected'] && is_numeric($_GET['selected'])){
		$id = $_GET['selected'];
		}else{
			$id = 0;
		}
		
			// $posts = get_posts();
			//$pages = get_pages();
			//$pages = get_post_type();
			//count_user_posts();
            /*$results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM ga_users"
                    , $user_id
                ),
                ARRAY_N
            );
            $results = $wpdb->get_results(
				$wpdb->prepare("SELECT * FROM ".$wpdb->prefix ."posts", $args),
			     ARRAY_N);
            */


		
         // Create new PHPExcel object
		$objPHPExcel = new PHPExcel();
		// Create a first sheet, representing sales data
		/*$objPHPExcel->createSheet();
		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setCellValue('A1', 'Something');
		$objPHPExcel->getActiveSheet()->fromArray($database, NULL, 'A1');*/

		// Rename sheet
		//$objPHPExcel->getActiveSheet()->setTitle('Name of Sheet 1');
		
		
		// looping for new sheets
		$count = 0;
		foreach($_POST['pc_export'] as $postType){
			// get data for sheets
			$records = array();
			$existing_columns = array();
			$prefix = $wpdb->prefix;
			$args = array('post_type' => 'post', 'meta_value' => 'on');			
			if($postType == 'user'){
				$existing_columns = $wpdb->get_col("DESC $wpdb->users", 0);
				$records = $wpdb->get_results( "SELECT *
			    FROM $wpdb->users", ARRAY_A);
			}else if ($postType == 'comment'){
				$existing_columns = $wpdb->get_col("DESC $wpdb->comments", 0);
				$records = $wpdb->get_results( "SELECT *
			    FROM $wpdb->comments", ARRAY_A);				
			}else{
				$existing_columns = $wpdb->get_col("DESC $wpdb->posts", 0);
				$records = $wpdb->get_results( "SELECT *
			    FROM $wpdb->posts
			    WHERE post_type = '$postType' AND post_status = 'publish'", ARRAY_A);				
			}
			

		    //$testt = get_the_title(1);
			//var_dump("<pre>",$testt);exit();
			
			$objPHPExcel->createSheet();
			$objPHPExcel->setActiveSheetIndex($count);
			$objPHPExcel->getActiveSheet()->setCellValue('A1', 'No records available');
			$objPHPExcel->getActiveSheet()->fromArray($existing_columns, NULL, 'A1');
			$objPHPExcel->getActiveSheet()->fromArray($records, NULL, 'A2');
			// Rename sheet
			$objPHPExcel->getActiveSheet()->setTitle($postType.'(s) data');
			$count++;
		}
		//var_dump($_POST['pc_export']);exit();
		

		// Create a new worksheet, after the default sheet
		// Add some data to the second sheet, resembling some different data types
		////$objPHPExcel->setActiveSheetIndex(1);
		////$objPHPExcel->getActiveSheet()->setCellValue('A1', 'More data');

		// Rename 2nd sheet
		////$objPHPExcel->getActiveSheet()->setTitle('Second sheet');

		// Redirect output to a client’s web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="wp_data_export.xls"');
		header('Cache-Control: max-age=0');
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
	}

	/**
	* Babar
	* Export All Post types and buddypress elements
	* @return excel save/open popup to export data
	*/
	function bpExportDataFunction(){
		global $wpdb, $bp;
			$bp = buddypress();
		
		///////////////////
		if($_GET['selected'] && is_numeric($_GET['selected'])){
			$id = $_GET['selected'];
			if($id != $_POST['user']){
				$id = 0;
			}
		}else{
			$id = 0;
		}
		if($id != 0){
	         // Create new PHPExcel object
			$objPHPExcel = new PHPExcel();	
			// looping for new sheets
			$count = 0;
			foreach($_POST['pc_export_bp'] as $postType){
				// get data for sheets
				$records = array();
				$existing_columns = array();
				$prefix = $wpdb->prefix;
				$args = array('post_type' => 'post', 'meta_value' => 'on');			
				
				$existing_columns = $wpdb->get_col("DESC $wpdb->posts", 0);
				$records = $wpdb->get_results( "SELECT * FROM $wpdb->posts
			    WHERE post_type = '$postType' AND post_status = 'publish' AND post_author=$id", ARRAY_A);				
				
				$objPHPExcel->createSheet();
				$objPHPExcel->setActiveSheetIndex($count);
				$objPHPExcel->getActiveSheet()->setCellValue('A1', 'No records available');
				$objPHPExcel->getActiveSheet()->fromArray($existing_columns, NULL, 'A1');
				$objPHPExcel->getActiveSheet()->fromArray($records, NULL, 'A2');
				// Rename sheet
				$objPHPExcel->getActiveSheet()->setTitle($postType.'(s) data');
				$count++;
			}
			// Redirect output to a client’s web browser (Excel5)
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="wp_data_export.xls"');
			header('Cache-Control: max-age=0');
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$objWriter->save('php://output');			
		}

	}

	/**
	* Babar
	* Resources Reports
	* This function can be used dynamically for all post types to be downloaded from single pages
	* @return excel save/open popup to export data
	*/
	function exportDataFunctionForResourcesReport(){
		error_reporting( E_ALL );
		global $wpdb, $bp;
		$bp = buddypress();
         // Create new PHPExcel object
		$objPHPExcel = new PHPExcel();
		
		$startDate =  $_POST['start_date'];
		$endDate =  $_POST['end_date'];
		
		// looping for new sheets
		$count = 0;
		foreach($_POST['pc_export_reports'] as $postType){
			// get data for sheets
			$records = array();
			$existing_columns = array();
			$prefix = $wpdb->prefix;
			$args = array('post_type' => 'post', 'meta_value' => 'on');			
			$trackingTable = $prefix . "post_type_tracking";

			$existing_columns = array("ID", "Downloaded Date", "Resource Name", "Total Downloads");
			$records = $wpdb->get_results( "
			SELECT posts.ID, DATE_FORMAT(tracking.dated, '%W %M %e %Y') AS Downloaded_Date, posts.post_title AS Resource_Title, COUNT(*) AS downloads FROM $wpdb->posts posts
			JOIN $trackingTable tracking ON tracking.post_type_id = posts.ID
			WHERE tracking.post_type = 'resource_item'
			AND tracking.dated >= '$startDate' 
			AND  tracking.dated <= '$endDate'
			GROUP BY tracking.dated, tracking.post_type_id 
			ORDER BY tracking.dated DESC
			", ARRAY_A);				
			
			$objPHPExcel->createSheet();
			$objPHPExcel->setActiveSheetIndex($count);
			
			$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
			$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
			$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
			$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
			$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
			
			$objPHPExcel->getActiveSheet()->getStyle("A1:E1")->applyFromArray(array("font" => array("bold" => true)));
			
			$objPHPExcel->getActiveSheet()->setCellValue('A1', 'No records available');
			$objPHPExcel->getActiveSheet()->fromArray($existing_columns, NULL, 'A1');
			$objPHPExcel->getActiveSheet()->fromArray($records, NULL, 'A2');
			// Rename sheet
			$objPHPExcel->getActiveSheet()->setTitle($postType.'(s) data');
			$count++;
		}

		// Redirect output to a client’s web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="wp_data_export.xls"');
		header('Cache-Control: max-age=0');
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
	}
	
	//var_dump($_POST['pc_export_bp']);exit();


	if(isset($_POST['pc_export'])){
		exportDataFunction2();
		//exportDataToExcel();
	}
	
	if(isset($_POST['pc_export_bp'])){
		bpExportDataFunction();
	}
	
	if(isset($_POST['pc_export_reports'])){
		exportDataFunctionForResourcesReport();
	}
