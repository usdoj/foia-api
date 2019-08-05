<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$docx->setBackgroundColor('FFFFCC');

$imageOptions = array(
    'src' => '../../files/image.png', 
    'dpi' => 300,  
);

$headerImage = new Phpdocx\Elements\WordFragment($docx, 'defaultHeader');
$headerImage->addImage($imageOptions);

$docx->addHeader(array('default' => $headerImage));

$imageOptions = array(
    'src' => '../../files/image.png', 
    'dpi' => 300,
    'imageAlign' => 'right',
);

$footerImage = new Phpdocx\Elements\WordFragment($docx, 'defaultFooter');
$footerImage->addImage($imageOptions);

$docx->addFooter(array('default' => $footerImage));

$style = array(
    'color' => '999999',
    'border' => 'single',
    'borderLeft' => 'double',
    'borderColor' => '990000',
    'borderRightColor' => '000099',
    'borderWidth' => 12,
    'borderTopWidth' => 24,
    'indentLeft' => 920
    );

$docx->createParagraphStyle('myStyle', $style);

$text = 'A paragraph in grey color with borders. All borders are red but the right one that is blue. ';
$text .= 'The general border style is single but the left border that is double. The top border is also thicker. ';
$text .= 'We also include big left indentation.';

$docx->addText($text, array('pStyle' => 'myStyle'));

$latinListOptions = array();
$latinListOptions[0]['type'] = 'lowerLetter';
$latinListOptions[0]['format'] = '%1.';
$latinListOptions[0]['bold'] = 'on';
$latinListOptions[0]['color'] = 'FF0000';
$latinListOptions[1]['type'] = 'lowerRoman';
$latinListOptions[1]['format'] = '%1.%2.';
$latinListOptions[1]['bold'] = 'on';
$latinListOptions[1]['color'] = '00FF00';
$latinListOptions[1]['underline'] = 'single';


$docx->createListStyle('myList', $latinListOptions);

$myList = array('item 1', array('subitem 1.1', 'subitem 1.2'), 'item 2');

$docx->addList($myList, 'myList');

$docx->createDocx('example_transformDocAdvHTML_4.docx');

$transformHTMLPlugin = new Phpdocx\Transform\TransformDocAdvHTMLDefaultPlugin();

$transform = new Phpdocx\Transform\TransformDocAdvHTML('example_transformDocAdvHTML_4.docx');
$html = $transform->transform($transformHTMLPlugin);

echo $html;