<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/docxpath/tables.docx');

$referenceNodeFrom = array(
    'type' => 'paragraph',
    'parent' => '/w:tc/',
    'occurrence' => 4,
);

$referenceNodeTo = array(
    'type' => 'paragraph',
    'parent' => '/w:tc/',
    'occurrence' => 8,
);

$docx->moveWordContent($referenceNodeFrom, $referenceNodeTo, 'after');

$content = new Phpdocx\Elements\WordFragment($docx, 'document');

$content->addText('New text to avoid empty cell');

$referenceNode = array(
    'parent' => '/w:tc/',
    'occurrence' => 7,
);

$docx->insertWordFragment($content, $referenceNode, 'after');

$docx->createDocx('example_moveWordContent_5');