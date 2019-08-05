<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$optimizedDocx = new Phpdocx\Utilities\DocxUtilities();
$optimizedDocx->optimizeDocx('../../files/document_sections.docx', 'optimized_docx_1.docx', array('compressionMethod' => 'deflate', 'extraAttributes' => true, 'imageFilesToJpegLevel' => 70));