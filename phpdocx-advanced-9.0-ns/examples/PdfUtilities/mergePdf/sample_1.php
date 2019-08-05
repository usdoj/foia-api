<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$merge = new Phpdocx\Utilities\MultiMerge();
$merge->mergePdf(array('../../files/Test.pdf', '../../files/Test2.pdf', '../../files/Test3.pdf'), 'example_merge_pdf.pdf');