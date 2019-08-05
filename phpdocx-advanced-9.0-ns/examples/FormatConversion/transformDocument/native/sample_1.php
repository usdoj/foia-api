<?php

require_once '../../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();
$docx->transformDocument('../../../files/Test.html', 'transformDocument_native_1.docx', 'native');