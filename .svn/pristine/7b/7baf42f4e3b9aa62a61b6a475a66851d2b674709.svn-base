<?php

namespace App\Utils;

// Include the main TCPDF library (search for installation path).
$tcpdf_include_dirs = array(realpath('../tcpdf.php'), realpath('../vendor/tecnick.com/tcpdf/tcpdf.php'));
foreach ($tcpdf_include_dirs as $tcpdf_include_path) {
	if (file_exists($tcpdf_include_path)) {
		require_once($tcpdf_include_path);
		break;
	}
}
use TCPDF;

// Extend the TCPDF class to create custom Header and Footer
class MyPDF extends TCPDF {
	protected $footerValue = '';
	
    public function setFooterValue($val) {
        $this->footerValue = $val;
    }
    
    //Page header
    /*public function Header() {
        $image_file = K_PATH_IMAGES.'logo_example.jpg'; // Logo
        $this->Image($image_file, 10, 10, 15, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        $this->SetFont('helvetica', 'B', 20); // Set font
        $this->Cell(0, 15, '<< TCPDF Example 003 >>', 0, false, 'C', 0, '', 0, false, 'M', 'M'); // Title
    }*/

    // Page footer
    public function Footer() {
        $this->SetY(-20); // Position at 20 mm from bottom
        $this->SetFont('helvetica', '', 8); // Set font
        $this->SetTextColor(128,128,128);
        $this->Cell(0, 10,  $this->footerValue, 0, false, 'R', 0, '', 0, false, 'T', 'M'); // Footer position and content
    }
}

?>