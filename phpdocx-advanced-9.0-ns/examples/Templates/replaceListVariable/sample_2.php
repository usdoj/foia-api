<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/TemplateList.docx');

$link = new Phpdocx\Elements\WordFragment($docx);
$linkOptions = array('url'=> 'http://www.google.es', 
    'color' => '0000FF', 
    'underline' => 'single',
);
$link->addLink('link to Google', $linkOptions);

$image = new Phpdocx\Elements\WordFragment($docx);
$imageOptions = array(
    'src' => '../../img/image.png',
    'scaling' => 50,
    );
$image->addImage($imageOptions);

$footnote = new Phpdocx\Elements\WordFragment($docx);
$footnote->addFootnote(
    array(
        'textDocument' => 'here it is',
        'textFootnote' => 'This is the footnote text.',
    )
);

$items = array($link, $image, $footnote);

$docx->replaceListVariable('LISTVAR', $items);

$docx->createDocx('example_replaceListVariable_2');