<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/docxpath/lists.docx');

$content = new Phpdocx\Elements\WordFragment($docx, 'document');

$content->addText('New text');

$referenceNode = array(
	'type' => 'list',
    'occurrence' => -1,
);

$docx->insertWordFragment($content, $referenceNode, 'after');

$docx->createDocx('example_insertWordFragment_5');