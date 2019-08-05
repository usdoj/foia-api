<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/TemplateList_header_footer.docx');

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

$text = new Phpdocx\Elements\WordFragment($docx);
$textOptions = array(
    'bold' => true,
    );
$text->addText('Lorem ipsum', $textOptions);

$itemsHeader = array($link, $image, $text);
$itemsBody = array('First item', 'Second item', 'Third item');
$itemsFooter = array($image, $text, $link);

$docx->replaceListVariable('LISTVAR_HEADER', $itemsHeader, array('target' => 'header'));
$docx->replaceListVariable('LISTVAR_BODY', $itemsBody);
$docx->replaceListVariable('LISTVAR_FOOTER', $itemsFooter, array('target' => 'footer'));

$docx->createDocx('example_replaceListVariable_3');