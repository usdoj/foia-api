<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Utilities\DocxUtilities();

$source = '../../files/Text.docx';
$target = 'example_watermarkText.docx';

$docx->watermarkDocx($source, $target, 'text', $options = array('text' => 'phpdocx'));