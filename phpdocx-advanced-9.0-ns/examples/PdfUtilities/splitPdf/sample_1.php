<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Utilities\PdfUtilities();

$source = '../../files/document_test.pdf';
$target = 'splitPdf_.pdf';

$docx->splitPdf($source, $target, array('pages' => array(3)));