<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocxFromTemplate('../../files/TemplateSimpleTable.docx');

$data = array(
            array(
                'ITEM' => 'Product A',
                'REFERENCE' => '107AW3',
            ),
            array(
                'ITEM' => 'Product B',
                'REFERENCE' => '204RS67O',
            ),
            array(
                'ITEM' => 'Product C',
                'REFERENCE' => '25GTR56',
            )
        );

$docx->replaceTableVariable($data, array('parseLineBreaks' => true));

$docx->createDocx('example_transformDocAdvPDF_5.docx');

$transform = new Phpdocx\Transform\TransformDocAdvPDF('example_transformDocAdvPDF_5.docx');
$transform->transform('example_transformDocAdvPDF_5.pdf');