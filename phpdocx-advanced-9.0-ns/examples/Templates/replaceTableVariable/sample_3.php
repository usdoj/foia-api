<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/TemplateComplexTable.docx');

$link1 = new Phpdocx\Elements\WordFragment($docx);
$linkOptions = array('url'=> 'http://www.google.es', 
    'color' => '0000FF', 
    'underline' => 'single',
);
$link1->addLink('link to product A', $linkOptions);

$link2 = new Phpdocx\Elements\WordFragment($docx);
$linkOptions = array('url'=> 'http://www.google.es', 
    'color' => '0000FF', 
    'underline' => 'single',
);
$link2->addLink('link to product B', $linkOptions);

$link3 = new Phpdocx\Elements\WordFragment($docx);
$linkOptions = array('url'=> 'http://www.google.es', 
    'color' => '0000FF', 
    'underline' => 'single',
);
$link3->addLink('link to product C', $linkOptions);

$image = new Phpdocx\Elements\WordFragment($docx);
$imageOptions = array(
    'src' => '../../img/image.png',
    'scaling' => 50,
    );
$image->addImage($imageOptions);

$docx->setTemplateSymbol('@');
$data = array(
	        array(
	            'ITEM' => $link1,
	            'REFERENCE' => $image,
	            'PRICE' => '5.45'
	        ),
	        array(
	            'ITEM' => $link2,
	            'REFERENCE' => $image,
	            'PRICE' => '30.12'
	        ),
	        array(
	            'ITEM' => $link3,
	            'REFERENCE' => $image,
	            'PRICE' => '7.00'
	        )
        );

$docx->replaceTableVariable($data);

$docx->createDocx('example_replaceTableVariable_3');