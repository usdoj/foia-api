<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docxPathUtilities = new Phpdocx\Utilities\DOCXPathUtilities();
$docxPathUtilities->splitDocx('../../files/document_sections.docx', 'splitDocx_.docx', array('keepSections' => false));