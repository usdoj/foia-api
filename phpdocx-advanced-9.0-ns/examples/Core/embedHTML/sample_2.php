<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$latinListOptions = array();
$latinListOptions[0]['type'] = 'lowerLetter';
$latinListOptions[0]['format'] = '%1.';
$latinListOptions[1]['type'] = 'lowerRoman';
$latinListOptions[1]['format'] = '%1.%2.';
$docx->createListStyle('latin', $latinListOptions);

$html = '
<ul class="latin">
    <li>First item.</li>
    <li>Second item with subitems:
        <ul>
            <li>First subitem.</li>
            <li>Second subitem.</li>
        </ul>
    </li>
    <li>Third subitem.</li>
</ul>';
$docx->embedHTML($html, array('customListStyles' => true));

$docx->createDocx('example_embedHTML_2');