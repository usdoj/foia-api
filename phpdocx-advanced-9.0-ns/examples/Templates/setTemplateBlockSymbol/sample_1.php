<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/TemplateBlocksCustomSymbol.docx');

$docx->setTemplateBlockSymbol('MYBLOCK');

$docx->deleteTemplateBlock('1');

$docx->createDocx('example_setTemplateBlockSymbol_1');