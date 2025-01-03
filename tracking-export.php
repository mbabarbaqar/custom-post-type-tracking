<?php
require_once(HS_ROOT_PATH . '/PhpSpreadsheet/vendor/autoload.php');
if(isset($excelData['file_name']) && isset($excelData['sheets']) && count($excelData['sheets']) > 0){
	$fileName = $excelData['file_name'] . ".xlsx";
	$upload_dir = wp_upload_dir();
	$folderPATH = $upload_dir['path'] .'/temp/reports';
	$folderURL = $upload_dir['url'] .'/temp/reports/';
	$deleteAbleFolder = $upload_dir['path'] .'/temp';
	if(!file_exists($folderPATH)){
		mkdir($folderPATH, 0755, true);	
	}

	$filePath = $folderPATH .'/'. $fileName;
	$fileUrl = $folderURL . $fileName;

	$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
	$reader = new \PhpOffice\PhpSpreadsheet\Reader\Html();
	foreach($excelData['sheets'] as $key => $value){
		$reader->setSheetIndex($key);
		$reader->loadFromString($value['content'], $spreadsheet);
		$spreadsheet->getActiveSheet()->setTitle($value['name']);
		
		//Styles
		$spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(200, 'px');
		if($key == 0){
			$spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(100, 'px');
		}elseif($key == 1){
			$spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(250, 'px');
			$spreadsheet->getActiveSheet()->getStyle('B1:ZZ1')->getAlignment()->setTextRotation(90);
		}
	}
	$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
	$writer->save($filePath);
	$session['DELETEABLE_FOLDER'] = $deleteAbleFolder;
}else{
	echo "<script>alert('Data not defined.');</script>";
}

?>