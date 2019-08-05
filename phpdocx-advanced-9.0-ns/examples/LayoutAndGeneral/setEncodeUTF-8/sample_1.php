<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$docx->setEncodeUTF8();

$docx->createDocx('example_setEncodeUTF8_1');