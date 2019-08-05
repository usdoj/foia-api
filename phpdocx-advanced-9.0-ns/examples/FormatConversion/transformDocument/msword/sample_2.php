<?php

require_once '../../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$docx->transformDocument('../../../files/Test.pdf', 'transformDocument_msword_2.docx', 'msword');