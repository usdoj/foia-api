<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/TemplateCharacterStyles.docx');

$docx->parseStyles('../../files/TemplateCharacterStyles.docx');

$docx->createDocx('example_parseStyles_4');
