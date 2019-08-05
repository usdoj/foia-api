<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Utilities\PdfUtilities();

$source = '../../files/document_test.pdf';
$target = 'removePagesPdf.pdf';

$docx->removePagesPdf($source, $target, array('pages' => array(2)));