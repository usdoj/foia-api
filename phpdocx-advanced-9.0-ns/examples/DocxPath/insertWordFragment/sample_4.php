<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/docxpath/links.docx');

$content = new Phpdocx\Elements\WordFragment($docx, 'document');

$content->addText('New text');
$content->addImage(array('src' => '../../img/image.png' , 'scaling' => 50));

$referenceNode = array(
	'type' => 'paragraph',
    'occurrence' => 2,
    'contains' => 'HYPERLINK',
);

$docx->insertWordFragment($content, $referenceNode, 'before');

$docx->createDocx('example_insertWordFragment_4');