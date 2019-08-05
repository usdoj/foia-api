<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Utilities\DocxUtilities();

$source = '../../files/Watermark.docx';
$target = 'example_unwatermark.docx';

$docx->watermarkRemove($source, $target);