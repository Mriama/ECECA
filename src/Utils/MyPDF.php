<?php

namespace App\Utils;

use TCPDF;

// Extend the TCPDF class to create custom Header and Footer
class MyPDF extends TCPDF {

    protected $footerValue = '';

    public function setFooterValue($val) {
        $this->footerValue = $val;
    }

    // Page footer
    public function Footer() {
        $this->SetY(-20); // Position at 20 mm from bottom
        $this->SetFont('helvetica', '', 8); // Set font
        $this->SetTextColor(128,128,128);
        $this->Cell(0, 10,  $this->footerValue, 0, false, 'R', 0, '', 0, false, 'T', 'M'); // Footer position and content
    }
}

?>