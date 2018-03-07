<?php
require_once('fpdf.php');
require_once('fpdi.php');

//header('Content-type: application/pdf');

class ConcatPdf extends FPDI
{
    public $files = array();

    public function setFiles($files)
    {
        $this->files = $files;
    }

    public function concat()
    {
        foreach($this->files AS $file) {
            $pageCount = $this->setSourceFile($file);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tplIdx = $this->ImportPage($pageNo);
                $s = $this->getTemplatesize($tplIdx);
                $this->AddPage($s['w'] > $s['h'] ? 'L' : 'P', array($s['w'], $s['h']));
                $this->useTemplate($tplIdx);
            }
        }
    }
}

$names = [];

foreach($_FILES as $k => $f){
    $names[] = "pdfgenerator/$k.pdf";
    move_uploaded_file($_FILES[$k]['tmp_name'], "pdfgenerator/$k.pdf");
}

$pdf = new ConcatPdf();
$pdf->setFiles($names);
$pdf->concat();

$pdf->Output('pdfgenerator/SumarioDetalle.pdf', 'F');