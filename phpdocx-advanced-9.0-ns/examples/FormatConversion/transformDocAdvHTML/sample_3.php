<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$itemList= array(
    'Line 1',
    array(
        'Line A',
        'Line B',
        'Line C',
        array(
            'Line 1.1',
            'Line 1.2',
            'Line 1.3',
        ),
        'Line D',
    ),
    'Line 2',
);

$docx->addList($itemList, 2);

$textData = new Phpdocx\Elements\WordFragment($docx);
$textData->addText('Bold text', array('bold' => true));

$htmlData = new Phpdocx\Elements\WordFragment($docx);
$html = '<i>Some HTML code</i> with a <a href="http://www.phpdocx.com">link</a>';
$htmlData->embedHTML($html);

$itemList= array(
    'In this example we use a custom list (val = 5) that comes bundled with the default PHPdocX template.',
    array(
        $textData,
        'Line B',
        'Line C'
    ),
    $htmlData,
    'Line 3',
);

$options = array(
    'font' => 'Arial',
    'italic' => true,
    'fontSize' => 14,
    'color' => 'b70000'
);

$docx->addList($itemList, 1, $options);

$docx->createDocx('example_transformDocAdvHTML_3.docx');

$transformHTMLPlugin = new Phpdocx\Transform\TransformDocAdvHTMLDefaultPlugin();

$transform = new Phpdocx\Transform\TransformDocAdvHTML('example_transformDocAdvHTML_3.docx');
$html = $transform->transform($transformHTMLPlugin);

echo $html;