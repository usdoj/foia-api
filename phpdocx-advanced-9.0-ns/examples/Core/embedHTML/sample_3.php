<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$html = '
<style>
ol.c {list-style-type: upper-roman;}
ol.d {list-style-type: lower-alpha;}
</style>
<body>
    <h1>The list-style-type Property</h1>

    <p>Example of unordered lists:</p>

    <ul>
        <li>Item 1</li>
        <li>Item 2</li>
        <li>Item 3</li>
        <li>Other items:
            <ul>
                <li>Item 3.1</li>
                <li>Item 3.2</li>
                <li>Item 3.3</li>
            </ul>
        </li>
    </ul>

    <ul class="b">
        <li>Item 1</li>
        <li>Item 2</li>
        <li>Item 3</li>
    </ul>

    <p>Example of ordered lists:</p>

    <ol class="c">
        <li>Item 1</li>
        <li>Item 2</li>
        <li>Item 3</li>
        <li>Other items:
            <ol class="d">
                <li>Item 3.A</li>
                <li>Item 3.B</li>
                <li>Item 3.C</li>
            </ol>
        </li>
    </ol>

    <ol class="d">
        <li>Item 1</li>
        <li>Item 2</li>
        <li>Item 3</li>
    </ol>
</body>
';
$docx->embedHTML($html);

$docx->createDocx('example_embedHTML_3');