<?php

namespace Phpdocx\Utilities;

/**
 * Generate xpath queries to select content in a DOCX
 * 
 * @category   Phpdocx
 * @package    DOCXPath
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2019.31.01
 * @link       https://www.phpdocx.com
 */
class DOCXPath
{        
    /**
     * Creates the required Xpath query expression
     * 
     * @access public
     * @param string $type can be * (all, default value), bookmark, break, chart, endnote (content reference), footnote (content reference), image, list, paragraph (also for links), run, section, shape, table, table-row, table-cell, tracking-insert, tracking-delete, tracking-run-style, tracking-paragraph-style, tracking-table-style, tracking-table-grid, tracking-table-row
     * @param array $filters, 
     * Keys and values:
     *     'contains' (string) 
     *     'occurrence' (int) exact occurrence or (string) range of contents or first() or last()
     *     'attributes' (array)
     *     'parent' (string) w:body immediate children (default), '/' (any children) or any other parent (w:tbl/, w:r/...)
     *     'target' (string) document, style, header, footer, footnote, endnote, comment
     * @param array $options, 
     * Values:
     *     'parse' (bool) if true parses the resulting nodes for images, charts, etcetera
     *     'location' (string) after (default), before, inlineBefore or inlineAfter (don't create a new w:p and add the WordFragment before or after the location)
     * @return DOMNode
     */
    public static function xpathContentQuery($type, $filters, $options = array())
    {
        $contentTypes = array(
            '*' => '*',
            'all' => '*',
            'bookmarkStart' => 'w:bookmarkStart',
            'bookmarkEnd' => 'w:bookmarkEnd',
            'break' => 'w:p/w:r/w:br',
            'chart' => 'w:p/w:r/w:drawing//a:graphic/a:graphicData/c:chart',
            'endnote' => 'w:p/w:r/w:endnoteReference',
            'footnote' => 'w:p/w:r/w:footnoteReference',
            'image' => 'w:p/w:r/w:drawing//a:graphic/a:graphicData/pic:pic',
            'link' => 'w:p/w:hyperlink',
            'paragraph' => 'w:p',
            'run' => '/w:r',
            'list' => 'w:p/w:pPr/w:numPr',
            'section' => 'w:p/w:pPr/w:sectPr',
            'shape' => 'w:p/w:r/w:pict',
            'style' => 'w:style',
            'table' => 'w:tbl',
            'table-row' => 'w:tr',
            'table-cell' => 'w:tc',
            'tracking-insert' => 'w:ins',
            'tracking-delete' => 'w:del',
            'tracking-run-style' => 'w:rPrChange',
            'tracking-paragraph-style' => 'w:pPrChange',
            'tracking-table-style' => 'w:tblPrChange',
            'tracking-table-grid' => 'w:tblGridChange',
            'tracking-table-row' => 'w:tcPrChange',
        );

        // set root parent
        $rootParent = 'w:body';
        if (isset($filters['target'])) {
            if ($filters['target'] == 'style') {
                $rootParent = 'w:styles';
            } elseif ($filters['target'] == 'header') {
                $rootParent = 'w:hdr';
            } elseif ($filters['target'] == 'footer') {
                $rootParent = 'w:ftr';
            } elseif ($filters['target'] == 'comments') {
                $rootParent = 'w:comments';
            } elseif ($filters['target'] == 'commentsExtended') {
                $rootParent = 'w15:commentsEx';
            } elseif ($filters['target'] == 'lastSection') {
                $rootParent = '';
                $type = 'section';
            }
        }

        if ($type == 'section' && (!isset($filters['occurrence']) || $filters['occurrence'] === 'last()' || $filters['occurrence'] === -1)) {
            // the last section doesn't use w:p/w:pPr as parent
            $contentTypes['section'] = 'w:sectPr';
        }

        if (isset($options['location']) && ($options['location'] == 'inlineBefore' || $options['location'] == 'inlineAfter')) {
            // inline location
            
            if ($type == 'bookmarkStart') {
                $nodeType = $contentTypes[$type] . '/following-sibling::w:p';
            } elseif ($type == 'bookmarkEnd') {
                $nodeType = $contentTypes[$type] . '/preceding-sibling::w:p';
            } else {
                $nodeType = $contentTypes[$type];
            }
        } else {
            $nodeType = $contentTypes[$type];
        }

        // set parent filter, direct children as default
        if (isset($filters['parent'])) {
            $nodeType = $filters['parent'] . $nodeType;
        }

        $condition = '1=1';
        
        $lastCondition = '';

        if (isset($filters['contains'])) {
            if ($type == 'bookmarkStart') {
                $contentFilter = ' following-sibling::w:p[contains(., \'' . $filters['contains']  . '\')]';
            } elseif ($type == 'bookmarkEnd') {
                $contentFilter = ' preceding-sibling::w:p[contains(., \'' . $filters['contains']  . '\')]';
            } else {
                $contentFilter =  ' contains(., \'' . $filters['contains']  . '\')';
            }

            $condition .= ' and ' . $contentFilter;
        }

        if (isset($filters['attributes']) && is_array($filters['attributes'])) {
            // the attribute value may be a string if getting the current element
            // or an array if getting a descendant of the current element
            foreach ($filters['attributes'] as $keyAttribute => $valueAttribute) {
                if (is_array($valueAttribute)) {
                    // get the descendant of the current element based on the key attribute to get the descendant
                    foreach ($valueAttribute as $keyValue => $valueValue) {
                        $condition .= ' and descendant::'.$keyAttribute.'[contains(@'.$keyValue.', "'.$valueValue.'")]';
                    }
                } else {
                    // get the current element
                    $condition .= ' and contains(@'.$keyAttribute.', "'.$valueAttribute.'")';
                }
            }
        }
        
        if ($rootParent == '') {
            $mainQuery = '//' . $nodeType . '[' . $condition . ']' . $lastCondition;
        } else {
            $mainQuery = '//' . $rootParent . '/' . $nodeType . '[' . $condition . ']' . $lastCondition;
        }

        if (isset($options['location']) && ($options['location'] == 'inlineBefore' || $options['location'] == 'inlineAfter')) {
            // inline location
            
            // get the body parent, needed to get the right child 
            if ($type == 'shape' || $type == 'list') {
                $mainQuery .= '/../..';
            } elseif ($type == 'chart' || $type == 'image') {
                $mainQuery .= '/../../../../../..';
            }

            $mainQuery .= '/w:r';
            
        } else {
            // get the body parent, needed to get the right child 
            if ($type == 'break' || $type == 'endnote' || $type == 'footnote' || $type == 'list' || $type == 'shape') {
                $mainQuery .= '/../..';
            } elseif ($type == 'chart' || $type == 'image') {
                $mainQuery .= '/../../../../../..';
            } elseif ($type == 'link') {
                $mainQuery .= '/..';
            }

            if ($type == 'section' && (isset($filters['occurrence']) && $filters['occurrence'] !== 'last()' && $filters['occurrence'] !== -1)) {
                // the last section doesn't use w:p/w:pPr as parent
                $mainQuery .= '/../..';
            }
        }
        
        // occurrence
        // if first() set value as 1
        if (isset($filters['occurrence']) && $filters['occurrence'] === 'first()') {
            $filters['occurrence'] = 1;
        }
        // if last() set value as -1
        if (isset($filters['occurrence']) && $filters['occurrence'] === 'last()') {
            $filters['occurrence'] = -1;
        }

        if (isset($filters['occurrence']) && is_int($filters['occurrence'])) {
            // position element
            $occurrence = ($filters['occurrence'] < 0) ? 'last()' : $filters['occurrence'];
            $mainQuery = '(' . $mainQuery . ')[' . $occurrence . ']';
        } elseif (isset($filters['occurrence'])) {
            // range elements
            $rangeValues = explode('..', $filters['occurrence']);

            // create the range query dynamically
            $rangeQuery = '[';

            // from
            if (isset($rangeValues[0]) && !empty($rangeValues[0])) {
                $rangeQuery .= 'position() >= ' . $rangeValues[0];
            }

            // to
            if (isset($rangeValues[1]) && !empty($rangeValues[1])) {
                if (isset($rangeValues[0]) && !empty($rangeValues[0])) {
                    $rangeQuery .= ' and ';
                }
                $rangeQuery .= 'position() <= ' . $rangeValues[1];
            }

            $rangeQuery .= ']';

            $mainQuery = '(' . $mainQuery . ')' . $rangeQuery;
        }

        $query = $mainQuery;

        return $query;
    }
    
}