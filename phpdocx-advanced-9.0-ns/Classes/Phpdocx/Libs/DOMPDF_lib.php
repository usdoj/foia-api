<?php
namespace Phpdocx\Libs;
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: dompdf_config.inc.php,v $
 * Created on: 2004-08-04
 *
 * Copyright (c) 2004 - Benj Carson <benjcarson@digitaljunkies.ca>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this library in the file LICENSE.LGPL; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 * 02111-1307 USA
 *
 * Alternatively, you may distribute this software under the terms of the
 * PHP License, version 3.0 or later.  A copy of this license should have
 * been distributed with this file in the file LICENSE.PHP .  If this is not
 * the case, you can obtain a copy at http://www.php.net/license/3_0.txt.
 *
 * The latest version of DOMPDF might be available at:
 * http://www.dompdf.com/
 *
 * @link http://www.dompdf.com/
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @package dompdf
 *
 * Changes
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @version 0.5.1.htischer.20090507
 * - Allow overriding of configuration settings by calling php script.
 *   This allows replacing of dompdf by a new version in an application
 *   without any modification,
 * - Optionally separate font cache folder from font folder.
 *   This allows write protecting the entire installation
 * - Add settings to enable/disable additional debug output categories
 * - Change some defaults to more practical values
 * - Add comments about configuration parameter implications
 */

/* $Id: dompdf_config.inc.php 363 2011-02-17 21:18:25Z fabien.menager $ */

error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);

/**
 * The root of your DOMPDF installation
 */
define("PARSERHTML_DIR", str_replace(DIRECTORY_SEPARATOR, '/', dirname(__FILE__)));

/**
 * The location of the DOMPDF include directory
 */
define("PARSERHTML_INC_DIR", PARSERHTML_DIR . "/include");

/**
 * The location of the DOMPDF lib directory
 */
define("PARSERHTML_LIB_DIR", PARSERHTML_DIR . "/lib");

/**
 * Some installations don't have $_SERVER['DOCUMENT_ROOT']
 * http://fyneworks.blogspot.com/2007/08/php-documentroot-in-iis-windows-servers.html
 */
if( !isset($_SERVER['DOCUMENT_ROOT']) ) {
  $path = "";
  
  if ( isset($_SERVER['SCRIPT_FILENAME']) )
    $path = $_SERVER['SCRIPT_FILENAME'];
  elseif ( isset($_SERVER['PATH_TRANSLATED']) )
    $path = str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']);
    
  $_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr($path, 0, 0-strlen($_SERVER['PHP_SELF'])));
}

/**
 * ==== IMPORTANT ====
 *
 * dompdf's "chroot": Prevents dompdf from accessing system files or other
 * files on the webserver.  All local files opened by dompdf must be in a
 * subdirectory of this directory.  DO NOT set it to '/' since this could
 * allow an attacker to use dompdf to read any files on the server.  This
 * should be an absolute path.
 * This is only checked on command line call by dompdf.php, but not by
 * direct class use like:
 * $dompdf = new DOMPDF();  $dompdf->load_html($htmldata); $dompdf->render(); $pdfdata = $dompdf->output();
 */
def_parser("PARSERHTML_CHROOT", realpath(PARSERHTML_DIR));

/**
 * html target media view which should be rendered into pdf.
 * List of types and parsing rules for future extensions:
 * http://www.w3.org/TR/REC-html40/types.html
 *   screen, tty, tv, projection, handheld, print, braille, aural, all
 * Note: aural is deprecated in CSS 2.1 because it is replaced by speech in CSS 3.
 * Note, even though the generated pdf file is intended for print output,
 * the desired content might be different (e.g. screen or projection view of html file).
 * Therefore allow specification of content here.
 */
def_parser("PARSERHTML_DEFAULT_MEDIA_TYPE", "screen");

/**
 * Image DPI setting
 *
 * This setting determines the default DPI setting for images and fonts.  The
 * DPI may be overridden for inline images by explictly setting the
 * image's width & height style attributes (i.e. if the image's native
 * width is 600 pixels and you specify the image's width as 72 points,
 * the image will have a DPI of 600 in the rendered PDF.  The DPI of
 * background images can not be overridden and is controlled entirely
 * via this parameter.
 *
 * For the purposes of DOMPDF, pixels per inch (PPI) = dots per inch (DPI).
 * If a size in html is given as px (or without unit as image size),
 * this tells the corresponding size in pt.
 * This adjusts the relative sizes to be similar to the rendering of the
 * html page in a reference browser.
 *
 * In pdf, always 1 pt = 1/72 inch
 *
 * Rendering resolution of various browsers in px per inch:
 * Windows Firefox and Internet Explorer:
 *   SystemControl->Display properties->FontResolution: Default:96, largefonts:120, custom:?
 * Linux Firefox:
 *   about:config *resolution: Default:96
 *   (xorg screen dimension in mm and Desktop font dpi settings are ignored)
 *
 * Take care about extra font/image zoom factor of browser.
 *
 * In images, <img> size in pixel attribute, img css style, are overriding
 * the real image dimension in px for rendering.
 *
 * @var int
 */
def_parser("PARSERHTML_DPI", 96);

/**
 * Enable remote file access
 *
 * If this setting is set to true, DOMPDF will access remote sites for
 * images and CSS files as required.
 * This is required for part of test case www/test/image_variants.html through www/examples.php
 *
 * Attention!
 * This can be a security risk, in particular in combination with PARSERHTML_ENABLE_PHP and
 * allowing remote access to dompdf.php or on allowing remote html code to be passed to
 * $dompdf = new DOMPDF(); $dompdf->load_html(...);
 * This allows anonymous users to download legally doubtful internet content which on
 * tracing back appears to being downloaded by your server, or allows malicious php code
 * in remote html pages to be executed by your server with your account privileges.
 *
 * @var bool
 */
def_parser("PARSERHTML_ENABLE_REMOTE", true);

/**
 * Enable CSS float
 *
 * Allows people to disabled CSS float support
 * @var bool
 */
def_parser("PARSERHTML_ENABLE_CSS_FLOAT", false);

/**
 * PARSERHTML autoload function
 *
 * If you have an existing autoload function, add a call to this function
 * from your existing __autoload() implementation.
 *
 * @param string $class
 */
/*function PARSERHTML_autoload($class) {
  $filename = PARSERHTML_INC_DIR . "/" . mb_strtolower($class) . ".cls.php";
  
  if ( is_file($filename) )
    require_once($filename);
}

// If SPL autoload functions are available (PHP >= 5.1.2)
if ( function_exists("spl_autoload_register") ) {
  $autoload = "PARSERHTML_autoload";
  $funcs = spl_autoload_functions();
  
  // No functions currently in the stack.
  if ( $funcs === false ) {
    spl_autoload_register($autoload);
  }
  
  // If PHP >= 5.3 the $prepend argument is available
  else if ( version_compare(PHP_VERSION, '5.3', '>=') ) {
    spl_autoload_register($autoload, true, true);
  }
  
  else {
    // Unregister existing autoloaders...
    $compat = version_compare(PHP_VERSION, '5.1.2', '<=') &&
              version_compare(PHP_VERSION, '5.1.0', '>=');
              
    foreach ($funcs as $func) {
      if (is_array($func)) {
        // :TRICKY: There are some compatibility issues and some
        // places where we need to error out
        $reflector = new ReflectionMethod($func[0], $func[1]);
        if (!$reflector->isStatic()) {
          throw new \Exception('This function is not compatible with non-static object methods due to PHP Bug #44144.');
        }
        
        // Suprisingly, spl_autoload_register supports the
        // Class::staticMethod callback format, although call_user_func doesn't
        if ($compat) $func = implode('::', $func);
      }
      
      spl_autoload_unregister($func);
    }
    
    // Register the new one, thus putting it at the front of the stack...
    spl_autoload_register($autoload);
    
    // Now, go back and re-register all of our old ones.
    foreach ($funcs as $func) {
      spl_autoload_register($func);
    }
    
    // Be polite and ensure that userland autoload gets retained
    if ( function_exists("__autoload") ) {
      spl_autoload_register("__autoload");
    }
  }
}*/

//else if ( !function_exists("__autoload") ) {
  /**
   * Default __autoload() function
   *
   * @param string $class
   */
/*  function __autoload($class) {
    PARSERHTML_autoload($class);
  }
}*/

// ### End of user-configurable options ###


/**
 * Ensure that PHP is working with text internally using UTF8 character encoding.
 */
mb_internal_encoding('UTF-8');

/**
 * Global array of warnings generated by DomDocument parser and
 * stylesheet class
 *
 * @var array
 */
global $_dompdf_warnings;
$_dompdf_warnings = array();

/**
 * If true, $_dompdf_warnings is dumped on script termination when using
 * dompdf/dompdf.php or after rendering when using the DOMPDF class.
 * When using the class, setting this value to true will prevent you from
 * streaming the PDF.
 *
 * @var bool
 */
global $_dompdf_show_warnings;
$_dompdf_show_warnings = false;

/**
 * PARSERHTML - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: attribute_translator.cls.php,v $
 * Created on: 2004-09-13
 *
 * Copyright (c) 2004 - Benj Carson <benjcarson@digitaljunkies.ca>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this library in the file LICENSE.LGPL; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 * 02111-1307 USA
 *
 * Alternatively, you may distribute this software under the terms of the
 * PHP License, version 3.0 or later.  A copy of this license should have
 * been distributed with this file in the file LICENSE.PHP .  If this is not
 * the case, you can obtain a copy at http://www.php.net/license/3_0.txt.
 *
 * The latest version of DOMPDF might be available at:
 * http://www.dompdf.com/
 *
 * @link http://www.dompdf.com/
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @package parserhtml
 */

/* $Id: attribute_translator.cls.php 346 2011-01-09 13:23:22Z fabien.menager $ */

/**
 * Translates HTML 4.0 attributes into CSS rules
 *
 * @access private
 * @package parserhtml
 */
class Attribute_Translator_Parser {
  
  // Munged data originally from
  // http://www.w3.org/TR/REC-html40/index/attributes.html
  //
  // thank you var_export() :D
  static private $__ATTRIBUTE_LOOKUP = array(
    //'caption' => array ( 'align' => '', ),
    'img' => array(
      'align' => array(
        'bottom' => 'vertical-align: baseline;',
        'middle' => 'vertical-align: middle;',
        'top'    => 'vertical-align: top;',
        'left'   => 'float: left;',
        'right'  => 'float: right;'
      ),
      'border' => 'border-width: %0.2F px;',
      'height' => 'height: %s px;',
      'hspace' => 'padding-left: %1$0.2F px; padding-right: %1$0.2F px;',
      'vspace' => 'padding-top: %1$0.2F px; padding-bottom: %1$0.2F px;',
      'width'  => 'width: %s px;',
    ),
    'table' => array(
      'align' => array(
        'left'   => 'margin-left: 0; margin-right: auto;',
        'center' => 'margin-left: auto; margin-right: auto;',
        'right'  => 'margin-left: auto; margin-right: 0;'
      ),
      'bgcolor' => 'background-color: %s;',
      'border' => '!set_table_border',
      'cellpadding' => '!set_table_cellpadding',
      'cellspacing' => 'border-spacing: %0.2F; border-collapse: separate;',
      'dir' => 'direction: %s;',
      'frame' => array(
        'void'   => 'border-style: none;',
        'above'  => 'border-top-style: solid;',
        'below'  => 'border-bottom-style: solid;',
        'hsides' => 'border-left-style: solid; border-right-style: solid;',
        'vsides' => 'border-top-style: solid; border-bottom-style: solid;',
        'lhs'    => 'border-left-style: solid;',
        'rhs'    => 'border-right-style: solid;',
        'box'    => 'border-style: solid;',
        'border' => 'border-style: solid;'
      ),
      'rules' => '!set_table_rules',
      'width' => 'width: %s;',
    ),
    'hr' => array(
      'align' => '!set_hr_align', // Need to grab width to set 'left' & 'right' correctly
      'noshade' => 'border-style: solid;',
      'size' => 'border-width: %0.2F px;',
      'width' => 'width: %s;',
    ),
    'div' => array(
      'align' => 'text-align: %s;',
      'dir' => 'direction: %s;',
    ),
    'h1' => array(
      'align' => 'text-align: %s;',
      'dir' => 'direction: %s;',
    ),
    'h2' => array(
      'align' => 'text-align: %s;',
      'dir' => 'direction: %s;',
    ),
    'h3' => array(
      'align' => 'text-align: %s;',
      'dir' => 'direction: %s;',
    ),
    'h4' => array(
      'align' => 'text-align: %s;',
      'dir' => 'direction: %s;',
    ),
    'h5' => array(
      'align' => 'text-align: %s;',
      'dir' => 'direction: %s;',
    ),
    'h6' => array(
      'align' => 'text-align: %s;',
      'dir' => 'direction: %s;',
    ),
    'p' => array(
      'align' => 'text-align: %s;',
      'dir' => 'direction: %s;',
    ),
//    'col' => array(
//      'align'  => '',
//      'valign' => '',
//    ),
//    'colgroup' => array(
//      'align'  => '',
//      'valign' => '',
//    ),
    'tbody' => array(
      'align'  => '!set_table_row_align',
      'dir' => 'direction: %s;',
      'valign' => '!set_table_row_valign',
    ),
    'td' => array(
      'align'   => 'text-align: %s;',
      'bgcolor' => 'background-color: %s;',
      'dir' => 'direction: %s;',
      'height'  => 'height: %s;',
      'nowrap'  => 'white-space: nowrap;',
      'valign'  => 'vertical-align: %s;',
      'width'   => 'width: %s;',
    ),
    'tfoot' => array(
      'align'   => '!set_table_row_align',
      'dir' => 'direction: %s;',
      'valign'  => '!set_table_row_valign',
    ),
    'th' => array(
      'align'   => 'text-align: %s;',
      'bgcolor' => 'background-color: %s;',
      'dir' => 'direction: %s;',
      'height'  => 'height: %s;',
      'nowrap'  => 'white-space: nowrap;',
      'valign'  => 'vertical-align: %s;',
      'width'   => 'width: %s;',
    ),
    'thead' => array(
      'align'   => '!set_table_row_align',
      'dir' => 'direction: %s;',
      'valign'  => '!set_table_row_valign',
    ),
    'tr' => array(
      'align'   => '!set_table_row_align',
      'bgcolor' => '!set_table_row_bgcolor',
      'dir' => 'direction: %s;',
      'valign'  => '!set_table_row_valign',
    ),
    'body' => array(
      'background' => 'background-image: url(%s);',
      'bgcolor'    => 'background-color: %s;',
      'dir' => 'direction: %s;',
      'link'       => '!set_body_link',
      'text'       => 'color: %s;',
    ),
    'html' => array(
      'bgcolor'    => 'background-color: %s;',
      'dir' => 'direction: %s;',
    ),
    'br' => array(
      'clear' => 'clear: %s;',
    ),
    'basefont' => array(
      'color' => 'color: %s;',
      'face'  => 'font-family: %s;',
      'size'  => '!set_basefont_size',
    ),
    'font' => array(
      'color' => 'color: %s;',
      'dir' => 'direction: %s;',
      'face'  => 'font-family: %s;',
      'size'  => '!set_font_size',
    ),
    'dir' => array(
      'compact' => 'margin: 0.5em 0;',
    ),
    'dl' => array(
      'compact' => 'margin: 0.5em 0;',
      'dir' => 'direction: %s;',
    ),
    'menu' => array(
      'compact' => 'margin: 0.5em 0;',
      'dir' => 'direction: %s;',
    ),
    'ol' => array(
      'compact' => 'margin: 0.5em 0;',
      'dir' => 'direction: %s;',
      'start'   => 'counter-reset: -dompdf-default-counter %d;',
      'type'    => 'list-style-type: %s;',
    ),
    'ul' => array(
      'compact' => 'margin: 0.5em 0;',
      'dir' => 'direction: %s;',
      'type'    => 'list-style-type: %s;',
    ),
    'li' => array(
      'dir' => 'direction: %s;',
      'type'    => 'list-style-type: %s;',
      'value'   => 'counter-reset: -dompdf-default-counter %d;',
    ),
    'pre' => array(
      'dir' => 'direction: %s;',
      'width' => 'width: %s;',
    ),
  );

  
  static protected $_last_basefont_size = 3;
  static protected $_font_size_lookup = array(
    // For basefont support
    -3 => "4pt",
    -2 => "5pt",
    -1 => "6pt",
     0 => "7pt",
    
     1 => "8pt",
     2 => "10pt",
     3 => "12pt",
     4 => "14pt",
     5 => "18pt",
     6 => "24pt",
     7 => "34pt",
     
    // For basefont support
     8 => "48pt",
     9 => "44pt",
    10 => "52pt",
    11 => "60pt",
  );

  static function translate_attributes($frame) {
    $node = $frame->get_node();
    $tag = $node->tagName;

    if ( !isset(self::$__ATTRIBUTE_LOOKUP[$tag]) )
      return;

    $valid_attrs = self::$__ATTRIBUTE_LOOKUP[$tag];
    $attrs = $node->attributes;
    $style = rtrim($node->getAttribute("style"), "; ");
    if ( $style != "" )
      $style .= ";";

    foreach ($attrs as $attr => $attr_node ) {
      if ( !isset($valid_attrs[$attr]) )
        continue;

      $value = $attr_node->value;

      $target = $valid_attrs[$attr];
      
      // Look up $value in $target, if $target is an array:
      if ( is_array($target) ) {

        if ( isset($target[$value]) )
          $style .= " " . self::_resolve_target($node, $target[$value], $value);

      } else {
        // otherwise use target directly
        $style .= " " . self::_resolve_target($node, $target, $value);
      }
    }
    if ( !is_null($style) ) {
      $style = ltrim($style);
      $node->setAttribute("style", $style);
    }
    
  }

  static protected function _resolve_target($node, $target, $value) {
    if ( $target[0] === "!" ) {
      // Function call
      $func = "_" . mb_substr($target, 1);
      return self::$func($node, $value);
    }
    
    return $value ? sprintf($target, $value) : "";
  }

  //

  static protected function _set_table_cellpadding($node, $value) {
    $td_list = $node->getElementsByTagName("td");
    foreach ($td_list as $td) {
      $style = rtrim($td->getAttribute("style"), ";");
      $style .= "; padding: $value" . "px;";
      $style = ltrim($style, ";");
      $td->setAttribute("style", $style);
    }
    return null;
  }

  static protected function _set_table_border($node, $value) {
    $td_list = $node->getElementsByTagName("td");
    foreach ($td_list as $td) {
      $style = $td->getAttribute("style");
      if ( strpos($style, "border") !== false )
        continue;
      $style = rtrim($style, ";");
      $style .= "; border-width: " . ($value > 0 ? 1 : 0) . "pt; border-style: inset;";
      $style = ltrim($style, ";");
      $td->setAttribute("style", $style);
    }

    $th_list = $node->getElementsByTagName("th");
    foreach ($th_list as $th) {
      $style = $th->getAttribute("style");
      if ( strpos($style, "border") !== false )
        continue;
      $style = rtrim($style, ";");
      $style .= "; border-width: " . ($value > 0 ? 1 : 0) . "pt; border-style: inset;";
      $style = ltrim($style, ";");
      $th->setAttribute("style", $style);
    }

    $style = rtrim($node->getAttribute("style"),";");
    $style .= "; border-width: $value" . "px; ";
    return ltrim($style, "; ");
  }

  static protected function _set_table_cellspacing($node, $value) {
    $style = rtrim($node->getAttribute($style), ";");

    if ( $value == 0 )
      $style .= "; border-collapse: collapse;";
      
    else
      $style = "; border-collapse: separate;";
      
    return ltrim($style, ";");
  }

  static protected function _set_table_rules($node, $value) {
    $new_style = "; border-collapse: collapse;";
    switch ($value) {
    case "none":
      $new_style .= "border-style: none;";
      break;

    case "groups":
      // FIXME: unsupported
      return;

    case "rows":
      $new_style .= "border-style: solid none solid none; border-width: 1px; ";
      break;

    case "cols":
      $new_style .= "border-style: none solid none solid; border-width: 1px; ";
      break;

    case "all":
      $new_style .= "border-style: solid; border-width: 1px; ";
      break;
      
    default:
      // Invalid value
      return null;
    }

    $td_list = $node->getElementsByTagName("td");

    foreach ($td_list as $td) {
      $style = $td->getAttribute("style");
      $style .= $new_style;
      $td->setAttribute("style", $style);
    }
    return null;
  }

  static protected function _set_hr_align($node, $value) {
    $style = rtrim($node->getAttribute("style"),";");
    $width = $node->getAttribute("width");
    if ( $width == "" )
      $width = "100%";

    $remainder = 100 - (double)rtrim($width, "% ");

    switch ($value) {
    case "left":
      $style .= "; margin-right: $remainder %;";
      break;

    case "right":
      $style .= "; margin-left: $remainder %;";
      break;

    case "center":
      $style .= "; margin-left: auto; margin-right: auto;";
      break;

    default:
      return null;
    }
    return ltrim($style, "; ");
  }

  static protected function _set_table_row_align($node, $value) {

    $td_list = $node->getElementsByTagName("td");

    foreach ($td_list as $td) {
      $style = rtrim($td->getAttribute("style"), ";");
      $style .= "; text-align: $value;";
      $style = ltrim($style, "; ");
      $td->setAttribute("style", $style);
    }

    return null;
  }

  static protected function _set_table_row_valign($node, $value) {

    $td_list = $node->getElementsByTagName("td");

    foreach ($td_list as $td) {
      $style = rtrim($td->getAttribute("style"), ";");
      $style .= "; vertical-align: $value;";
      $style = ltrim($style, "; ");
      $td->setAttribute("style", $style);
    }

    return null;
  }

  static protected function _set_table_row_bgcolor($node, $value) {

    $td_list = $node->getElementsByTagName("td");

    foreach ($td_list as $td) {
      $style = rtrim($td->getAttribute("style"), ";");
      $style .= "; background-color: $value;";
      $style = ltrim($style, "; ");
      $td->setAttribute("style", $style);
    }

    return null;
  }

  static protected function _set_body_link($node, $value) {

    $a_list = $node->getElementsByTagName("a");

    foreach ($a_list as $a) {
      $style = rtrim($a->getAttribute("style"), ";");
      $style .= "; color: $value;";
      $style = ltrim($style, "; ");
      $a->setAttribute("style", $style);
    }

    return null;
  }

  static protected function _set_basefont_size($node, $value) {
    // FIXME: ? we don't actually set the font size of anything here, just
    // the base size for later modification by <font> tags.
    self::$_last_basefont_size = $value;
    return null;
  }

  static protected function _set_font_size($node, $value) {
    $style = $node->getAttribute("style");

    if ( $value[0] === "-" || $value[0] === "+" )
      $value = self::$_last_basefont_size + (int)$value;

    if ( isset(self::$_font_size_lookup[$value]) )
      $style .= "; font-size: " . self::$_font_size_lookup[$value] . ";";
    else
      $style .= "; font-size: $value;";

    return ltrim($style, "; ");

  }

}

/**
 * PARSERHTML - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: frame.cls.php,v $
 * Created on: 2004-06-02
 *
 * Copyright (c) 2004 - Benj Carson <benjcarson@digitaljunkies.ca>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this library in the file LICENSE.LGPL; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 * 02111-1307 USA
 *
 * Alternatively, you may distribute this software under the terms of the
 * PHP License, version 3.0 or later.  A copy of this license should have
 * been distributed with this file in the file LICENSE.PHP .  If this is not
 * the case, you can obtain a copy at http://www.php.net/license/3_0.txt.
 *
 * The latest version of DOMPDF might be available at:
 * http://www.dompdf.com/
 *
 * @link http://www.dompdf.com/
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @package parserhtml
 */

/* $Id: frame.cls.php 359 2011-02-05 12:15:06Z fabien.menager $ */

/**
 * The main FrameParser class
 *
 * This class represents a single HTML element.  This class stores
 * positioning information as well as containing block location and
 * dimensions. StyleParser information for the element is stored in a {@link
 * StyleParser} object.  Tree structure is maintained via the parent & children
 * links.
 *
 * @access protected
 * @package parserhtml
 */
class FrameParser {
  
  /**
   * The DOMNode object this frame represents
   *
   * @var DOMNode
   */
  protected $_node;

  /**
   * Unique identifier for this frame.  Used to reference this frame
   * via the node.
   *
   * @var string
   */
  protected $_id;

  /**
   * Unique id counter
   */
  static protected $ID_COUNTER = 0;
  
  /**
   * This frame's calculated style
   *
   * @var StyleParser
   */
  protected $_style;
  
  /**
   * This frame's parent in the document tree.
   *
   * @var FrameParser
   */
  protected $_parent;

  /**
   * This frame's first child.  All children are handled as a
   * doubly-linked list.
   *
   * @var FrameParser
   */
  protected $_first_child;

  /**
   * This frame's last child.
   *
   * @var FrameParser
   */
  protected $_last_child;

  /**
   * This frame's previous sibling in the document tree.
   *
   * @var FrameParser
   */
  protected $_prev_sibling;

  /**
   * This frame's next sibling in the document tree.
   *
   * @var FrameParser
   */
  protected $_next_sibling;
  
  /**
   * This frame's containing block (used in layout): array(x, y, w, h)
   *
   * @var array
   */
  protected $_containing_block;

  /**
   * Position on the page of the top-left corner of the margin box of
   * this frame: array(x,y)
   *
   * @var array
   */
  protected $_position;

  /**
   * Class constructor
   *
   * @param DOMNode $node the DOMNode this frame represents
   */
  function __construct(\DomNode $node) {

    $this->_node = $node;
      
    $this->_parent = null;
    $this->_first_child = null;
    $this->_last_child = null;
    $this->_prev_sibling = $this->_next_sibling = null;
    
    $this->_style = null;

    $this->set_id( self::$ID_COUNTER++ );
  }

  // Accessor methods
  /**
   * @return DOMNode
   */
  function get_node() {
return $this->_node; }
  
  /**
   * @return string
   */
  function get_id() {
return $this->_id; }
  
  /**
   * @return StyleParser
   */
  function get_style() {
return $this->_style; }

  /**
   * @return FrameParser
   */
  function get_parent() {
return $this->_parent; }

  /**
   * @return FrameParser
   */
  function get_first_child() {
return $this->_first_child; }
  
  /**
   * @return FrameParser
   */
  function get_last_child() {
return $this->_last_child; }
  
  /**
   * @return FrameParser
   */
  function get_prev_sibling() {
return $this->_prev_sibling; }
  
  /**
   * @return FrameParser
   */
  function get_next_sibling() {
return $this->_next_sibling; }
  
  /**
   * @return FrameParserList
   */
  function get_children() {
return new FrameParserList($this); }

  // Layout property accessors

  // Set methods
  function set_id($id) {

    $this->_id = $id;

    // We can only set attributes of DOMElement objects (nodeType == 1).
    // Since these are the only objects that we can assign CSS rules to,
    // this shortcoming is okay.
    if ( $this->_node->nodeType == XML_ELEMENT_NODE )
      $this->_node->setAttribute("frame_id", $id);
  }

  function set_style(StyleParser $style) {

    /*if ( is_null($this->_style) )//PHPDOCX
      $this->_original_style = clone $style;*/
    
    //$style->set_frame($this);
    $this->_style = $style;
  }

  function clean_style() {
    $this->_style = null;
  }

//

  /**
   * Inserts a new child at the beginning of the Frame
   *
   * @param $child Frame The new Frame to insert
   * @param $update_node boolean Whether or not to update the DOM
   */
  function prepend_child(FrameParser $child, $update_node = true) {
    if ( $update_node )
      $this->_node->insertBefore($child->_node, $this->_first_child ? $this->_first_child->_node : null);

    // Remove the child from its parent
    if ( $child->_parent )
      $child->_parent->remove_child($child, false);
    
    $child->_parent = $this;
    $child->_prev_sibling = null;

    // Handle the first child
    if ( !$this->_first_child ) {
      $this->_first_child = $child;
      $this->_last_child = $child;
      $child->_next_sibling = null;
    } else {
      $this->_first_child->_prev_sibling = $child;
      $child->_next_sibling = $this->_first_child;
      $this->_first_child = $child;
    }
  }

  /**
   * Inserts a new child at the end of the FrameParser
   *
   * @param $child FrameParser The new FrameParser to insert
   * @param $update_node boolean Whether or not to update the DOM
   */
  function append_child(FrameParser $child, $update_node = true) {

    if ( $update_node )
      $this->_node->appendChild($child->_node);

    // Remove the child from its parent
    if ( $child->_parent )
      $child->_parent->remove_child($child, false);

    $child->_parent = $this;
    $child->_next_sibling = null;
    
    // Handle the first child
    if ( !$this->_last_child ) {
      $this->_first_child = $child;
      $this->_last_child = $child;
      $child->_prev_sibling = null;
    } else {
      $this->_last_child->_next_sibling = $child;
      $child->_prev_sibling = $this->_last_child;
      $this->_last_child = $child;
    }
  }

  //

}

//------------------------------------------------------------------------

/**
 * Linked-list IteratorAggregate
 *
 * @access private
 * @package parserhtml
 */
class FrameParserList implements \IteratorAggregate {
  protected $_frame;

  function __construct($frame) {
$this->_frame = $frame; }
  function getIterator() {
return new FrameParserListIterator($this->_frame); }
}
  
/**
 * Linked-list Iterator
 *
 * Returns children in order and allows for list to change during iteration,
 * provided the changes occur to or after the current element
 *
 * @access private
 * @package parserhtml
 */
class FrameParserListIterator implements \Iterator {

  /**
   * @var FrameParser
   */
  protected $_parent;
  
  /**
   * @var FrameParser
   */
  protected $_cur;
  
  /**
   * @var int
   */
  protected $_num;

  function __construct(FrameParser $frame) {

    $this->_parent = $frame;
    $this->_cur = $frame->get_first_child();
    $this->_num = 0;
  }

  function rewind() {

    $this->_cur = $this->_parent->get_first_child();
    $this->_num = 0;
  }

  /**
   * @return bool
   */
  function valid() {

    return isset($this->_cur);// && ($this->_cur->get_prev_sibling() === $this->_prev);
  }
  
  function key() {
return $this->_num; }

  /**
   * @return FrameParser
   */
  function current() {
return $this->_cur; }

  /**
   * @return FrameParser
   */
  function next() {


    $ret = $this->_cur;
    if ( !$ret )
      return null;
    
    $this->_cur = $this->_cur->get_next_sibling();
    $this->_num++;
    return $ret;
  }
}

//------------------------------------------------------------------------

/**
 * Pre-order IteratorAggregate
 *
 * @access private
 * @package parserhtml
 */
class FrameParserTreeList implements \IteratorAggregate {
  /**
   * @var FrameParser
   */
  protected $_root;
  
  function __construct(FrameParser $root) {
$this->_root = $root; }
  
  /**
   * @return FrameParserTreeIterator
   */
  function getIterator() {
return new FrameParserTreeIterator($this->_root); }
}

/**
 * Pre-order Iterator
 *
 * Returns frames in preorder traversal order (parent then children)
 *
 * @access private
 * @package parserhtml
 */
class FrameParserTreeIterator implements \Iterator {
  /**
   * @var FrameParser
   */
  protected $_root;
  protected $_stack = array();
  
  /**
   * @var int
   */
  protected $_num;
  
  function __construct(FrameParser $root) {

    $this->_stack[] = $this->_root = $root;
    $this->_num = 0;
  }

  function rewind() {

    $this->_stack = array($this->_root);
    $this->_num = 0;
  }
  
  /**
   * @return bool
   */
  function valid() {
return count($this->_stack) > 0; }
  
  /**
   * @return int
   */
  function key() {
return $this->_num; }

  /**
   * @var FrameParser
   */
  function current() {
return end($this->_stack); }

  /**
   * @var FrameParser
   */
  function next() {

    $b = end($this->_stack);
    
    // Pop last element
    unset($this->_stack[ key($this->_stack) ]);
    $this->_num++;
    
    // Push all children onto the stack in reverse order
    if ( $c = $b->get_last_child() ) {
      $this->_stack[] = $c;
      while ( $c = $c->get_prev_sibling() )
        $this->_stack[] = $c;
    }
    return $b;
  }
}

/**
 * PARSERHTML - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: frame_tree.cls.php,v $
 * Created on: 2004-06-02
 *
 * Copyright (c) 2004 - Benj Carson <benjcarson@digitaljunkies.ca>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this library in the file LICENSE.LGPL; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 * 02111-1307 USA
 *
 * Alternatively, you may distribute this software under the terms of the
 * PHP License, version 3.0 or later.  A copy of this license should have
 * been distributed with this file in the file LICENSE.PHP .  If this is not
 * the case, you can obtain a copy at http://www.php.net/license/3_0.txt.
 *
 * The latest version of DOMPDF might be available at:
 * http://www.dompdf.com/
 *
 * @link http://www.dompdf.com/
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @package parserhtml
 */

/* $Id: frame_tree.cls.php 332 2010-11-27 14:06:34Z fabien.menager $ */

/**
 * Represents an entire document as a tree of frames
 *
 * The FrameParser_Tree consists of {@link FrameParser} objects each tied to specific
 * DomNode objects in a specific DomDocument.  The FrameParser_Tree has the same
 * structure as the DomDocument, but adds additional capabalities for
 * styling and layout.
 *
 * @package parserhtml
 * @access protected
 */
class FrameParser_Tree {

  /**
   * Tags to ignore while parsing the tree
   *
   * @var array
   */
  static protected $_HIDDEN_TAGS = array("area", "base", "basefont", "head", "style",
                                         "meta", "title", "colgroup",
                                         "noembed", "noscript", "param", "#comment"
                                         , 'script');

  /**
   * The main DomDocument
   *
   * @see http://ca2.php.net/manual/en/ref.dom.php
   * @var DomDocument
   */
  protected $_dom;

  /**
   * The root node of the FrameParserTree.
   *
   * @var FrameParser
   */
  protected $_root;

  /**
   * A mapping of {@link FrameParser} objects to DomNode objects
   *
   * @var array
   */
  protected $_registry;

  /**
   * Class constructor
   *
   * @param DomDocument $dom the main DomDocument object representing the current html document
   */
  function __construct(\DomDocument $dom) {
    $this->_dom = $dom;
    $this->_root = null;
    $this->_registry = array();
  }
  
  function __destruct() {
    clear_object_parser($this);
  }

  /**
   * Returns the DomDocument object representing the curent html document
   *
   * @return DomDocument
   */
  function get_dom() { return $this->_dom; }

  /**
   * Returns a specific frame given its id
   *
   * @param string $id
   * @return FrameParser
   */
  function get_frame($id) { return isset($this->_registry[$id]) ? $this->_registry[$id] : null; }

  /**
   * Returns a post-order iterator for all frames in the tree
   *
   * @return FrameParserTreeList
   */
  function get_frames() { return new FrameParserTreeList($this->_root); }

  /**
   * Builds the tree
   */
  function build_tree($filter = '*') {

    if($filter != '*' && strpos($filter, '/') === false){
        if(strpos($filter, '.') === 0) $filter = "//*[contains(@class,'".substr($filter, 1)."')]"; //css class
        elseif(strpos($filter, '#') === 0) $filter = "//*[@id='".substr($filter, 1)."']"; //dom id
        elseif(strpos($filter, '<') === 0) $filter = '//'.trim($filter, ' <>'); //dom tag //*[contains(name(),'C')]
        else $filter = "//*[contains(@class,'".$filter."')]|//*[@id='".$filter."']|//".trim($filter, ' <>'); //css|id|tag
    }

    if(strpos($filter, '/') === 0){ //xpath expression
        $xpath = new \DOMXPath($this->_dom);
        $entradas = @$xpath->query($filter);

        if($entradas !== false){
            foreach($entradas as $entrada){
                //$entrada->item($i)->nodeValue
                $entrada->setAttribute('class', ($entrada->hasAttribute('class')?$entrada->getAttribute('class').' ':'').'_phpdocx_filter_paint_');
            }

            /*$aNodos = array();
            foreach($entradas as $entrada){
                $aNodos[] = $entrada;
            }

            //remake domdocument
            $oldBody = $this->_dom->getElementsByTagName('body')->item(0);
            if($this->_dom->getElementsByTagName("html")->item(0)->removeChild($oldBody)){
                $newBody = $this->_dom->createElement('body');
                $this->_dom->getElementsByTagName("html")->item(0)->appendChild($newBody);

                foreach($aNodos as $nodo){
                    try{$this->_dom->getElementsByTagName('body')->item(0)->appendChild($nodo);}
                    catch(Exception $e){echo($filter.' -> incorrect XPath expression.');}
                }
            }*/
        }
        else $filter = '*'; //xpath expression was incorrect
    }

    if($filter == '*') @$this->_dom->getElementsByTagName("html")->item(0)->setAttribute('class', '_phpdocx_filter_paint_');

    $html = @$this->_dom->getElementsByTagName("html")->item(0); //all document, default

    if ( is_null($html) )
      $html = $this->_dom->firstChild;

    if ( is_null($html) )
      throw new \Exception("Requested HTML document contains no data.");

    $this->_root = $this->_build_tree_r($html);

  }

  /**
   * Recursively adds {@link FrameParser} objects to the tree
   *
   * Recursively build a tree of FrameParser objects based on a dom tree.
   * No layout information is calculated at this time, although the
   * tree may be adjusted (i.e. nodes and frames for generated content
   * and images may be created).
   *
   * @param DomNode $node the current DomNode being considered
   * @return FrameParser
   */
  protected function _build_tree_r(\DomNode $node) {

    $frame = new FrameParser($node);
    $id = $frame->get_id();
    $this->_registry[ $id ] = $frame;

    if ( !$node->hasChildNodes() )
      return $frame;

    // Fixes 'cannot access undefined property for object with
    // overloaded access', fix by Stefan radulian
    // <stefan.radulian@symbion.at>
    //foreach ($node->childNodes as $child) {

    // Store the children in an array so that the tree can be modified
    $children = array();
    for ($i = 0; $i < $node->childNodes->length; $i++)
      $children[] = $node->childNodes->item($i);

    foreach ($children as $child) {
      $node_name = mb_strtolower($child->nodeName);

      // Skip non-displaying nodes
      if ( in_array($node_name, self::$_HIDDEN_TAGS) ) {
        if ( $node_name !== "head" &&
             $node_name !== "style" )
          $child->parentNode->removeChild($child);
        continue;
      }

      // Skip empty text nodes
      if ( $node_name === "#text" && $child->nodeValue == "" ) {
        $child->parentNode->removeChild($child);
        continue;
      }

      // Skip empty image nodes
      if ( $node_name === "img" && $child->getAttribute("src") == "" ) {
        $child->parentNode->removeChild($child);
        continue;
      }

      $frame->append_child($this->_build_tree_r($child), false);
    }

    return $frame;
  }

  public function insert_node(\DOMNode $node, \DOMNode $new_node, $pos) {
    if ($pos === "after" || !$node->firstChild)
      $node->appendChild($new_node);
    else
      $node->insertBefore($new_node, $node->firstChild);

    $this->_build_tree_r($new_node);

    $frame_id = $new_node->getAttribute("frame_id");
    $frame = $this->get_frame($frame_id);

    $parent_id = $node->getAttribute("frame_id");
    $parent = $this->get_frame($parent_id);

    if ($pos === "before")
      $parent->prepend_child($frame, false);
    else
      $parent->append_child($frame, false);
  }
}

/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: functions.inc.php,v $
 * Created on: 2004-08-04
 *
 * Copyright (c) 2004 - Benj Carson <benjcarson@digitaljunkies.ca>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this library in the file LICENSE.LGPL; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 * 02111-1307 USA
 *
 * Alternatively, you may distribute this software under the terms of the
 * PHP License, version 3.0 or later.  A copy of this license should have
 * been distributed with this file in the file LICENSE.PHP .  If this is not
 * the case, you can obtain a copy at http://www.php.net/license/3_0.txt.
 *
 * The latest version of DOMPDF might be available at:
 * http://www.dompdf.com/
 *
 * @link http://www.dompdf.com/
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @package dompdf
 *
 * Changes
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @version 0.5.1.htischer.20090507
 * - trailing slash of base_path in build_url_parser is no longer optional when
 *   required. This allows paths not ending in a slash, e.g. on dynamically
 *   created sites with page id in the url parameters.
 * @version 20090601
 * - fix windows paths
 * @version 20090610
 * - relax windows path syntax, use uniform path delimiter. Used for background images.
 */

/* $Id: functions.inc.php 361 2011-02-16 21:03:05Z fabien.menager $ */

function def_parser($name, $value = true) {
  if (!defined($name)) {
    define($name, $value);
  }
}

if ( !function_exists("pre_r") ) {
/**
 * print_r wrapper for html/cli output
 *
 * Wraps print_r() output in < pre > tags if the current sapi is not
 * 'cli'.  Returns the output string instead of displaying it if $return is
 * true.
 *
 * @param mixed $mixed variable or expression to display
 * @param bool $return
 *
 */
function pre_r($mixed, $return = false) {
  if ($return)
    return "<pre>" . print_r($mixed, true) . "</pre>";

  if ( php_sapi_name() !== "cli")
    echo ("<pre>");
  print_r($mixed);

  if ( php_sapi_name() !== "cli")
    echo("</pre>");
  else
    echo ("\n");
  flush();

}
}

if ( !function_exists("pre_var_dump") ) {
/**
 * var_dump wrapper for html/cli output
 *
 * Wraps var_dump() output in < pre > tags if the current sapi is not
 * 'cli'.
 *
 * @param mixed $mixed variable or expression to display.
 */
function pre_var_dump($mixed) {
  if ( php_sapi_name() !== "cli")
    echo("<pre>");
    
  var_dump($mixed);
  
  if ( php_sapi_name() !== "cli")
    echo("</pre>");
}
}

if ( !function_exists("d") ) {
/**
 * generic debug function
 *
 * Takes everything and does its best to give a good debug output
 *
 * @param mixed $mixed variable or expression to display.
 */
function d($mixed) {
  if ( php_sapi_name() !== "cli")
    echo("<pre>");
    
  // line
  if (is_array($mixed) && array_key_exists("tallest_frame", $mixed)) {
    echo "<strong>LINE</strong>:\n";
    foreach($mixed as $key => $value) {
      if (is_array($value) || is_object($value)) continue;
      echo "  $key:\t".var_export($value,true)."\n";
    }
  }
  
  // other
  else {
    var_export($mixed);
  }
  
  if ( php_sapi_name() !== "cli")
    echo("</pre>");
}
}

/**
 * builds a full url given a protocol, hostname, base path and url
 *
 * @param string $protocol
 * @param string $host
 * @param string $base_path
 * @param string $url
 * @return string
 *
 * Initially the trailing slash of $base_path was optional, and conditionally appended.
 * However on dynamically created sites, where the page is given as url parameter,
 * the base path might not end with an url.
 * Therefore do not append a slash, and **require** the $base_url to ending in a slash
 * when needed.
 * Vice versa, on using the local file system path of a file, make sure that the slash
 * is appended (o.k. also for Windows)
 */
function build_url_parser($protocol, $host, $base_path, $url) {
  if ( mb_strlen($url) == 0 ) {
    //return $protocol . $host . rtrim($base_path, "/\\") . "/";
    return $protocol . $host . $base_path;
  }

  // Is the url already fully qualified or a Data URI?
  if ( mb_strpos($url, "://") !== false || mb_strpos($url, "data:") === 0 )
    return $url;

  $ret = $protocol;

  if (!in_array(mb_strtolower($protocol), array("http://", "https://", "ftp://", "ftps://"))) {
    //On Windows local file, an abs path can begin also with a '\' or a drive letter and colon
    //drive: followed by a relative path would be a drive specific default folder.
    //not known in php app code, treat as abs path
    //($url[1] !== ':' || ($url[2]!=='\\' && $url[2]!=='/'))
    if ($url[0] !== '/' && (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN' || ($url[0] !== '\\' && $url[1] !== ':'))) {
      // For rel path and local acess we ignore the host, and run the path through realpath()
      $ret .= realpath($base_path).'/';
    }
    $ret .= $url;
    $ret = preg_replace("/\?(.*)$/", "", $ret);
    return $ret;
  }

  //remote urls with backslash in html/css are not really correct, but lets be genereous
  if ( $url[0] === '/' || $url[0] === '\\' ) {
    // Absolute path
    $ret .= $host . $url;
  } else {
    // Relative path
    //$base_path = $base_path !== "" ? rtrim($base_path, "/\\") . "/" : "";
    $ret .= $host . $base_path . $url;
  }

  return $ret;

}

/**
 * parse a full url or pathname and return an array(protocol, host, path,
 * file + query + fragment)
 *
 * @param string $url
 * @return array
 */
function explode_url_parser($url) {
  $protocol = "";
  $host = "";
  $path = "";
  $file = "";

  $arr = parse_url($url);

  if ( isset($arr["scheme"]) &&
       $arr["scheme"] !== "file" &&
       mb_strlen($arr["scheme"]) > 1 ) // Exclude windows drive letters...
    {
    $protocol = $arr["scheme"] . "://";

    if ( isset($arr["user"]) ) {
      $host .= $arr["user"];

      if ( isset($arr["pass"]) )
        $host .= "@" . $arr["pass"];

      $host .= ":";
    }

    if ( isset($arr["host"]) )
      $host .= $arr["host"];

    if ( isset($arr["port"]) )
      $host .= ":" . $arr["port"];

    if ( isset($arr["path"]) && $arr["path"] !== "" ) {
      // Do we have a trailing slash?
      if ( $arr["path"][ mb_strlen($arr["path"]) - 1 ] === "/" ) {
        $path = $arr["path"];
        $file = "";
      } else {
        $path = dirname($arr["path"]) . "/";
        $file = basename($arr["path"]);
      }
    }

    if ( isset($arr["query"]) )
      $file .= "?" . $arr["query"];

    if ( isset($arr["fragment"]) )
      $file .= "#" . $arr["fragment"];

  } else {

    $i = mb_strpos($url, "file://");
    if ( $i !== false)
      $url = mb_substr($url, $i + 7);

    $protocol = ""; // "file://"; ? why doesn't this work... It's because of
                    // network filenames like //COMPU/SHARENAME

    $host = ""; // localhost, really
    $file = basename($url);

    $path = dirname($url);

    // Check that the path exists
    if ( $path !== false ) {
      $path .= '/';

    } else {
      // generate a url to access the file if no real path found.
      $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';

      $host = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : php_uname("n");

      if ( substr($arr["path"], 0, 1) === '/' ) {
        $path = dirname($arr["path"]);
      } else {
        $path = '/' . rtrim(dirname($_SERVER["SCRIPT_NAME"]), '/') . '/' . $arr["path"];
      }
    }
  }

  $ret = array($protocol, $host, $path, $file,
               "protocol" => $protocol,
               "host" => $host,
               "path" => $path,
               "file" => $file);
  return $ret;
}

/**
 * mb_string compatibility
 */
if ( !function_exists("mb_strlen") ) {
  
  define('MB_OVERLOAD_MAIL', 1);
  define('MB_OVERLOAD_STRING', 2);
  define('MB_OVERLOAD_REGEX', 4);
  define('MB_CASE_UPPER', 0);
  define('MB_CASE_LOWER', 1);
  define('MB_CASE_TITLE', 2);

  function mb_convert_encoding($data, $to_encoding, $from_encoding = 'UTF-8') {
    if (str_replace('-', '', strtolower($to_encoding)) === 'utf8') {
      return utf8_encode($data);
    } else {
      return utf8_decode($data);
    }
  }
  
  function mb_detect_encoding($data, $encoding_list = array('iso-8859-1'), $strict = false) {
    return 'iso-8859-1';
  }
  
  function mb_detect_order($encoding_list = array('iso-8859-1')) {
    return 'iso-8859-1';
  }
  
  function mb_internal_encoding($encoding = null) {
    if (isset($encoding)) {
      return true;
    } else {
      return 'iso-8859-1';
    }
  }

  function mb_strlen($str, $encoding = 'iso-8859-1') {
    switch (str_replace('-', '', strtolower($encoding))) {
      case "utf8": return strlen(utf8_encode($str));
      case "8bit": return strlen($str);
      default:     return strlen(utf8_decode($str));
    }
  }
  
  function mb_strpos($haystack, $needle, $offset = 0) {
    return strpos($haystack, $needle, $offset);
  }
  
  function mb_strrpos($haystack, $needle, $offset = 0) {
    return strrpos($haystack, $needle, $offset);
  }
  
  function mb_strtolower( $str ) {
    return strtolower($str);
  }
  
  function mb_strtoupper( $str ) {
    return strtoupper($str);
  }
  
  function mb_substr($string, $start, $length = null, $encoding = 'iso-8859-1') {
    if ( is_null($length) )
      return substr($string, $start);
    else
      return substr($string, $start, $length);
  }
  
  function mb_substr_count($haystack, $needle, $encoding = 'iso-8859-1') {
    return substr_count($haystack, $needle);
  }
  
  function mb_encode_numericentity($str, $convmap, $encoding) {
    return htmlspecialchars($str);
  }
  
  function mb_convert_case($str, $mode = MB_CASE_UPPER, $encoding = array()) {
    switch($mode) {
      case MB_CASE_UPPER: return mb_strtoupper($str);
      case MB_CASE_LOWER: return mb_strtolower($str);
      case MB_CASE_TITLE: return ucwords(mb_strtolower($str));
      default: return $str;
    }
  }
  
  function mb_list_encodings() {
    return array(
      "ISO-8859-1",
      "UTF-8",
      "8bit",
    );
  }
}

/**
 * Decoder for RLE8 compression in windows bitmaps
 * http://msdn.microsoft.com/library/default.asp?url=/library/en-us/gdi/bitmaps_6x0u.asp
 */
function rle8_decode_parser($str, $width){
  $lineWidth = $width + (3 - ($width-1) % 4);
  $out = '';
  $cnt = strlen($str);
  
  for ($i = 0; $i <$cnt; $i++) {
    $o = ord($str[$i]);
    switch ($o){
      case 0: # ESCAPE
        $i++;
        switch (ord($str[$i])){
          case 0: # NEW LINE
            $padCnt = $lineWidth - strlen($out)%$lineWidth;
            if ($padCnt<$lineWidth) $out .= str_repeat(chr(0), $padCnt); # pad line
            break;
          case 1: # END OF FILE
            $padCnt = $lineWidth - strlen($out)%$lineWidth;
            if ($padCnt<$lineWidth) $out .= str_repeat(chr(0), $padCnt); # pad line
            break 3;
          case 2: # DELTA
            $i += 2;
            break;
          default: # ABSOLUTE MODE
            $num = ord($str[$i]);
            for ($j = 0; $j < $num; $j++)
              $out .= $str[++$i];
            if ($num % 2) $i++;
        }
      break;
      default:
      $out .= str_repeat($str[++$i], $o);
    }
  }
  return $out;
}

/**
 * Decoder for RLE4 compression in windows bitmaps
 * see http://msdn.microsoft.com/library/default.asp?url=/library/en-us/gdi/bitmaps_6x0u.asp
 */
function rle4_decode_parser($str, $width) {
  $w = floor($width/2) + ($width % 2);
  $lineWidth = $w + (3 - ( ($width-1) / 2) % 4);
  $pixels = array();
  $cnt = strlen($str);
  
  for ($i = 0; $i < $cnt; $i++) {
    $o = ord($str[$i]);
    switch ($o) {
      case 0: # ESCAPE
        $i++;
        switch (ord($str[$i])){
          case 0: # NEW LINE
            while (count($pixels)%$lineWidth!=0)
              $pixels[]=0;
            break;
          case 1: # END OF FILE
            while (count($pixels)%$lineWidth!=0)
              $pixels[]=0;
            break 3;
          case 2: # DELTA
            $i += 2;
            break;
          default: # ABSOLUTE MODE
            $num = ord($str[$i]);
            for ($j = 0; $j < $num; $j++){
              if ($j%2 == 0){
                $c = ord($str[++$i]);
                $pixels[] = ($c & 240)>>4;
              } else
                $pixels[] = $c & 15;
            }
            if ($num % 2) $i++;
       }
       break;
      default:
        $c = ord($str[++$i]);
        for ($j = 0; $j < $o; $j++)
          $pixels[] = ($j%2==0 ? ($c & 240)>>4 : $c & 15);
    }
  }
  
  $out = '';
  if (count($pixels)%2) $pixels[]=0;
  $cnt = count($pixels)/2;
  
  for ($i = 0; $i < $cnt; $i++)
    $out .= chr(16*$pixels[2*$i] + $pixels[2*$i+1]);
    
  return $out;
}

if ( !function_exists("imagecreatefrombmp") ) {

/**
 * Credit goes to mgutt
 * http://www.programmierer-forum.de/function-imagecreatefrombmp-welche-variante-laeuft-t143137.htm
 * Modified by Fabien Menager to support RGB555 BMP format
 */
function imagecreatefrombmp($filename) {
  try {
  // version 1.00
  if (!($fh = fopen($filename, 'rb'))) {
    trigger_error('imagecreatefrombmp: Can not open ' . $filename, E_USER_WARNING);
    return false;
  }
  
  // read file header
  $meta = unpack('vtype/Vfilesize/Vreserved/Voffset', fread($fh, 14));
  
  // check for bitmap
  if ($meta['type'] != 19778) {
    trigger_error('imagecreatefrombmp: ' . $filename . ' is not a bitmap!', E_USER_WARNING);
    return false;
  }
  
  // read image header
  $meta += unpack('Vheadersize/Vwidth/Vheight/vplanes/vbits/Vcompression/Vimagesize/Vxres/Vyres/Vcolors/Vimportant', fread($fh, 40));
  
  // read additional bitfield header
  if ($meta['compression'] == 3) {
    $meta += unpack('VrMask/VgMask/VbMask', fread($fh, 12));
  }
  
  //pre_r($filename);pre_r($meta);
  
  // set bytes and padding
  $meta['bytes'] = $meta['bits'] / 8;
  $meta['decal'] = 4 - (4 * (($meta['width'] * $meta['bytes'] / 4)- floor($meta['width'] * $meta['bytes'] / 4)));
  if ($meta['decal'] == 4) {
    $meta['decal'] = 0;
  }
  
  // obtain imagesize
  if ($meta['imagesize'] < 1) {
    $meta['imagesize'] = $meta['filesize'] - $meta['offset'];
    // in rare cases filesize is equal to offset so we need to read physical size
    if ($meta['imagesize'] < 1) {
      $meta['imagesize'] = @filesize($filename) - $meta['offset'];
      if ($meta['imagesize'] < 1) {
        trigger_error('imagecreatefrombmp: Can not obtain filesize of ' . $filename . '!', E_USER_WARNING);
        return false;
      }
    }
  }
  
  // calculate colors
  $meta['colors'] = !$meta['colors'] ? pow(2, $meta['bits']) : $meta['colors'];
  
  // read color palette
  $palette = array();
  if ($meta['bits'] < 16) {
    $palette = unpack('l' . $meta['colors'], fread($fh, $meta['colors'] * 4));
    // in rare cases the color value is signed
    if ($palette[1] < 0) {
      foreach ($palette as $i => $color) {
        $palette[$i] = $color + 16777216;
      }
    }
  }
  
  // create gd image
  $im = imagecreatetruecolor($meta['width'], $meta['height']);
  $data = fread($fh, $meta['imagesize']);
  
  // uncompress data
  switch ($meta['compression']) {
    case 1: $data = rle8_decode_parser($data, $meta['width']); break;
    case 2: $data = rle4_decode_parser($data, $meta['width']); break;
  }

  $p = 0;
  $vide = chr(0);
  $y = $meta['height'] - 1;
  $error = 'imagecreatefrombmp: ' . $filename . ' has not enough data!';

  // loop through the image data beginning with the lower left corner
  while ($y >= 0) {
    $x = 0;
    while ($x < $meta['width']) {
      switch ($meta['bits']) {
        case 32:
        case 24:
          if (!($part = substr($data, $p, 3 /*$meta['bytes']*/))) {
            trigger_error($error, E_USER_WARNING);
            return $im;
          }
          $color = unpack('V', $part . $vide);
          break;
        case 16:
          if (!($part = substr($data, $p, 2 /*$meta['bytes']*/))) {
            trigger_error($error, E_USER_WARNING);
            return $im;
          }
          $color = unpack('v', $part);

          if (empty($meta['rMask']) || $meta['rMask'] != 0xf800)
            $color[1] = (($color[1] & 0x7c00) >> 7) * 65536 + (($color[1] & 0x03e0) >> 2) * 256 + (($color[1] & 0x001f) << 3); // 555
          else
            $color[1] = (($color[1] & 0xf800) >> 8) * 65536 + (($color[1] & 0x07e0) >> 3) * 256 + (($color[1] & 0x001f) << 3); // 565
          break;
        case 8:
          $color = unpack('n', $vide . substr($data, $p, 1));
          $color[1] = $palette[ $color[1] + 1 ];
          break;
        case 4:
          $color = unpack('n', $vide . substr($data, floor($p), 1));
          $color[1] = ($p * 2) % 2 == 0 ? $color[1] >> 4 : $color[1] & 0x0F;
          $color[1] = $palette[ $color[1] + 1 ];
          break;
        case 1:
          $color = unpack('n', $vide . substr($data, floor($p), 1));
          switch (($p * 8) % 8) {
            case 0: $color[1] =  $color[1] >> 7; break;
            case 1: $color[1] = ($color[1] & 0x40) >> 6; break;
            case 2: $color[1] = ($color[1] & 0x20) >> 5; break;
            case 3: $color[1] = ($color[1] & 0x10) >> 4; break;
            case 4: $color[1] = ($color[1] & 0x8 ) >> 3; break;
            case 5: $color[1] = ($color[1] & 0x4 ) >> 2; break;
            case 6: $color[1] = ($color[1] & 0x2 ) >> 1; break;
            case 7: $color[1] = ($color[1] & 0x1 );      break;
          }
          $color[1] = $palette[ $color[1] + 1 ];
          break;
        default:
          trigger_error('imagecreatefrombmp: ' . $filename . ' has ' . $meta['bits'] . ' bits and this is not supported!', E_USER_WARNING);
          return false;
      }
      imagesetpixel($im, $x, $y, $color[1]);
      $x++;
      $p += $meta['bytes'];
    }
    $y--;
    $p += $meta['decal'];
  }
  fclose($fh);
  return $im;
  } catch (\Exception $e) {var_dump($e);}
}
}

if ( !function_exists("date_default_timezone_get") ) {
  function date_default_timezone_get() {
    return "";
  }
  
  function date_default_timezone_set($timezone_identifier) {
    return true;
  }
}

/**
 * Affect null to the unused objects
 * @param unknown_type $object
 */
function clear_object_parser(&$object) {
  if ( is_object($object) ) {
    foreach (array_keys((array)$object) as $key) {
      clear_object_parser($property);
    }
    foreach(get_class_vars(get_class($object)) as $property => $value) {
      clear_object_parser($property);
    }
  }
  $object = null;
  unset($object);
}

function record_warnings_parser($errno, $errstr, $errfile, $errline) {
  if ( !($errno & (E_WARNING | E_NOTICE | E_USER_NOTICE | E_USER_WARNING )) ) // Not a warning or notice
    throw new \Exception($errstr . " $errno");

/*  global $_dompdf_warnings;
  global $_dompdf_show_warnings;

  if ( $_dompdf_show_warnings )
    echo $errstr . "\n";

  $_dompdf_warnings[] = $errstr;*/
}

/**
 * PARSERHTML - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: parserhtml.cls.php,v $
 * Created on: 2004-06-09
 *
 * Copyright (c) 2004 - Benj Carson <benjcarson@digitaljunkies.ca>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this library in the file LICENSE.LGPL; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 * 02111-1307 USA
 *
 * Alternatively, you may distribute this software under the terms of the
 * PHP License, version 3.0 or later.  A copy of this license should have
 * been distributed with this file in the file LICENSE.PHP .  If this is not
 * the case, you can obtain a copy at http://www.php.net/license/3_0.txt.
 *
 * The latest version of DOMPDF might be available at:
 * http://www.dompdf.com/
 *
 * @link http://www.dompdf.com/
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @package dompdf
 */

/* $Id: dompdf.cls.php 362 2011-02-16 22:17:28Z fabien.menager $ */

/**
 * PARSERHTML - PHP5 HTML to PDF renderer
 *
 * DOMPDF loads HTML and does its best to render it as a PDF.  It gets its
 * name from the new DomDocument PHP5 extension.  Source HTML is first
 * parsed by a DomDocument object.  DOMPDF takes the resulting DOM tree and
 * attaches a {@link FrameParser} object to each node.  {@link FrameParser} objects store
 * positioning and layout information and each has a reference to a {@link
 * StyleParser} object.
 *
 * StyleParser information is loaded and parsed (see {@link StyleParsersheet}) and is
 * applied to the frames in the tree by using XPath.  CSS selectors are
 * converted into XPath queries, and the computed {@link StyleParser} objects are
 * applied to the {@link FrameParser}s.
 *
 * {@link FrameParser}s are then decorated (in the design pattern sense of the
 * word) based on their CSS display property ({@link
 * http://www.w3.org/TR/CSS21/visuren.html#propdef-display}).
 * FrameParser_Decorators augment the basic {@link FrameParser} class by adding
 * additional properties and methods specific to the particular type of
 * {@link FrameParser}.  For example, in the CSS layout model, block frames
 * (display: block;) contain line boxes that are usually filled with text or
 * other inline frames.  The Block_FrameParser_Decorator therefore adds a $lines
 * property as well as methods to add {@link FrameParser}s to lines and to add
 * additional lines.  {@link FrameParser}s also are attached to specific
 * Positioner and {@link FrameParser_Reflower} objects that contain the
 * positioining and layout algorithm for a specific type of frame,
 * respectively.  This is an application of the Strategy pattern.
 *
 * Layout, or reflow, proceeds recursively (post-order) starting at the root
 * of the document.  Space constraints (containing block width & height) are
 * pushed down, and resolved positions and sizes bubble up.  Thus, every
 * {@link FrameParser} in the document tree is traversed once (except for tables
 * which use a two-pass layout algorithm).  If you are interested in the
 * details, see the reflow() method of the Reflower classes.
 *
 * Rendering is relatively straightforward once layout is complete. {@link
 * FrameParser}s are rendered using an adapted {@link Cpdf} class, originally
 * written by Wayne Munro, http://www.ros.co.nz/pdf/.  (Some performance
 * related changes have been made to the original {@link Cpdf} class, and
 * the {@link CPDF_Adapter} class provides a simple, stateless interface to
 * PDF generation.)  PDFLib support has now also been added, via the {@link
 * PDFLib_Adapter}.
 *
 *
 * @package parserhtml
 */
class PARSERHTML {

  /**
     *
     * @access public
     * @static
     * @var array
     */
  public static $noDiv = array('p', 'li', 'span', 'strong', 'em', 'u', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'caption');
  /**
   * DomDocument representing the HTML document
   *
   * @var DomDocument
   */
  protected $_xml;

  /**
   * FrameParser_Tree derived from the DOM tree
   *
   * @var FrameParser_Tree
   */
  protected $_tree;

  /**
   * StyleParsersheet for the document
   *
   * @var StyleParsersheet
   */
  protected $_css;

  /**
   * CSS raw styles of the document
   *
   * @var string
   */
  protected $cssRaw = '';

  /**
   * Actual PDF renderer
   *
   * @var Canvas
   */
  protected $_pdf;

  /**
   * Desired paper size ('letter', 'legal', 'A4', etc.)
   *
   * @var string
   */
  protected $_paper_size;

  /**
   * Paper orientation ('portrait' or 'landscape')
   *
   * @var string
   */
  protected $_paper_orientation;

  /**
   * Callbacks on new page and new element
   *
   * @var array
   */
  protected $_callbacks;

  /**
   * Experimental caching capability
   *
   * @var string
   */
  private $_cache_id;

  /**
   * Base hostname
   *
   * Used for relative paths/urls
   * @var string
   */
  protected $_base_host;

  /**
   * Absolute base path
   *
   * Used for relative paths/urls
   * @var string
   */
  protected $_base_path;

  /**
   * If "advanced" parse floating divs as tables, no floating divs as p
   *
   * @var string
   */
  private $parseDivs;

  /**
   * Protcol used to request file (file://, http://, etc)
   *
   * @var string
   */
  protected $_protocol;
  
  /**
   * Timestamp of the script start time
   *
   * @var int
   */
  private $_start_time = null;
  
  /**
   * @var string The system's locale
   */
  private $_system_locale = null;
  
  /**
   * @var bool Tells if the system's locale is the C standard one
   */
  private $_locale_standard = false;

  /**
   * Simple DOMPdf tree
   *
   * @var array
   * @access private
   * @see dompdf_treeOut::getDompdfTree()
   */
  private $aDompdfTree;

  /**
   * HTML file url
   *
   * @var string
   * @access private
   */
  private $htmlFile;

  /**
   * Resolved domains for relative files
   *
   * @var string
   * @access private
   */
  private $aDomainsResolved;

  /**
   * Disable wrap value in Tidy
   * @var bool
   */
  private $disableWrapValue;

  /**
   * Class constructor
   */
  function __construct() {
    $this->_locale_standard = sprintf('%.1f', 1.0) == '1.0';

    $this->save_locale();

    $this->_messages = array();
    $this->_xml = new \DOMDocument();
    $this->_xml->preserveWhiteSpace = true;
    $this->_tree = new FrameParser_Tree($this->_xml);
    $this->_css = new StyleParsersheet();
    $this->_pdf = null;
    $this->_paper_size = "letter";
    $this->_paper_orientation = "portrait";
    $this->_base_protocol = "";
    $this->_base_host = "";
    $this->_base_path = "";
    $this->_callbacks = array();
    $this->_cache_id = null;
    $this->parseDivs = false;
    $this->aDompdfTree = array();
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $this->htmlFile = 'http://'.$host.dirname($script); //TODO better protocol resolution
    $this->aDomainsResolved = array();

    $this->restore_locale();
  }

  /**
   * Class destructor
   */
  function __destruct() {
    clear_object_parser($this);
  }

  /**
   * Geter $cssRaw
   *
   * @return string
   */
  public function getterCssRaw() {
    return $this->cssRaw;
  }

  /**
   * Save the system's locale configuration and
   * set the right value for numeric formatting
   */
  private function save_locale() {
    if ( $this->_locale_standard ) return;

    $this->_system_locale = setlocale(LC_NUMERIC, "C");
  }

  /**
   * Restore the system's locale configuration
   */
  private function restore_locale() {
    if ( $this->_locale_standard ) return;

    setlocale(LC_NUMERIC, $this->_system_locale);
  }

  /**
   * Loads an HTML file
   *
   * Parse errors are stored in the global array _dompdf_warnings.
   *
   * @param string $file a filename or url to load
   */
  function load_html_file($file) {
    $this->save_locale();

    // Store parsing warnings as messages (this is to prevent output to the
    // browser if the html is ugly and the dom extension complains,
    // preventing the pdf from being streamed.)
    if ( !$this->_protocol && !$this->_base_host && !$this->_base_path )
      list($this->_protocol, $this->_base_host, $this->_base_path) = explode_url_parser($file);

    if ( !PARSERHTML_ENABLE_REMOTE && ($this->_protocol != "" && $this->_protocol !== "file://" ) )
      throw new \Exception("Remote file requested, but PARSERHTML_ENABLE_REMOTE is false.");

    if ($this->_protocol == "" || $this->_protocol === "file://") {

      $realfile = realpath($file);
      if ( !$file )
        throw new \Exception("File '$file' not found.");

      if ( strpos($realfile, PARSERHTML_CHROOT) !== 0 )
        throw new \Exception("Permission denied on $file.");

      // Exclude dot files (e.g. .htaccess)
      if ( substr(basename($realfile),0,1) === "." )
        throw new \Exception("Permission denied on $file.");

      $file = $realfile;
    }

    $context = stream_context_create(array('http'=>array(
      'method' => 'GET',
      'header' => "Cache-Control: no-cache"
        ."Connection: close\r\n"
        ."Referer: http://".$_SERVER['HTTP_HOST']."\r\n"
      ,
      //'user_agent' => 'PHPDocX/2.5 ('.$_SERVER['HTTP_HOST'].'; '.PHP_OS.') HTML2WordML/load_html_file',
      'timeout' => '10'
    )));
    $contents = @file_get_contents(urldecode($file), false, $context);
    if($contents === false){/*var_dump($http_response_header);*/throw new \Exception('Issue reading: '.$file);}
    if(strpos($file, 'http') === 0) $this->htmlFile = $file; //saves url for relative url when parsing
    $encoding = null;

    // See http://the-stickman.com/web-development/php/getting-http-response-headers-when-using-file_get_contents/
    if ( isset($http_response_header) ) {
      foreach($http_response_header as $_header) {
        if ( preg_match("@Content-Type:\s*[\w/]+;\s*?charset=([^\s]+)@i", $_header, $matches) ) {
          $encoding = strtoupper($matches[1]);
          break;
        }
      }
    }

    $this->restore_locale();

    $this->load_html($contents, $encoding);
  }

  /**
   * Loads an HTML string
   *
   * Parse errors are stored in the global array _dompdf_warnings.
   *
   * @param string $str HTML text to load
   */
  function load_html($str, $encoding = null) {
    $this->save_locale();

    $encoding = mb_detect_encoding($str, mb_list_encodings(), true);
    //var_dump('ini: '.$encoding);
    if ($encoding !== 'UTF-8') {
      $metatags = array(
      '@<meta\s+http-equiv="Content-Type"\s+content="(?:[\w/]+)(?:;\s*?charset=([^\s"]+))?@i',
      '@<meta\s+content="(?:[\w/]+)(?:;\s*?charset=([^\s"]+))"?\s+http-equiv="Content-Type"@i',
      );
      foreach($metatags as $metatag) {
        if (preg_match($metatag, $str, $matches)) break;
      }
      //redetecta segun metas
      if (empty($encoding)) {
        if (isset($matches[1])) {
          $encoding = strtoupper($matches[1]);
        } else {
          $encoding = 'UTF-8';
        }
      } else {
        if (isset($matches[1])) {
          $encoding = strtoupper($matches[1]);
        } else {
          $encoding = 'auto';
        }
      }

      if($encoding != 'UTF-8') $str = mb_convert_encoding($str, 'UTF-8', $encoding);

      if (isset($matches[1])) {
        $str = preg_replace('/charset=([^\s"]+)/i','charset=UTF-8', $str);
      } else {
        $str = str_replace('<head>', '<head><meta http-equiv="Content-Type" content="text/html;charset=UTF-8">', $str);
      }
    }

    // if the document contains non utf-8 with a utf-8 meta tag chars and was
    // detected as utf-8 by mbstring, problems could happen.
    // http://devzone.zend.com/article/8855
    if ( $encoding === 'UTF-8' ) {
      $str = preg_replace("/<meta([^>]+)>/", "", $str);
    }

    $str = $this->_load_html($str);

    // Store parsing warnings as messages
    //set_error_handler("record_warnings_parser");
    $str = mb_convert_encoding($str, 'HTML-ENTITIES', 'UTF-8'); //DOMDocument::loadHTML tiene problemas con cadenas en utf 8
    @$this->_xml->loadHTML($str);
    //restore_error_handler();

    $this->restore_locale();
  }

  /**
   * Normalizes an HTML string
   *
   * @param string $str HTML text to load
   */
  private function _load_html($str){
    //$str = mb_detect_encoding($str, 'UTF-8', true) == 'UTF-8' ? utf8_decode($str) : $str;

    if (class_exists('tidy')) {
      try {
        $tidy = new \tidy();
        if ($this->disableWrapValue) {
          $tidyConfiguration = array(
            'output-xhtml' => true,
            'markup' => false,
            'wrap-asp' => false,
            'wrap-jste' => false,
            'wrap-php' => false,
            'wrap-sections' => false,
          );
        } else {
          $tidyConfiguration = array(
            'output-xhtml' => true,
            'markup' => false,
            'wrap' => 0,
            'wrap-asp' => false,
            'wrap-jste' => false,
            'wrap-php' => false,
            'wrap-sections' => false,
          );
        }
        if (file_exists(dirname(__FILE__) . '/Transform/HTMLExtended.php')) {
          // support extended tags
          $htmlExtended = new Phpdocx\Transform\HTMLExtended();
          $extendedTagsInline = $htmlExtended->getTagsInline();
          $tidyConfiguration['new-inline-tags'] = join(' ', array_keys($extendedTagsInline));
          $extendedTagsBlock = $htmlExtended->getTagsBlock();
          $tidyConfiguration['new-blocklevel-tags'] = join(' ', array_keys($extendedTagsBlock));
        }
        $tidy = tidy_parse_string($str, $tidyConfiguration, 'utf8');
        //echo $tidy->errorBuffer;
        //$tidy->cleanRepair();
        $html = $tidy->html();
        $str = $html->value;
      }
      catch(\Exception $e){
        throw new \Exception('Problem with Tidy validation. Verify HTML source Tidy installation.');
      }
    } else {
        throw new \Exception('Please install and enable Tidy for PHP (http://php.net/manual/en/book.tidy.php) to transform HTML to DOCX.');
    }

    $str = preg_replace_callback(
                        '/>(\s*$\s*)</m',
                        function ($matches) {return strpos('$matches[0]', ' ') === false?'><':'> <';},
                        $str
                        );

    $str = str_replace('</body>', '<close></body>', $str);

    return($str);
  }
        
  /**
   * Builds the {@link FrameParser_Tree}, loads any CSS and applies the styles to
   * the {@link FrameParser_Tree}
   */
  protected function _process_html($filter = '*') {
    $this->save_locale();

    $this->_tree->build_tree($filter);

    $this->_css->load_css_file(StyleParsersheet::DEFAULT_STYLESHEET);

    $acceptedmedia = StyleParsersheet::$ACCEPTED_GENERIC_MEDIA_TYPES;
    if ( defined("PARSERHTML_DEFAULT_MEDIA_TYPE") ) {
      $acceptedmedia[] = PARSERHTML_DEFAULT_MEDIA_TYPE;
    } else {
      $acceptedmedia[] = StyleParsersheet::$ACCEPTED_DEFAULT_MEDIA_TYPE;
    }

    // load <link rel="STYLESHEET" ... /> tags
    $links = $this->_xml->getElementsByTagName("link");
    foreach ($links as $link) {
      if ( mb_strtolower($link->getAttribute("rel")) === "stylesheet" ||
           mb_strtolower($link->getAttribute("type")) === "text/css" ) {
        //Check if the css file is for an accepted media type
        //media not given then always valid
        $formedialist = preg_split("/[\s\n,]/", $link->getAttribute("media"),-1, PREG_SPLIT_NO_EMPTY);
        if ( count($formedialist) > 0 ) {
          $accept = false;
          foreach ( $formedialist as $type ) {
            if ( in_array(mb_strtolower(trim($type)), $acceptedmedia) ) {
              $accept = true;
              break;
            }
          }
          if (!$accept) {
            //found at least one mediatype, but none of the accepted ones
            //Skip this css file.
            continue;
          }
        }

        if (file_exists(dirname(__FILE__) . '/Transform/HTMLExtended.php')) {
          $this->cssRaw .= $link->ownerDocument->saveXML($link);
        }

        $url = $link->getAttribute("href");
        $url = build_url_parser($this->_protocol, $this->_base_host, $this->_base_path, $url);

        $this->_css->load_css_file($url);
      }

    }

    // load <style> tags
    $styles = $this->_xml->getElementsByTagName("style");
    foreach ($styles as $style) {

      // Accept all <style> tags by default (note this is contrary to W3C
      // HTML 4.0 spec:
      // http://www.w3.org/TR/REC-html40/present/styles.html#adef-media
      // which states that the default media type is 'screen'
      if ( $style->hasAttributes() &&
           ($media = $style->getAttribute("media")) &&
           !in_array($media, $acceptedmedia) )
        continue;

      $css = "";
      if ( $style->hasChildNodes() ) {

        $child = $style->firstChild;
        while ( $child ) {
          $css .= $child->nodeValue; // Handle <style><!-- blah --></style>
          $child = $child->nextSibling;
        }

      } else
        $css = $style->nodeValue;

      // Set the base path of the StyleParsersheet to that of the file being processed
      $this->_css->set_protocol($this->_protocol);
      $this->_css->set_host($this->_base_host);
      $this->_css->set_base_path($this->_base_path);

      if (file_exists(dirname(__FILE__) . '/Transform/HTMLExtended.php')) {
        $this->cssRaw .= '<style>' . $css . '</style>';
      }

      $this->_css->load_css($css);
    }

    $this->restore_locale();
  }

  /**
   * Renders the HTML to PDF
   */
  function render($filter = '*') {
    
    $this->_process_html($filter);
    
    $this->_css->apply_styles($this->_tree);

    foreach($this->_tree->get_frames() as $frame){
      $this->aDompdfTree = $this->_render($frame);
      break;
    }

    //print_r($this->getDompdfTree());
    return(true);
  }

  /**
   * Render frames recursively
   *
   * @param FrameParser $frame The frame to render
   */
  private function _render(FrameParser $frame, $filter = false){
    $aDompdfTree = array();

    $node = $frame->get_node();

    switch($node->nodeName){
      case 'meta':
      case 'script':
      case 'title':
        break;
      case 'div':
        //converts floating divs to floating tables
        /*$nodeTable = false;
        $attributes = $this->getProperties($frame->get_style());
        if(isset($attributes['float']) && ($attributes['float'] == 'right' || $attributes['float'] == 'left')){
          $nodeTable = true;
        }*/

        if($this->parseDivs == 'table'/* && $nodeTable*/){
          //TODO move float childs
          /*foreach($frame->get_children() as $child){
            $attributes = $this->getProperties($child->get_style());
            if(isset($attributes['float']) && ($attributes['float'] == 'right' || $attributes['float'] == 'left')){
              // $childNode = $child->get_node();
              // $childNode = $node->removeChild($childNode);
              // $node->appendChild($childNode);
              //$frame->append_child($child);
            }
          }*/

          $aDompdfTree['nodeName'] = 'table';
          //$aDompdfTree['nodeValue'] = $node->nodeValue;
          $aDompdfTree['attributes'] = $this->getAttributes($node);
          $aDompdfTree['properties'] = $this->getProperties($frame->get_style());

          $filter = ($filter == '*' || (isset($aDompdfTree['attributes']['class']) && in_array('_phpdocx_filter_paint_', $aDompdfTree['attributes']['class'])))?'*':false;
          $sTempFilter = ($filter != '*')?'_noPaint':'';
          $aDompdfTree['nodeName'] .= $sTempFilter;

          $aDompdfTree['children'][] = array('nodeName' => 'tr'.$sTempFilter, 'attributes' => array('border' => 0), 'properties' => array('background_color' => 'transparent'));
          $aDompdfTree['children'][0]['children'][] = array('nodeName' => 'td'.$sTempFilter, 'nodeValue' => $node->nodeValue, 'attributes' => array('colspan' => '1', 'rowspan' => '1', 'border' => 0), 'properties' => array('background_color' => 'transparent'));

          $aTempTree = array();
          foreach($frame->get_children() as $child){
            /*$attributes = $this->getProperties($child->get_style());
            //TODO extract to parent; make next sibling of this
            if(isset($attributes['float']) && ($attributes['float'] == 'right' || $attributes['float'] == 'left')){
              var_dump($attributes['float'], $child->get_node());
              $node->parentNode->appendChild($child->get_node());
              continue;
            }*/

            $aTemp = $this->_render($child, $filter);
            if(!empty($aTemp)) $aTempTree[] = $aTemp;
          }
          $aDompdfTree['children'][0]['children'][0]['children'] = empty($aTempTree)?array():$aTempTree;
          $frame->clean_style();
          return($aDompdfTree);
          break;
        }
        //elseif($this->parseDivs) $aDompdfTree['nodeName'] = 'p';/**/
      case '#text':
      case 'a':
      case 'br':
      case 'dd':
      case 'dl':
      case 'dt':
      case 'h1':
      case 'h2':
      case 'h3':
      case 'h4':
      case 'h5':
      case 'h6':
      case 'hr':
      case 'img':
      case 'img_inner':
      case 'input':
      case 'label':
      case 'li':
      case 'ol':
      case 'option':
      case 'p':
      case 'caption':
      case 'samp':
      case 'select':
      case 'span':
      case 'sub':
      case 'sup':
      case 'table':
      case 'td':
      case 'th':
      case 'tr':
      case 'u':
      case 'ul':
                                
        if($this->parseDivs == 'paragraph' && $node->nodeName == 'div'){
                                    if(!in_array($node->parentNode->nodeName, self::$noDiv)){
                                     $aDompdfTree['nodeName'] = 'p';
                                    }
                                }
        else $aDompdfTree['nodeName'] = $node->nodeName;

        $aDompdfTree['nodeValue'] = $node->nodeValue;
        $aDompdfTree['attributes'] = $this->getAttributes($node);
        $aDompdfTree['properties'] = $this->getProperties($frame->get_style());

        $filter = ($filter == '*' || (isset($aDompdfTree['attributes']['class']) && in_array('_phpdocx_filter_paint_', $aDompdfTree['attributes']['class'])))?'*':false;
        if($filter != '*') $aDompdfTree['nodeName'] .= '_noPaint';

        $aTempTree = array();
        foreach($frame->get_children() as $child){
          $aTemp = $this->_render($child, $filter);
          if(!empty($aTemp)) $aTempTree[] = $aTemp;
        }
        $aDompdfTree['children'] = empty($aTempTree)?array():$aTempTree;
        $frame->clean_style();
        return($aDompdfTree);
        break;
      case 'close':
        $aDompdfTree['nodeName'] = $node->nodeName;
        foreach($frame->get_children() as $child){
          $aTemp = $this->_render($child, false);
          if(!empty($aTemp)) $aTempTree[] = $aTemp;
        }
        $aDompdfTree['children'] = empty($aTempTree)?array():$aTempTree;
        $frame->clean_style();
        return($aDompdfTree);
        break;
      default:
        if (file_exists(dirname(__FILE__) . '/Transform/HTMLExtended.php')) {
            // support extended tags
            $htmlExtended = new HTMLExtended();
            $extendedTagsInline = $htmlExtended->getTagsInline() + $htmlExtended->getTagsBlock();
            if (array_key_exists($node->nodeName , $extendedTagsInline)) {
                // inherit styles
                $aDompdfTree['properties'] = $this->getProperties($frame->get_style());
                $aDompdfTree['inheritContents'] = $node;
            }
        }

        $aDompdfTree['nodeName'] = $node->nodeName;
        $aDompdfTree['attributes'] = $this->getAttributes($node);

        $filter = ($filter == '*' || (isset($aDompdfTree['attributes']['class']) && in_array('_phpdocx_filter_paint_', $aDompdfTree['attributes']['class'])))?'*':false;
        if($filter != '*') $aDompdfTree['nodeName'] .= '_noPaint';

        foreach($frame->get_children() as $child){
          $aTemp = $this->_render($child, $filter);
          if(!empty($aTemp)) $aTempTree[] = $aTemp;
        }
        $aDompdfTree['children'] = empty($aTempTree)?array():$aTempTree;
        $frame->clean_style();
        return($aDompdfTree);
        break;
    }

    $frame->clean_style();
    return(false);
  }

  private function resolve_uri($href){
    if(!$href) return(false); //file url

    $base_parsed = parse_url($this->htmlFile);
    $base_parsed['path'] = isset($base_parsed['path'])?$base_parsed['path']:'';

    $rel_parsed = parse_url($href);
    if(array_key_exists('scheme', $rel_parsed)) return $href; //fqdn
    elseif(strpos($href, '//') === 0) return('http:'.$href); //url like "//domain.tld/path/file" or "//domain.tld/path/file.ext"; must be "http://domain.tld/path/file" or "http://domain.tld/path/file.ext"
    elseif(strpos($href, '/') === 0) return($base_parsed['scheme'].'://'.$base_parsed['host'].$href); //url like "/path/file" or "/path/file.ext"; must be "http://domain.tld/path/file" or "http://domain.tld/path/file.ext"
    elseif(isset($this->aDomainsResolved[$this->htmlFile])) return($this->aDomainsResolved[$this->htmlFile].$href);

    //$aTypes = array('gif' => 'image/gif', 'jpeg' => 'image/jpeg', 'jpg' => 'image/jpeg', 'png' => 'image/png'); //Content-Type
    $sFileExt = substr($href, strrpos($href, '.') + 1);
    if(empty($sFileExt)/* || empty($aTypes[$sFileExt])*/) return(false); //unknown image type

    if(function_exists('stream_context_set_default')) stream_context_set_default(array('http' => array('method' => 'HEAD', 'max_redirects' => 1, 'ignore_errors' => 1)));

    $url = $base_parsed['scheme'].'://'.$base_parsed['host'].dirname($base_parsed['path']).'/'.$href; //TODO if the server redirect bad request this is not correct
    $hdrs = @get_headers($url);
    if ($hdrs) {
      //$file = strpos(implode('#', $hdrs), $aTypes[$sFileExt]);
      $file = strpos(implode('#', $hdrs), '200 OK');
      //$file = is_array($hdrs)?preg_match('/^HTTP\\/\\d+\\.\\d+\\s+2\\d\\d\\s+.*$/',$hdrs[0]):false;
      if($file) $this->aDomainsResolved[$this->htmlFile] = $base_parsed['scheme'].'://'.$base_parsed['host'].dirname($base_parsed['path']).'/';
      else{
        $url = $base_parsed['scheme'].'://'.$base_parsed['host'].$base_parsed['path'].'/'.$href;
        $hdrs = @get_headers($url);
        //$file = strpos(implode('#', $hdrs), $aTypes[$sFileExt]);
        $file = strpos(implode('#', $hdrs), '200 OK');
        //$file = is_array($hdrs)?preg_match('/^HTTP\\/\\d+\\.\\d+\\s+2\\d\\d\\s+.*$/',$hdrs[0]):false;
        if($file) $this->aDomainsResolved[$this->htmlFile] = $base_parsed['scheme'].'://'.$base_parsed['host'].$base_parsed['path'].'/';
      }
      //returns bad url if not found (ms word can show a placeholder)

      if(function_exists('stream_context_set_default')) stream_context_set_default(array('http' => array('method' => 'GET', 'max_redirects' => 20, 'ignore_errors' => 0)));
      return($url);
    } else {
      return($href);
    }
  }

  private function getAttributes($node){
    $aRet = array();
    $temp = false;

    switch ($node->nodeName) {
      case '#text':
        return($aRet);
        break;
      case 'a':
        $aRet['dir'] = (string)$node->getAttribute('dir');
        $aRet['href'] = (string)$node->getAttribute('href');
        break;
      case 'form':
        $action = $node->getAttribute('action');
        $aRet['action'] = empty($action)?'#':$action;
        $method = $node->getAttribute('method');
        $aRet['method'] = empty($method)?'post':$method;
        $aRet['id'] = (string)$node->getAttribute('id');
        break;
      case 'img':
        $aRet['src'] = (string)$node->getAttribute('src');
                                if($this->baseURL == ''){
                                    $aRet['src'] = $this->resolve_uri($aRet['src']); //try to resolve relative url
                                }
        $aRet['width'] = (string)$node->getAttribute('width');
        $aRet['height'] = (string)$node->getAttribute('height');
        break;
      case 'img_inner':
        $aRet['src'] = (string)$node->getAttribute('src');
        break;
      case 'input':
        $aRet['dir'] = (string)$node->getAttribute('dir');  
        $aRet['type'] = (string)$node->getAttribute('type');
        $aRet['name'] = (string)$node->getAttribute('name');
        $aRet['id'] = (string)$node->getAttribute('id');
        $aRet['value'] = (string)$node->getAttribute('value');
        $aRet['size'] = (string)$node->getAttribute('size');
        if($node->hasAttribute('checked')) $aRet['checked'] = true;
        else $aRet['checked'] = false;
        break;
      case 'ol':
        $aRet['start'] = (string)$node->getAttribute('start');
        break;
      case 'option':
        if($node->hasAttribute('selected')) $aRet['selected'] = true;
        else $aRet['selected'] = false;
        $aRet['dir'] = (string)$node->getAttribute('dir');
        break;
      case 'p':
        $aRet['dir'] = (string)$node->getAttribute('dir');
        break;
      case 'table':
        $aRet['border'] = (string)$node->getAttribute('border');
        $aRet['align'] = (string)$node->getAttribute('align');
        $aRet['width'] = (string)$node->getAttribute('width');
        $aRet['height'] = (string)$node->getAttribute('height');
        $aRet['dir'] = (string)$node->getAttribute('dir');
        break;
      case 'td':
      case 'th':
        $aRet['dir'] = (string)$node->getAttribute('dir');
        $colspan = (int)$node->getAttribute('colspan');
        $aRet['colspan'] = empty($colspan)?1:$colspan;
        $rowspan = (int)$node->getAttribute('rowspan');
        $aRet['rowspan'] = empty($rowspan)?1:$rowspan;
        break;
      default:
        foreach ($node->attributes as $attr) {
          if (strstr($attr->nodeName, 'data-')) {
            $aRet[$attr->nodeName] = $attr->nodeValue;
          }
        }
    }

    $temp = $node->getAttribute('id');
    if($temp){$aRet['id'] = $temp;$temp = false;}

    $temp = $node->getAttribute('name');
    if($temp){$aRet['name'] = $temp;$temp = false;}

    $temp = $node->getAttribute('title');
    if($temp){$aRet['title'] = $temp;$temp = false;}

    $temp = $node->getAttribute('alt');
    if($temp){$aRet['alt'] = $temp;$temp = false;}

    $temp = $node->getAttribute('class');
    if($temp){
      $aRet['class'] = explode(' ', $temp);
      $temp = false;
    }

    return($aRet);
  }

  private function getProperties($properties){
    $aRet = array();

    //valid styles
    /*$aStyleParsers = array('azimuth', 'background_attachment', 'background_color', 'background_image', 'background_position', 'background_repeat',
    'background', 'border_collapse', 'border_color', 'border_spacing', 'border_style', 'border_top', 'border_right', 'border_bottom', 'border_left',
    'border_top_color', 'border_right_color', 'border_bottom_color', 'border_left_color', 'border_top_style', 'border_right_style', 'border_bottom_style',
    'border_left_style', 'border_top_width', 'border_right_width', 'border_bottom_width', 'border_left_width', 'border_width', 'border', 'bottom',
    'caption_side', 'clear', 'clip', 'color', 'content', 'counter_increment', 'counter_reset', 'cue_after', 'cue_before', 'cue', 'cursor', 'direction',
    'display', 'elevation', 'empty_cells', 'float', 'font_family', 'font_size', 'font_style', 'font_variant', 'font_weight', 'font', 'height', 'left',
    'letter_spacing', 'line_height', 'list_style_image', 'list_style_position', 'list_style_type', 'list_style', 'margin_right', 'margin_left', 'margin_top',
    'margin_bottom', 'margin', 'max_height', 'max_width', 'min_height', 'min_width', 'orphans', 'outline_color', 'outline_style', 'outline_width', 'outline',
    'overflow', 'padding_top', 'padding_right', 'padding_bottom', 'padding_left', 'padding', 'page_break_after', 'page_break_before', 'page_break_inside',
    'pause_after', 'pause_before', 'pause', 'pitch_range', 'pitch', 'play_during', 'position', 'quotes', 'richness', 'right', 'speak_header', 'speak_numeral',
    'speak_punctuation', 'speak', 'speech_rate', 'stress', 'table_layout', 'text_align', 'text_decoration', 'text_indent', 'text_transform', 'top',
    'unicode_bidi', 'vertical_align', 'visibility', 'voice_family', 'volume', 'white_space', 'widows', 'width', 'word_spacing', 'z_index');/**/
    //valid styles
    $aStyleParsers = array_keys(StyleParser::$aDefaultProperties);

    foreach($aStyleParsers as $style){
      if($style == 'font_family') $sTemp = $properties->get_props($style);
      else{
        try{$sTemp = $properties->getpropstyle($style);}
        catch(\Exception $e){$sTemp = '';}
      }
      if($sTemp != ''){
        $aRet[$style] = $sTemp;
      }
    }

    static $properties_pool = array();
    $hash = md5(serialize($aRet));
    if (!isset($properties_pool[$hash])) {
      $properties_pool[$hash] = $aRet;
    }

    return $properties_pool[$hash];
  }

  public function getDompdfTree($html = '', $isfile = false, $filter = '*', $parseDivs = false, $baseURL = '', $disableWrapValue = false){
    $this->parseDivs = ($parseDivs == 'table' || $parseDivs == 'paragraph')?$parseDivs:false;
    $this->baseURL = $baseURL;
    $this->disableWrapValue = $disableWrapValue;
    if(!empty($html)){
      //if($xpath !== false && preg_match('/^[:_A-Za-z][-.:_A-Za-z0-9]*/', $xpath)){
      //  $xpath = "//*[@$xpath]";
      //}
      if($isfile) {
        $this->load_html_file($html);
      } else {
        $this->load_html($html);
      }

      $this->render($filter);
    } elseif (empty($this->aDompdfTree)) {
      $this->render($filter);
    }

    return($this->aDompdfTree);
  }

}

/**
 * PARSERHTML - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: style.cls.php,v $
 * Created on: 2004-06-01
 *
 * Copyright (c) 2004 - Benj Carson <benjcarson@digitaljunkies.ca>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this library in the file LICENSE.LGPL; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 * 02111-1307 USA
 *
 * Alternatively, you may distribute this software under the terms of the
 * PHP License, version 3.0 or later.  A copy of this license should have
 * been distributed with this file in the file LICENSE.PHP .  If this is not
 * the case, you can obtain a copy at http://www.php.net/license/3_0.txt.
 *
 * The latest version of DOMPDF might be available at:
 * http://www.dompdf.com/
 *
 * @link http://www.dompdf.com/
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @package parserhtml
 *
 * Changes
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @version 0.5.1.htischer.20090507
 * - Fix px to pt conversion according to DOMPDF_DPI
 * - Recognize css styles with !important attribute, and store !important attribute within style
 * - Propagate !important by inherit and sequences of styles with merge.
 * - Add missing style property cache flushes for consistent rendering, e.g. on explicte assignments
 * - Add important set/get for access from outside of class
 * - Fix font_family search path with multiple fonts list in css attribute:
 *   On missing font, do not immediately fall back to default font,
 *   but try subsequent fonts in search chain. Only when none found, explicitely
 *   refer to default font.
 * - Allow read of background individual properties
 * - Add support for individual styles background-position, background-attachment, background-repeat
 * - Complete style components of list-style
 * - Add support for combined styles in addition to individual styles
 *   like {border: red 1px solid;}, { border-width: 1px;}, { border-right-color: red; } ...
 *   for font, background
 * - Propagate attributes including !important from combined style to individual component
 *   for border, background, padding, margin, font, list_style
 * - Refactor common code of border, background, padding, margin, font, list_style
 * - Refactor common code of list-style-image and background-image
 * - special treatment of css images "none" instead of url(...), otherwise would prepend string "none" with path name
 * - Added comments
 * - Added debug output
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @version dompdf_trunk_with_helmut_mods.20090524
 * - Allow superflous white space and string delimiter in font search path.
 * - Restore lost change of default font of above
 * @version 20090610
 * - Allow absolute path from web server root as html image reference
 * - More accurate handling of css property cache consistency
 */

/* $Id: style.cls.php 355 2011-01-27 07:44:54Z fabien.menager $ */

/**
 * Represents CSS properties.
 *
 * The StyleParser class is responsible for handling and storing CSS properties.
 * It includes methods to resolve colours and lengths, as well as getters &
 * setters for many CSS properites.
 *
 * Actual CSS parsing is performed in the {@link StyleParsersheet} class.
 *
 * @package parserhtml
 */
class StyleParser {

  // All CSS 2.1 properties, and their default values
  static $aDefaultProperties = array(
    "background_color" => "transparent",
    "background" => "",
    "border_collapse" => "separate",
    "border_color" => "",
    "border_spacing" => "0",
    "border_style" => "",
    "border_top" => "",
    "border_right" => "",
    "border_bottom" => "",
    "border_left" => "",
    "border_top_color" => "",
    "border_right_color" => "",
    "border_bottom_color" => "",
    "border_left_color" => "",
    "border_top_style" => "none",
    "border_right_style" => "none",
    "border_bottom_style" => "none",
    "border_left_style" => "none",
    "border_top_width" => "medium",
    "border_right_width" => "medium",
    "border_bottom_width" => "medium",
    "border_left_width" => "medium",
    "border_width" => "medium",
    "border" => "",
    "color" => "#000000",
    "direction" => "ltr",
    "display" => "inline",
    "float" => "none",
    "font_family" => "serif",
    "font_size" => "medium",
    "font_style" => "normal",
    "font_variant" => "normal",
    "font_weight" => "normal",
    "font" => "",
    "height" => "auto",
    "line_height" => "normal",
    "list_style_type" => "disc",
    "list_style" => "",
    "margin_right" => "0",
    "margin_left" => "0",
    "margin_top" => "0",
    "margin_bottom" => "0",
    "margin" => "",
    "padding_top" => "0",
    "padding_right" => "0",
    "padding_bottom" => "0",
    "padding_left" => "0",
    "padding" => "",
    "page_break_after" => "auto",
    "page_break_before" => "auto",
    "page_break_inside" => "auto",
    "table_layout" => "auto",
    "text_align" => "left",
    "text_decoration" => "none",
    "text_indent" => "0",
    "text_transform" => "none",
    "vertical_align" => "baseline",
    "width" => "auto",
  );

  /**
   * Default font size, in points.
   *
   * @var float
   */
  private $default_font_size = 12;

  /**
   * Default line height, as a fraction of the font size.
   *
   * @var float
   */
  private $default_line_height = 1.2;


  /**
   * List of valid border styles.  Should also really be a constant.
   *
   * @var array
   */
  

  /**
   * Default style values.
   *
   * @link http://www.w3.org/TR/CSS21/propidx.html
   *
   * @var array
   */
  static protected $_defaults = null;

  /**
   * List of inherited properties
   *
   * @link http://www.w3.org/TR/CSS21/propidx.html
   *
   * @var array
   */
  static protected $_inherited = null;

  /**
   * The stylesheet this style belongs to
   *
   * @see Stylesheet
   * @var Stylesheet
   */
  protected $_stylesheet; // stylesheet this style is attached to

  /**
   * Main array of all CSS properties & values
   *
   * @var array
   */
  protected $_props;

  /* var instead of protected would allow access outside of class */
  protected $_important_props;

  /**
   * Cached property values
   *
   * @var array
   */
  protected $_prop_cache;

  /**
   * Font size of parent element in document tree.  Used for relative font
   * size resolution.
   *
   * @var float
   */
  protected $_parent_font_size; // Font size of parent element

  // private members
  /**
   * True once the font size is resolved absolutely
   *
   * @var bool
   */
  private $__font_size_calculated; // Cache flag

  /**
   * Class constructor
   *
   * @param StyleParsersheet $stylesheet the stylesheet this StyleParser is associated with.
   */
  function __construct(StyleParsersheet $stylesheet) {

    $this->_props = array();
    $this->_important_props = array();
    $this->_stylesheet = $stylesheet;
    $this->_parent_font_size = null;
    $this->__font_size_calculated = false;

    if ( !isset(self::$_defaults) ) {

      // Shorthand
      $d =& self::$_defaults;

      foreach(self::$aDefaultProperties as $key => $value){
        $d[$key] = $value;
      };

      // Properties that inherit by default
      self::$_inherited = array(//"azimuth",
                                 "background_color",
                                 //"border_collapse",
                                 //"border_spacing",
                                 //"caption_side",
                                 "color",
                                 //"cursor",
                                 "direction",
                                 //"elevation",
                                 //"empty_cells",
                                 "font_family",
                                 "font_size",
                                 "font_style",
                                 "font_variant",
                                 "font_weight",
                                 "font",
                                 //"letter_spacing",
                                 "line_height",
                                 "list_style_image",
                                 "list_style_position",
                                 "list_style_type",
                                 "list_style",
                                 //"orphans",
                                 "page_break_inside",
                                 //"pitch_range",
                                 //"pitch",
                                 //"quotes",
                                 //"richness",
                                 //"speak_header",
                                 //"speak_numeral",
                                 //"speak_punctuation",
                                 //"speak",
                                 //"speech_rate",
                                 //"stress",
                                 "text_align",
                                 "text_indent",
                                 "text_transform",
                                 "text_decoration",
                                 "vertical_align", //TODO: check if this is a good general option
                                 //"visibility",
                                 //"voice_family",
                                 //"volume",
                                 //"white_space",
                                 //"widows",
                                 //"word_spacing"
                                 );
    }

  }

  /**
   * Converts any CSS length value into an absolute length in points.
   *
   * length_in_pt() takes a single length (e.g. '1em') or an array of
   * lengths and returns an absolute length.  If an array is passed, then
   * the return value is the sum of all elements.
   *
   * If a reference size is not provided, the default font size is used
   * ({@link $this->default_font_size}).
   *
   * @param float|array $length   the length or array of lengths to resolve
   * @param float       $ref_size  an absolute reference size to resolve percentage lengths
   * @return float
   */
  function length_in_pt($length, $ref_size = null) {

    if ( !is_array($length) )
      $length = array($length);

    if ( !isset($ref_size) )
      $ref_size = $this->default_font_size;

    $ret = 0;
    foreach ($length as $l) {

      if ( $l === "auto" )
        return "auto";
      
      if ( $l === "none" )
        return "none";

      // Assume numeric values are already in points
      if ( is_numeric($l) ) {
        $ret += $l;
        continue;
      }

      if ( $l === "normal" ) {
        $ret += $ref_size;
        continue;
      }

      // Border lengths
      if ( $l === "thin" ) {
        $ret += 0.5;
        continue;
      }

      if ( $l === "medium" ) {
        $ret += 1.5;
        continue;
      }

      if ( $l === "thick" ) {
        $ret += 2.5;
        continue;
      }

      if ( ($i = mb_strpos($l, "px"))  !== false ) {
        $ret += ( mb_substr($l, 0, $i)  * 72 ) / PARSERHTML_DPI;
        continue;
      }

      if ( ($i = mb_strpos($l, "pt"))  !== false ) {
        $ret += mb_substr($l, 0, $i);
        continue;
      }

      if ( ($i = mb_strpos($l, "em"))  !== false ) {
        $ret += mb_substr($l, 0, $i) * $this->__get("font_size");
        continue;
      }

      if ( ($i = mb_strpos($l, "%"))  !== false ) {
        $ret += mb_substr($l, 0, $i)/100 * $ref_size;
        continue;
      }

      if ( ($i = mb_strpos($l, "cm")) !== false ) {
        $ret += mb_substr($l, 0, $i) * 72 / 2.54;
        continue;
      }

      if ( ($i = mb_strpos($l, "mm")) !== false ) {
        $ret += mb_substr($l, 0, $i) * 72 / 25.4;
        continue;
      }

      // FIXME: em:ex ratio?
      if ( ($i = mb_strpos($l, "ex"))  !== false ) {
        $ret += mb_substr($l, 0, $i) * $this->__get("font_size");
        continue;
      }
      
      if ( ($i = mb_strpos($l, "in")) !== false ) {
        $ret += mb_substr($l, 0, $i) * 72;
        continue;
      }

      if ( ($i = mb_strpos($l, "pc")) !== false ) {
        $ret += mb_substr($l, 0, $i) / 12;
        continue;
      }

      // Bogus value
      $ret += $ref_size;
    }

    return $ret;
  }

  
  /**
   * Set inherited properties in this style using values in $parent
   *
   * @param StyleParser $parent
   */
  function inherit(StyleParser $parent) {


    // Set parent font size
    $this->_parent_font_size = $parent->get_font_size();

    foreach (self::$_inherited as $prop) {
      //inherit the !important property also.
      //if local property is also !important, don't inherit.
      if ( isset($parent->_props[$prop]) &&
           ( !isset($this->_props[$prop]) ||
             ( isset($parent->_important_props[$prop]) && !isset($this->_important_props[$prop]) )
           )
         ) {
        if ( isset($parent->_important_props[$prop]) ) {
          $this->_important_props[$prop] = true;
        }
        //see __set and __get, on all assignments clear cache!

        $this->_props[$prop] = $parent->_props[$prop];
      }
    }

    foreach (array_keys($this->_props) as $prop) {
      if ( $this->_props[$prop] === "inherit" ) {
        if ( isset($parent->_important_props[$prop]) ) {
          $this->_important_props[$prop] = true;
        }
        //do not assign direct, but
        //implicite assignment through __set, redirect to specialized, get value with __get
        //This is for computing defaults if the parent setting is also missing.
        //Therefore do not directly assign the value without __set
        //set _important_props before that to be able to propagate.
        //see __set and __get, on all assignments clear cache!
        //$this->_prop_cache[$prop] = null;
        //$this->_props[$prop] = $parent->_props[$prop];
        //props_set for more obvious explicite assignment not implemented, because
        //too many implicite uses.
        // $this->props_set($prop, $parent->$prop);
        $this->$prop = $parent->$prop;
      }
    }

    return $this;
  }

  /**
   * Override properties in this style with those in $style
   *
   * @param StyleParser $style
   */
  function merge(StyleParser $style) {

    //treat the !important attribute
    //if old rule has !important attribute, override with new rule only if
    //the new rule is also !important
    foreach($style->_props as $prop => $val ) {
      if (isset($style->_important_props[$prop])) {
        $this->_important_props[$prop] = true;
        //see __set and __get, on all assignments clear cache!
        $this->_props[$prop] = $val;
      } else if ( !isset($this->_important_props[$prop]) ) {
        //see __set and __get, on all assignments clear cache!
        $this->_prop_cache[$prop] = null;
        $this->_props[$prop] = $val;
      }
    }

    if ( isset($style->_props["font_size"]) )
      $this->__font_size_calculated = false;
  }

  /**
   * Returns an array(r, g, b, "r"=> r, "g"=>g, "b"=>b, "hex"=>"#rrggbb")
   * based on the provided CSS colour value.
   *
   * function code from css_color.cls.php
   *
   * @param string $colour
   * @return array
   */
  function munge_colour($colour) {
    static $cssColorNames = array("aliceblue" => "F0F8FF", "antiquewhite" => "FAEBD7", "aqua" => "00FFFF","aquamarine" => "7FFFD4", "azure" => "F0FFFF",
    "beige" => "F5F5DC", "bisque" => "FFE4C4", "black" => "000000", "blanchedalmond" => "FFEBCD", "blue" => "0000FF", "blueviolet" => "8A2BE2", "brown" => "A52A2A",
    "burlywood" => "DEB887", "cadetblue" => "5F9EA0", "chartreuse" => "7FFF00", "chocolate" => "D2691E", "coral" => "FF7F50", "cornflowerblue" => "6495ED",
    "cornsilk" => "FFF8DC", "crimson" => "DC143C", "cyan" => "00FFFF", "darkblue" => "00008B", "darkcyan" => "008B8B", "darkgoldenrod" => "B8860B",
    "darkgray" => "A9A9A9", "darkgreen" => "006400", "darkgrey" => "A9A9A9", "darkkhaki" => "BDB76B", "darkmagenta" => "8B008B", "darkolivegreen" => "556B2F",
    "darkorange" => "FF8C00", "darkorchid" => "9932CC", "darkred" => "8B0000", "darksalmon" => "E9967A", "darkseagreen" => "8FBC8F", "darkslateblue" => "483D8B",
    "darkslategray" => "2F4F4F", "darkslategrey" => "2F4F4F", "darkturquoise" => "00CED1", "darkviolet" => "9400D3", "deeppink" => "FF1493",
    "deepskyblue" => "00BFFF", "dimgray" => "696969", "dimgrey" => "696969", "dodgerblue" => "1E90FF", "firebrick" => "B22222", "floralwhite" => "FFFAF0",
    "forestgreen" => "228B22", "fuchsia" => "FF00FF", "gainsboro" => "DCDCDC", "ghostwhite" => "F8F8FF", "gold" => "FFD700", "goldenrod" => "DAA520",
    "gray" => "808080", "green" => "008000", "greenyellow" => "ADFF2F", "grey" => "808080", "honeydew" => "F0FFF0", "hotpink" => "FF69B4", "indianred" => "CD5C5C",
    "indigo" => "4B0082", "ivory" => "FFFFF0", "khaki" => "F0E68C", "lavender" => "E6E6FA", "lavenderblush" => "FFF0F5", "lawngreen" => "7CFC00",
    "lemonchiffon" => "FFFACD", "lightblue" => "ADD8E6", "lightcoral" => "F08080", "lightcyan" => "E0FFFF", "lightgoldenrodyellow" => "FAFAD2",
    "lightgray" => "D3D3D3", "lightgreen" => "90EE90", "lightgrey" => "D3D3D3", "lightpink" => "FFB6C1", "lightsalmon" => "FFA07A", "lightseagreen" => "20B2AA",
    "lightskyblue" => "87CEFA", "lightslategray" => "778899", "lightslategrey" => "778899", "lightsteelblue" => "B0C4DE", "lightyellow" => "FFFFE0",
    "lime" => "00FF00", "limegreen" => "32CD32", "linen" => "FAF0E6", "magenta" => "FF00FF", "maroon" => "800000", "mediumaquamarine" => "66CDAA",
    "mediumblue" => "0000CD", "mediumorchid" => "BA55D3", "mediumpurple" => "9370DB", "mediumseagreen" => "3CB371", "mediumslateblue" => "7B68EE",
    "mediumspringgreen" => "00FA9A", "mediumturquoise" => "48D1CC", "mediumvioletred" => "C71585", "midnightblue" => "191970", "mintcream" => "F5FFFA",
    "mistyrose" => "FFE4E1", "moccasin" => "FFE4B5", "navajowhite" => "FFDEAD", "navy" => "000080", "oldlace" => "FDF5E6", "olive" => "808000",
    "olivedrab" => "6B8E23", "orange" => "FFA500", "orangered" => "FF4500", "orchid" => "DA70D6", "palegoldenrod" => "EEE8AA", "palegreen" => "98FB98",
    "paleturquoise" => "AFEEEE", "palevioletred" => "DB7093", "papayawhip" => "FFEFD5", "peachpuff" => "FFDAB9", "peru" => "CD853F", "pink" => "FFC0CB",
    "plum" => "DDA0DD", "powderblue" => "B0E0E6", "purple" => "800080", "red" => "FF0000", "rosybrown" => "BC8F8F", "royalblue" => "4169E1",
    "saddlebrown" => "8B4513", "salmon" => "FA8072", "sandybrown" => "F4A460", "seagreen" => "2E8B57", "seashell" => "FFF5EE", "sienna" => "A0522D",
    "silver" => "C0C0C0", "skyblue" => "87CEEB", "slateblue" => "6A5ACD", "slategray" => "708090", "slategrey" => "708090", "snow" => "FFFAFA",
    "springgreen" => "00FF7F", "steelblue" => "4682B4", "tan" => "D2B48C", "teal" => "008080", "thistle" => "D8BFD8", "tomato" => "FF6347", "turquoise" => "40E0D0",
    "violet" => "EE82EE", "wheat" => "F5DEB3", "white" => "FFFFFF", "whitesmoke" => "F5F5F5", "yellow" => "FFFF00", "yellowgreen" => "9ACD32"
    );

    if ( is_array($colour) )
      // Assume the array has the right format...
      // FIXME: should/could verify this.
      return $colour;

    $colour = strtolower($colour);

    if (isset($cssColorNames[$colour]))
      return $this->getArray($cssColorNames[$colour]);

    if ($colour === "transparent")
      return "transparent";

    $length = mb_strlen($colour);

    // #rgb format
    if ( $length == 4 && $colour[0] === "#" ) {
      return $this->getArray($colour[1].$colour[1].$colour[2].$colour[2].$colour[3].$colour[3]);

    // #rrggbb format
    } else if ( $length == 7 && $colour[0] === "#" ) {
      return $this->getArray(mb_substr($colour, 1, 6));

    // rgb( r,g,b ) format
    } else if ( mb_strpos($colour, "rgb") !== false ) {
      $i = mb_strpos($colour, "(");
      $j = mb_strpos($colour, ")");

      // Bad colour value
      if ($i === false || $j === false)
        return null;

      $triplet = explode(",", mb_substr($colour, $i+1, $j-$i-1));

      if (count($triplet) != 3)
        return null;

      foreach (array_keys($triplet) as $c) {
        $triplet[$c] = trim($triplet[$c]);
        
        if ( $triplet[$c][mb_strlen($triplet[$c]) - 1] === "%" )
          $triplet[$c] = round($triplet[$c] * 2.55);
      }

      return $this->getArray(vsprintf("%02X%02X%02X", $triplet));

    // cmyk( c,m,y,k ) format
    // http://www.w3.org/TR/css3-gcpm/#cmyk-colors
    } else if ( mb_strpos($colour, "cmyk") !== false ) {
      $i = mb_strpos($colour, "(");
      $j = mb_strpos($colour, ")");

      // Bad colour value
      if ($i === false || $j === false)
        return null;

      $values = explode(",", mb_substr($colour, $i+1, $j-$i-1));

      if (count($values) != 4)
        return null;

      foreach ($values as &$c) {
        $c = floatval(trim($c));
        if ($c > 1.0) $c = 1.0;
        if ($c < 0.0) $c = 0.0;
      }

      return $this->getArray($values);
    }
  }

  //from css_color.cls.php
  private function getArray($colour) {

    //$c = array(null, null, null, null, "hex" => null);
    $c = array("hex" => null); //We only get the hex color
    if (is_array($colour)) {
      $c = $colour;
      /*$c["c"] = $c[0];
      $c["m"] = $c[1];
      $c["y"] = $c[2];
      $c["k"] = $c[3];*/
      $c["hex"] = "cmyk($c[0],$c[1],$c[2],$c[3])";
    }
    else {
      /*$c[0] = hexdec(mb_substr($colour, 0, 2)) / 0xff;
      $c[1] = hexdec(mb_substr($colour, 2, 2)) / 0xff;
      $c[2] = hexdec(mb_substr($colour, 4, 2)) / 0xff;
      $c["r"] = $c[0];
      $c["g"] = $c[1];
      $c["b"] = $c[2];*/
      $c["hex"] = "#$colour";
    }

    return $c;
  }

  /* direct access to _important_props array from outside would work only when declared as
   * 'var $_important_props;' instead of 'protected $_important_props;'
   * Don't call _set/__get on missing attribute. Therefore need a special access.
   * Assume that __set will be also called when this is called, so do not check validity again.
   * Only created, if !important exists -> always set true.
   */
  function important_set($prop) {

    $prop = str_replace("-", "_", $prop);
    $this->_important_props[$prop] = true;
  }

  /**
   * PHP5 overloaded setter
   *
   * This function along with {@link StyleParser::__get()} permit a user of the
   * StyleParser class to access any (CSS) property using the following syntax:
   * <code>
   *  StyleParser->margin_top = "1em";
   *  echo (StyleParser->margin_top);
   * </code>
   *
   * __set() automatically calls the provided set function, if one exists,
   * otherwise it sets the property directly.  Typically, __set() is not
   * called directly from outside of this class.
   *
   * On each modification clear cache to return accurate setting.
   * Also affects direct settings not using __set
   * For easier finding all assignments, attempted to allowing only explicite assignment:
   * Very many uses, e.g. frame_reflower.cls.php -> for now leave as it is
   * function __set($prop, $val) {
   *   throw new Exception("Implicite replacement of assignment by __set.  Not good.");
   * }
   * function props_set($prop, $val) { ... }
   *
   * @param string $prop  the property to set
   * @param mixed  $val   the value of the property
   *
   */
  function __set($prop, $val) {
    $prop = str_replace("-", "_", $prop);
    $this->_prop_cache[$prop] = null;

    if ( !isset(self::$_defaults[$prop]) ) {
      global $_parserhtml_warnings;
      $_parserhtml_warnings[] = "'$prop' is not a valid CSS2 property.";
      return;
    }

    if ( $prop !== "content" && $prop !== "font_family" && is_string($val) && strlen($val) > 5 && mb_strpos($val, "url") === false ) {
      $val = mb_strtolower(trim(str_replace(array("\n", "\t"), array(" "), $val)));
      $val = preg_replace("/([0-9]+) (pt|px|pc|em|ex|in|cm|mm|%)/S", "\\1\\2", $val);
    }

    $method = "set_$prop";

    if ( method_exists($this, $method) )
      $this->$method($val);
    else
      $this->_props[$prop] = $val;
  }

  /**
   * PHP5 overloaded getter
   *
   * Along with {@link StyleParser::__set()} __get() provides access to all CSS
   * properties directly.  Typically __get() is not called directly outside
   * of this class.
   *
   * On each modification clear cache to return accurate setting.
   * Also affects direct settings not using __set
   *
   * @param string $prop
   * @return mixed
   */
  function __get($prop) {
    return $this->getpropstyle($prop);
  }

  function getpropstyle($prop) {
    if ( !isset(self::$_defaults[$prop]) ){
      //throw new \Exception("'$prop' is not a valid CSS2 property.");
        return;
    }

    if ( isset($this->_prop_cache[$prop]) && $this->_prop_cache[$prop] != null )
      return $this->_prop_cache[$prop];

    $method = "get_$prop";

    // Fall back on defaults if property is not set
    if ( !isset($this->_props[$prop]) )
      $this->_props[$prop] = self::$_defaults[$prop];

    if ( method_exists($this, $method) )
      return $this->_prop_cache[$prop] = $this->$method();

    return $this->_prop_cache[$prop] = $this->_props[$prop];
  }

  /**
   * Getter para el array _props[]
   * Alguna informacion de estilo (como font_family) no se puede recuperar original, tal como se encuentra en el array _props[], este metodo lo permite
   * Devuelve false si no existe la propiedad pedida.
   *
   * @param string $prop Propiedad CSS a recuperar
   * @return string
   */
  function get_props($prop) {

    if(isset($this->_prop_cache[$prop])) return($this->_prop_cache[$prop]);
    elseif(isset($this->_props[$prop])) return($this->_props[$prop]);
    else return(false);
  }

  /**
   * Getter for the 'font-family' CSS property.
   *
   * Uses the {@link Font_Metrics} class to resolve the font family into an
   * actual font file.
   *
   * @link http://www.w3.org/TR/CSS21/fonts.html#propdef-font-family
   * @return string
   */
  function get_font_family() {

    //$DEBUGCSS=DEBUGCSS; //=DEBUGCSS; Allow override of global setting for ad hoc debug

    // Select the appropriate font.  First determine the subtype, then check
    // the specified font-families for a candidate.

    // Resolve font-weight
    $weight = $this->__get("font_weight");

    if ( is_numeric($weight) ) {

      if ( $weight < 600 )
        $weight = "normal";
      else
        $weight = "bold";

    } else if ( $weight === "bold" || $weight === "bolder" ) {
      $weight = "bold";

    } else {
      $weight = "normal";

    }

    // Resolve font-style
    $font_style = $this->__get("font_style");

    if ( $weight === "bold" && ($font_style === "italic" || $font_style === "oblique") )
      $subtype = "bold_italic";
    else if ( $weight === "bold" && $font_style !== "italic" && $font_style !== "oblique" )
      $subtype = "bold";
    else if ( $weight !== "bold" && ($font_style === "italic" || $font_style === "oblique") )
      $subtype = "italic";
    else
      $subtype = "normal";

    // Resolve the font family
    /*if ($DEBUGCSS) {
      print "<pre>[get_font_family:";
      print '('.$this->_props["font_family"].'.'.$font_style.'.'.$this->__get("font_weight").'.'.$weight.'.'.$subtype.')';
    }*/
    $families = explode(",", $this->_props["font_family"]);
    $families = array_map('trim',$families);
    reset($families);

    $font = null;
    foreach ($families as $familiesData) {
      //remove leading and trailing string delimiters, e.g. on font names with spaces;
      //remove leading and trailing whitespace
      $family = trim($familiesData," \t\n\r\x0B\"'");
      $font = $family;
      if ( $font ) {
        return $font;
      }
    }

    $family = null;
    //if ($DEBUGCSS)  print '(default)';
    $font = $family;

    if ( $font ) {
      //if ($DEBUGCSS) print '('.$font.")get_font_family]\n</pre>";
      return $font;
    }

  }

  /**
   * Returns the resolved font size, in points
   *
   * @link http://www.w3.org/TR/CSS21/fonts.html#propdef-font-size
   * @return float
   */
  function get_font_size() {

    if ( $this->__font_size_calculated )
      return $this->_props["font_size"];

    if ( !isset($this->_props["font_size"]) )
      $fs = self::$_defaults["font_size"];
    else
      $fs = $this->_props["font_size"];

    if ( !isset($this->_parent_font_size) )
      $this->_parent_font_size = $this->default_font_size;

    $font_size_keywords = array(
      "xx-small" => 0.6,   // 3/5
      "x-small"  => 0.75,  // 3/4
      "small"    => 0.889, // 8/9
      "medium"   => 1,     // 1
      "large"    => 1.2,   // 6/5
      "x-large"  => 1.5,   // 3/2
      "xx-large" => 2.0,   // 2/1
    );

    switch ($fs) {
    case "xx-small":
    case "x-small":
    case "small":
    case "medium":
    case "large":
    case "x-large":
    case "xx-large":
      $fs = $this->default_font_size * @$font_size_keywords[$fs];
      break;

    case "smaller":
      $fs = 8/9 * $this->_parent_font_size;
      break;

    case "larger":
      $fs = 6/5 * $this->_parent_font_size;
      break;

    default:
      break;
    }

    // Ensure relative sizes resolve to something
    if ( ($i = mb_strpos($fs, "em")) !== false )
      $fs = mb_substr($fs, 0, $i) * $this->_parent_font_size;

    else if ( ($i = mb_strpos($fs, "ex")) !== false )
      $fs = mb_substr($fs, 0, $i) * $this->_parent_font_size;

    else
      $fs = $this->length_in_pt($fs);

    //see __set and __get, on all assignments clear cache!
    $this->_prop_cache["font_size"] = null;
    $this->_props["font_size"] = $fs;
    $this->__font_size_calculated = true;
    return $this->_props["font_size"];

  }

  /**
   * @link http://www.w3.org/TR/CSS21/visudet.html#propdef-line-height
   * @return float
   */
  function get_line_height() {

    $line_height = $this->_props["line_height"];

    if ( $line_height === "normal" )
      return $this->default_line_height * $this->get_font_size();

    if ( is_numeric($line_height) )
      return $this->length_in_pt( $line_height . "em", $this->get_font_size());

    return $this->length_in_pt( $line_height, $this->get_font_size() );
  }

  /**
   * Returns the colour as an array
   *
   * The array has the following format:
   * <code>array(r,g,b, "r" => r, "g" => g, "b" => b, "hex" => "#rrggbb")</code>
   *
   * @link http://www.w3.org/TR/CSS21/colors.html#propdef-color
   * @return array
   */
  function get_color() {

    return $this->munge_colour( $this->_props["color"] );
  }

  /**
   * Returns the background colour as an array
   *
   * The returned array has the same format as {@link StyleParser::get_color()}
   *
   * @link http://www.w3.org/TR/CSS21/colors.html#propdef-background-color
   * @return array
   */
  function get_background_color() {

    return $this->munge_colour( $this->_props["background_color"] );
  }

  /**#@+
   * Returns the border colour as an array
   *
   * See {@link StyleParser::get_color()}
   *
   * @link http://www.w3.org/TR/CSS21/box.html#border-color-properties
   * @return array
   */
  function get_border_top_color() {

    if ( $this->_props["border_top_color"] === "" ) {
      //see __set and __get, on all assignments clear cache!
      $this->_prop_cache["border_top_color"] = null;
      $this->_props["border_top_color"] = $this->__get("color");
    }
    return $this->munge_colour($this->_props["border_top_color"]);
  }

  function get_border_right_color() {

    if ( $this->_props["border_right_color"] === "" ) {
      //see __set and __get, on all assignments clear cache!
      $this->_prop_cache["border_right_color"] = null;
      $this->_props["border_right_color"] = $this->__get("color");
    }
    return $this->munge_colour($this->_props["border_right_color"]);
  }

  function get_border_bottom_color() {

    if ( $this->_props["border_bottom_color"] === "" ) {
      //see __set and __get, on all assignments clear cache!
      $this->_prop_cache["border_bottom_color"] = null;
      $this->_props["border_bottom_color"] = $this->__get("color");
    }
    return $this->munge_colour($this->_props["border_bottom_color"]);
  }

  function get_border_left_color() {

    if ( $this->_props["border_left_color"] === "" ) {
      //see __set and __get, on all assignments clear cache!
      $this->_prop_cache["border_left_color"] = null;
      $this->_props["border_left_color"] = $this->__get("color");
    }
    return $this->munge_colour($this->_props["border_left_color"]);
  }
  

 /**
  * Returns the border width, as it is currently stored
  *
  * @link http://www.w3.org/TR/CSS21/box.html#border-width-properties
  * @return float|string
  */
  function get_border_top_width() {

    $style = $this->__get("border_top_style");
    return $style !== "none" && $style !== "hidden" ? $this->length_in_pt($this->_props["border_top_width"]) : 0;
  }

  function get_border_right_width() {

    $style = $this->__get("border_right_style");
    return $style !== "none" && $style !== "hidden" ? $this->length_in_pt($this->_props["border_right_width"]) : 0;
  }

  function get_border_bottom_width() {

    $style = $this->__get("border_bottom_style");
    return $style !== "none" && $style !== "hidden" ? $this->length_in_pt($this->_props["border_bottom_width"]) : 0;
  }

  function get_border_left_width() {

    $style = $this->__get("border_left_style");
    return $style !== "none" && $style !== "hidden" ? $this->length_in_pt($this->_props["border_left_width"]) : 0;
  }

  /**
   * Return a single border property
   *
   * @return mixed
   */
  protected function _get_border($side) {
    $color = $this->__get("border_" . $side . "_color");

    return $this->__get("border_" . $side . "_width") . " " .
      $this->__get("border_" . $side . "_style") . " " . $color["hex"];
  }

  /**
   * Return full border properties as a string
   *
   * Border properties are returned just as specified in CSS:
   * <pre>[width] [style] [color]</pre>
   * e.g. "1px solid blue"
   *
   * @link http://www.w3.org/TR/CSS21/box.html#border-shorthand-properties
   * @return string
   */
  function get_border_top() { return $this->_get_border("top"); }
  function get_border_right() { return $this->_get_border("right"); }
  function get_border_bottom() { return $this->_get_border("bottom"); }
  function get_border_left() { return $this->_get_border("left"); }

  /*
   !important attribute
   For basic functionality of the !important attribute with overloading
   of several styles of an element, changes in inherit(), merge() and _parse_properties()
   are sufficient [helpers var $_important_props, __construct(), important_set(), important_get()]

   Only for combined attributes extra treatment needed. See below.

   div { border: 1px red; }
   div { border: solid; } // Not combined! Only one occurence of same style per context
   //
   div { border: 1px red; }
   div a { border: solid; } // Adding to border style ok by inheritance
   //
   div { border-style: solid; } // Adding to border style ok because of different styles
   div { border: 1px red; }
   //
   div { border-style: solid; !important} // border: overrides, even though not !important
   div { border: 1px dashed red; }
   //
   div { border: 1px red; !important }
   div a { border-style: solid; } // Need to override because not set

   Special treatment:
   At individual property like border-top-width need to check whether overriding value is also !important.
   Also store the !important condition for later overrides.
   Since not known who is initiating the override, need to get passed !importan as parameter.
   !important Paramter taken as in the original style in the css file.
   When poperty border !important given, do not mark subsets like border_style as important. Only
   individual properties.

   Note:
   Setting individual property directly from css with e.g. set_border_top_style() is not needed, because
   missing set funcions handled by a generic handler __set(), including the !important.
   Setting individual property of as sub-property is handled below.

   Implementation see at _set_style_side_type()
   Callers _set_style_sides_type(), _set_style_type, _set_style_type_important()

   Related functionality for background, padding, margin, font, list_style
  */

  /* Generalized set function for individual attribute of combined style.
   * With check for !important
   * Applicable for background, border, padding, margin, font, list_style
   * Note: $type has a leading underscore (or is empty), the others not.
   */
  protected function _set_style_side_type($style,$side,$type,$val,$important) {

    $prop = $style.'_'.$side.$type;

    if ( !isset($this->_important_props[$prop]) || $important) {
      //see __set and __get, on all assignments clear cache!
      $this->_prop_cache[$prop] = null;
      if ($important) {
        $this->_important_props[$prop] = true;
      }
      $this->_props[$prop] = $val;
    }
  }

  protected function _set_style_sides_type($style,$top,$right,$bottom,$left,$type,$important) {

      $this->_set_style_side_type($style,'top',$type,$top,$important);
      $this->_set_style_side_type($style,'right',$type,$right,$important);
      $this->_set_style_side_type($style,'bottom',$type,$bottom,$important);
      $this->_set_style_side_type($style,'left',$type,$left,$important);
  }

  protected function _set_style_type($style,$type,$val,$important) {

    $arr = explode(" ", $val);
    switch (count($arr)) {
    case 1:
      $this->_set_style_sides_type($style,$arr[0],$arr[0],$arr[0],$arr[0],$type,$important);
      break;
    case 2:
      $this->_set_style_sides_type($style,$arr[0],$arr[1],$arr[0],$arr[1],$type,$important);
      break;
    case 3:
      $this->_set_style_sides_type($style,$arr[0],$arr[1],$arr[2],$arr[1],$type,$important);
      break;
    case 4:
      $this->_set_style_sides_type($style,$arr[0],$arr[1],$arr[2],$arr[3],$type,$important);
      break;
    default:
      break;
    }
    //see __set and __get, on all assignments clear cache!
    $this->_prop_cache[$style.$type] = null;
    $this->_props[$style.$type] = $val;
  }

  protected function _set_style_type_important($style,$type,$val) {

    $this->_set_style_type($style,$type,$val,isset($this->_important_props[$style.$type]));
  }

  /* Anyway only called if _important matches and is assigned
   * E.g. _set_style_side_type($style,$side,'',str_replace("none", "0px", $val),isset($this->_important_props[$style.'_'.$side]));
   */
  protected function _set_style_side_width_important($style,$side,$val) {

    //see __set and __get, on all assignments clear cache!
    $this->_prop_cache[$style.'_'.$side] = null;
    $this->_props[$style.'_'.$side] = str_replace("none", "0px", $val);
  }

  protected function _set_style($style,$val,$important) {
    if ( !isset($this->_important_props[$style]) || $important) {
      if ($important) {
        $this->_important_props[$style] = true;
      }
      //see __set and __get, on all assignments clear cache!
      $this->_prop_cache[$style] = null;
      $this->_props[$style] = $val;
    }
  }

  protected function _image($val) {

    if ( mb_strpos($val, "url") === false ) {
      $path = "none"; //Don't resolve no image -> otherwise would prefix path and no longer recognize as none
    } else {
      $val = preg_replace("/url\(['\"]?([^'\")]+)['\"]?\)/","\\1", trim($val));

      // Resolve the url now in the context of the current stylesheet
      $parsed_url = explode_url_parser($val);
      if ( $parsed_url["protocol"] == "" && $this->_stylesheet->get_protocol() == "" ) {
        if ($parsed_url["path"][0] === '/' || $parsed_url["path"][0] === '\\' ) {
          $path = $_SERVER["DOCUMENT_ROOT"].'/';
        } else {
          $path = $this->_stylesheet->get_base_path();
        }
        $path .= $parsed_url["path"] . $parsed_url["file"];
        $path = realpath($path);
        // If realpath returns FALSE then specifically state that there is no background image
        if (!$path) { $path = 'none'; }
      } else {
        $path = build_url_parser($this->_stylesheet->get_protocol(),
                          $this->_stylesheet->get_host(),
                          $this->_stylesheet->get_base_path(),
                          $val);
      }
    }
    return $path;
  }

  /**
   * Sets colour
   *
   * The colour parameter can be any valid CSS colour value
   *
   * @link http://www.w3.org/TR/CSS21/colors.html#propdef-color
   * @param string $colour
   */
  function set_color($colour) {

    $col = $this->munge_colour($colour);

    if ( is_null($col) ) {
      $col = self::$_defaults["color"];
    }

    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["color"] = null;
    if (is_array($col)) {
        $this->_props["color"] = $col["hex"];
    } else {
        $this->_props["color"] = $col;
    }
  }

  /**
   * Sets the background colour
   *
   * @link http://www.w3.org/TR/CSS21/colors.html#propdef-background-color
   * @param string $colour
   */
  function set_background_color($colour) {

    $col = $this->munge_colour($colour);
    if ( is_null($col) )
      $col = self::$_defaults["background_color"];

    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["background_color"] = null;
    $this->_props["background_color"] = is_array($col) ? $col["hex"] : $col;
  }

  /**
   * Sets the background - combined options
   *
   * @link http://www.w3.org/TR/CSS21/colors.html#propdef-background
   * @param string $val
   */
  function set_background($val) {
    $col = null;
    $pos = array();
    $tmp = preg_replace("/\s*\,\s*/", ",", $val); // when rgb() has spaces
    $tmp = explode(" ", $tmp);
    $important = isset($this->_important_props["background"]);

    foreach($tmp as $attr) {
      if (($col = $this->munge_colour($attr)) != null ) {
        $this->_set_style("background_color", is_array($col) ? $col["hex"] : $col, $important);
      } else {
        $pos[] = $attr;
      }
    }

    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    /*
    //FIXME inheritance problems: background: #ccc; in <table> overwrite background-color in <tr>, <td>, ...
    $this->_prop_cache["background"] = null;
    $this->_props["background"] = $val;
    /**/
  }

  /**
   * Sets the font size
   *
   * $size can be any acceptable CSS size
   *
   * @link http://www.w3.org/TR/CSS21/fonts.html#propdef-font-size
   * @param string|float $size
   */
  function set_font_size($size) {

    $this->__font_size_calculated = false;
    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["font_size"] = null;
    $this->_props["font_size"] = $size;
  }

  /**
   * Sets the font style
   *
   * combined attributes
   * set individual attributes also, respecting !important mark
   * exactly this order, separate by space. Multiple fonts separated by comma:
   * font-style, font-variant, font-weight, font-size, line-height, font-family
   *
   * Other than with border and list, existing partial attributes should
   * reset when starting here, even when not mentioned.
   * If individual attribute is !important and explicite or implicite replacement is not,
   * keep individual attribute
   *
   * require whitespace as delimiters for single value attributes
   * On delimiter "/" treat first as font height, second as line height
   * treat all remaining at the end of line as font
   * font-style, font-variant, font-weight, font-size, line-height, font-family
   *
   * missing font-size and font-family might be not allowed, but accept it here and
   * use default (medium size, enpty font name)
   *
   * @link http://www.w3.org/TR/CSS21/generate.html#propdef-list-style
   * @param $val
   */
  function set_font($val) {
    $this->__font_size_calculated = false;
    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["font"] = null;
    $this->_props["font"] = $val;

    $important = isset($this->_important_props["font"]);

    if ( preg_match("/^(italic|oblique|normal)\s*(.*)$/i",$val,$match) ) {
      $this->_set_style("font_style", $match[1], $important);
      $val = $match[2];
    } else {
      $this->_set_style("font_style", self::$_defaults["font_style"], $important);
    }

    if ( preg_match("/^(small-caps|normal)\s*(.*)$/i",$val,$match) ) {
      $this->_set_style("font_variant", $match[1], $important);
      $val = $match[2];
    } else {
      $this->_set_style("font_variant", self::$_defaults["font_variant"], $important);
    }

    //matching numeric value followed by unit -> this is indeed a subsequent font size. Skip!
    if ( preg_match("/^(bold|bolder|lighter|100|200|300|400|500|600|700|800|900|normal)\s*(.*)$/i",$val,$match) &&
         !preg_match("/^(?:pt|px|pc|em|ex|in|cm|mm|%)/",$match[2])
       ) {
      $this->_set_style("font_weight", $match[1], $important);
      $val = $match[2];
    } else {
      $this->_set_style("font_weight", self::$_defaults["font_weight"], $important);
    }

    if ( preg_match("/^(xx-small|x-small|small|medium|large|x-large|xx-large|smaller|larger|\d+\s*(?:pt|px|pc|em|ex|in|cm|mm|%))\s*(.*)$/i",$val,$match) ) {
      $this->_set_style("font_size", $match[1], $important);
      $val = $match[2];
      if (preg_match("/^\/\s*(\d+\s*(?:pt|px|pc|em|ex|in|cm|mm|%))\s*(.*)$/i",$val,$match) ) {
        $this->_set_style("line_height", $match[1], $important);
        $val = $match[2];
      } else {
        $this->_set_style("line_height", self::$_defaults["line_height"], $important);
      }
    } else {
      $this->_set_style("font_size", self::$_defaults["font_size"], $important);
      $this->_set_style("line_height", self::$_defaults["line_height"], $important);
    }

    if(strlen($val) != 0) {
      $this->_set_style("font_family", $val, $important);
    } else {
      $this->_set_style("font_family", self::$_defaults["font_family"], $important);
    }
  }

  /**
   * Sets page break properties
   *
   * @link http://www.w3.org/TR/CSS21/page.html#page-breaks
   * @param string $break
   */
  function set_page_break_before($break) {
    if ($break === "left" || $break === "right")
      $break = "always";

    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["page_break_before"] = null;
    $this->_props["page_break_before"] = $break;
  }

  function set_page_break_after($break) {
    if ($break === "left" || $break === "right")
      $break = "always";

    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["page_break_after"] = null;
    $this->_props["page_break_after"] = $break;
  }

  /**
   * Sets the margin size
   *
   * @link http://www.w3.org/TR/CSS21/box.html#margin-properties
   * @param $val
   */
  function set_margin_top($val) {

    $this->_set_style_side_width_important('margin','top',$val);
  }

  function set_margin_right($val) {

    $this->_set_style_side_width_important('margin','right',$val);
  }

  function set_margin_bottom($val) {

    $this->_set_style_side_width_important('margin','bottom',$val);
  }

  function set_margin_left($val) {

    $this->_set_style_side_width_important('margin','left',$val);
  }

  function set_margin($val) {

    $val = str_replace("none", "0px", $val);
    $this->_set_style_type_important('margin','',$val);
  }

  /**
   * Sets the padding size
   *
   * @link http://www.w3.org/TR/CSS21/box.html#padding-properties
   * @param $val
   */
  function set_padding_top($val) {
    $this->_set_style_side_width_important('padding','top',$val);
  }

  function set_padding_right($val) {
    $this->_set_style_side_width_important('padding','right',$val);
  }

  function set_padding_bottom($val) {
    $this->_set_style_side_width_important('padding','bottom',$val);
  }

  function set_padding_left($val) {
    $this->_set_style_side_width_important('padding','left',$val);
  }

  function set_padding($val) {

    $val = str_replace("none", "0px", $val);
    $this->_set_style_type_important('padding','',$val);
  }

  /**
   * Sets a single border
   *
   * @param string $side
   * @param string $border_spec  ([width] [style] [color])
   */
  protected function _set_border($side, $border_spec, $important) {

    $border_spec = preg_replace("/\s*\,\s*/", ",", $border_spec);
    //$border_spec = str_replace(",", " ", $border_spec); // Why did we have this ?? rbg(10, 102, 10) > rgb(10  102  10)
    $arr = explode(" ", $border_spec);

    // FIXME: handle partial values
 
    //For consistency of individal and combined properties, and with ie8 and firefox3
    //reset all attributes, even if only partially given
    $this->_set_style_side_type('border',$side,'_style',self::$_defaults['border_'.$side.'_style'],$important);
    $this->_set_style_side_type('border',$side,'_width',self::$_defaults['border_'.$side.'_width'],$important);
    $this->_set_style_side_type('border',$side,'_color',self::$_defaults['border_'.$side.'_color'],$important);

    $BORDER_STYLES = array("none", "hidden", "dotted", "dashed", "solid", "double", "groove", "ridge", "inset", "outset");
    foreach ($arr as $value) {
      $value = trim($value);
      if ( in_array($value, $BORDER_STYLES) ) {
        $this->_set_style_side_type('border',$side,'_style',$value,$important);

      } else if ( preg_match("/[.0-9]+(?:px|pt|pc|em|ex|%|in|mm|cm)|(?:thin|medium|thick)/", $value ) ) {
        $this->_set_style_side_type('border',$side,'_width',$value,$important);

      } else {
        // must be colour
        $this->_set_style_side_type('border',$side,'_color',$value,$important);
      }
    }

    //see __set and __get, on all assignments clear cache!
    $this->_prop_cache['border_'.$side] = null;
    $this->_props['border_'.$side] = $border_spec;
  }

  /**
   * Sets the border styles
   *
   * @link http://www.w3.org/TR/CSS21/box.html#border-properties
   * @param string $val
   */
  function set_border_top($val) {
$this->_set_border("top", $val, isset($this->_important_props['border_top'])); }
  function set_border_right($val) {
$this->_set_border("right", $val, isset($this->_important_props['border_right'])); }
  function set_border_bottom($val) {
$this->_set_border("bottom", $val, isset($this->_important_props['border_bottom'])); }
  function set_border_left($val) {
$this->_set_border("left", $val, isset($this->_important_props['border_left'])); }

  function set_border($val) {

    $important = isset($this->_important_props["border"]);
    $this->_set_border("top", $val, $important);
    $this->_set_border("right", $val, $important);
    $this->_set_border("bottom", $val, $important);
    $this->_set_border("left", $val, $important);
    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["border"] = null;
    $this->_props["border"] = $val;
  }

  function set_border_width($val) {
    $this->_set_style_type_important('border','_width',$val);
  }

  function set_border_color($val) {
    $this->_set_style_type_important('border','_color',$val);
  }

  function set_border_style($val) {
    $this->_set_style_type_important('border','_style',$val);
  }

  /**
   * Sets the border spacing
   *
   * @link http://www.w3.org/TR/CSS21/box.html#border-properties
   * @param float $val
   */
  function set_border_spacing($val) {

    $arr = explode(" ", $val);

    if ( count($arr) == 1 )
      $arr[1] = $arr[0];

    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["border_spacing"] = null;
    $this->_props["border_spacing"] = "$arr[0] $arr[1]";
  }

  /**
   * Sets the list style image
   *
   * @link http://www.w3.org/TR/CSS21/generate.html#propdef-list-style-image
   * @param $val
   */
  function set_list_style_image($val) {
    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["list_style_image"] = null;
    $this->_props["list_style_image"] = $this->_image($val);
  }

  /**
   * Sets the list style
   *
   * @link http://www.w3.org/TR/CSS21/generate.html#propdef-list-style
   * @param $val
   */
  function set_list_style($val) {
    $important = isset($this->_important_props["list_style"]);
    $arr = explode(" ", str_replace(",", " ", $val));

    static $types = array(
      "disc", "circle", "square",
      "decimal-leading-zero", "decimal", "1",
      "lower-roman", "upper-roman", "a", "A",
      "lower-greek",
      "lower-latin", "upper-latin",
      "lower-alpha", "upper-alpha",
      "armenian", "georgian", "hebrew",
      "cjk-ideographic", "hiragana", "katakana",
      "hiragana-iroha", "katakana-iroha", "none"
    );

    static $positions = array("inside", "outside");

    foreach ($arr as $value) {
      /* http://www.w3.org/TR/CSS21/generate.html#list-style
       * A value of 'none' for the 'list-style' property sets both 'list-style-type' and 'list-style-image' to 'none'
       */
      if ($value === "none") {
         $this->_set_style("list_style_type", $value, $important);
         $this->_set_style("list_style_image", $value, $important);
        continue;
      }

      //On setting or merging or inheriting list_style_image as well as list_style_type,
      //and url exists, then url has precedence, otherwise fall back to list_style_type
      //Firefox is wrong here (list_style_image gets overwritten on explicite list_style_type)
      //Internet Explorer 7/8 and dompdf is right.

      if (mb_substr($value, 0, 3) === "url") {
        $this->_set_style("list_style_image", $this->_image($value), $important);
        continue;
      }

      if ( in_array($value, $types) ) {
        $this->_set_style("list_style_type", $value, $important);
      } else if ( in_array($value, $positions) ) {
        $this->_set_style("list_style_position", $value, $important);
      }
    }

    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["list_style"] = null;
    $this->_props["list_style"] = $val;
  }

  function set_size($val) {

    $length_re = "/(\d+\s*(?:pt|px|pc|em|ex|in|cm|mm|%))/";

    $val = mb_strtolower($val);

    if ( $val === "auto" ) {
      return;
    }

    $parts = preg_split("/\s+/", $val);

    $computed = array();
    if ( preg_match($length_re, $parts[0]) ) {
      $computed[] = $this->length_in_pt($parts[0]);

      if ( isset($parts[1]) && preg_match($length_re, $parts[1]) ) {
        $computed[] = $this->length_in_pt($parts[1]);
      }
      else {
        $computed[] = $computed[0];
      }
    }
    elseif ( isset(CPDF_Adapter::$PAPER_SIZES[$parts[0]]) ) {
      $computed = array_slice(CPDF_Adapter::$PAPER_SIZES[$parts[0]], 2, 2);

      if ( isset($parts[1]) && $parts[1] === "landscape" ) {
        $computed = array_reverse($computed);
      }
    }
    else {
      return;
    }

    $this->_props["size"] = $computed;
  }

}

/**
 * PARSERHTML - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: stylesheet.cls.php,v $
 * Created on: 2004-06-01
 *
 * Copyright (c) 2004 - Benj Carson <benjcarson@digitaljunkies.ca>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this library in the file LICENSE.LGPL; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 * 02111-1307 USA
 *
 * Alternatively, you may distribute this software under the terms of the
 * PHP License, version 3.0 or later.  A copy of this license should have
 * been distributed with this file in the file LICENSE.PHP .  If this is not
 * the case, you can obtain a copy at http://www.php.net/license/3_0.txt.
 *
 * The latest version of DOMPDF might be available at:
 * http://www.dompdf.com/
 *
 * @link http://www.dompdf.com/
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @package parserhtml
 *
 * Changes
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @version 0.5.1.htischer.20090507
 * - Specifity of css selector chain was too small because leading whitespace
 *   to be counted as number of elements was removed
 * - On parsing css properties accept and register !important attribute
 * - Add optional debug output
 * @version 20090610
 * - _parse_properties on style property name and value remove augmenting superflous
 *   space for consistent parsing, in particular combined values like background
 */

/* $Id: stylesheet.cls.php 360 2011-02-15 19:33:52Z fabien.menager $ */

/**
 * The location of the default built-in CSS file.
 * {@link StyleParsersheet::DEFAULT_STYLESHEET}
 */
define('__DEFAULT_STYLESHEET', dirname(__FILE__) . '/../../../templates/html.css');

/**
 * The master stylesheet class
 *
 * The StyleParsersheet class is responsible for parsing stylesheets and style
 * tags/attributes.  It also acts as a registry of the individual StyleParser
 * objects generated by the current set of loaded CSS files and style
 * elements.
 *
 * @see StyleParser
 * @package parserhtml
 */
class StyleParsersheet {

  /**
   * The location of the default built-in CSS file.
   */
  const DEFAULT_STYLESHEET = __DEFAULT_STYLESHEET;

  /**
   * Array of currently defined styles
   * @var array
   */
  private $_styles;

  /**
   * Base protocol of the document being parsed
   * Used to handle relative urls.
   * @var string
   */
  private $_protocol;

  /**
   * Base hostname of the document being parsed
   * Used to handle relative urls.
   * @var string
   */
  private $_base_host;

  /**
   * Base path of the document being parsed
   * Used to handle relative urls.
   * @var string
   */
  private $_base_path;

  /**
   * The style defined by @page rules
   * @var StyleParser
   */
  private $_page_style;

  /**
   * List of loaded files, used to prevent recursion
   * @var array
   */
  private $_loaded_files;

  /**
   * Accepted CSS media types
   * List of types and parsing rules for future extensions:
   * http://www.w3.org/TR/REC-html40/types.html
   *   screen, tty, tv, projection, handheld, print, braille, aural, all
   * The following are non standard extensions for undocumented specific environments.
   *   static, visual, bitmap, paged, dompdf
   * Note, even though the generated pdf file is intended for print output,
   * the desired content might be different (e.g. screen or projection view of html file).
   * Therefore allow specification of content by dompdf setting DOMPDF_DEFAULT_MEDIA_TYPE.
   * If given, replace media "print" by DOMPDF_DEFAULT_MEDIA_TYPE.
   * (Previous version $ACCEPTED_MEDIA_TYPES = $ACCEPTED_GENERIC_MEDIA_TYPES + $ACCEPTED_DEFAULT_MEDIA_TYPE)
   */
  static $ACCEPTED_DEFAULT_MEDIA_TYPE = "print";
  static $ACCEPTED_GENERIC_MEDIA_TYPES = array("all", "static", "visual", "bitmap", "paged", "parserhtml");

  /**
   * The class constructor.
   *
   * The base protocol, host & path are initialized to those of
   * the current script.
   */
  function __construct() {
    $this->_styles = array();
    $this->_loaded_files = array();
    list($this->_protocol, $this->_base_host, $this->_base_path) = explode_url_parser($_SERVER["SCRIPT_FILENAME"]);
    $this->_page_style = null;
  }

  /**
   * Set the base protocol
   *
   * @param string $proto
   */
  function set_protocol($proto) { $this->_protocol = $proto; }

  /**
   * Set the base host
   *
   * @param string $host
   */
  function set_host($host) { $this->_base_host = $host; }

  /**
   * Set the base path
   *
   * @param string $path
   */
  function set_base_path($path) { $this->_base_path = $path; }

  /**
   * Return the base protocol for this stylesheet
   *
   * @return string
   */
  function get_protocol() { return $this->_protocol; }

  /**
   * Return the base host for this stylesheet
   *
   * @return string
   */
  function get_host() { return $this->_base_host; }

  /**
   * Return the base path for this stylesheet
   *
   * @return string
   */
  function get_base_path() { return $this->_base_path; }

  /**
   * Return the page style
   *
   * @return StyleParser
   */
  function get_page_style() { return $this->_page_style; }

  /**
   * Add a new StyleParser object to the stylesheet
   *
   * add_style() adds a new StyleParser object to the current stylesheet, or
   * merges a new StyleParser with an existing one.
   *
   * @param string $key   the StyleParser's selector
   * @param StyleParser $style  the StyleParser to be added
   */
  function add_style($key, StyleParser $style) {
    if (!is_string($key))
      throw new \Exception("CSS rule must be keyed by a string.");

    if ( isset($this->_styles[$key]) )
      $this->_styles[$key]->merge($style);
    else
      $this->_styles[$key] = clone $style;
  }


  /**
   * create a new StyleParser object associated with this stylesheet
   *
   * @param StyleParser $parent The style of this style's parent in the DOM tree
   * @return StyleParser
   */
  function create_style(StyleParser $parent = null) {
    return new StyleParser($this, $parent);
  }

  /**
   * load and parse a CSS string
   *
   * @param string $css
   */
  function load_css(&$css) { $this->_parse_css($css); }

  /**
   * load and parse a CSS file
   *
   * @param string $file
   */
  function load_css_file($file) {
    global $_parserhtml_warnings;

    // Prevent circular references
    if ( isset($this->_loaded_files[$file]) )
      return;

    $this->_loaded_files[$file] = true;
    $parsed_url = explode_url_parser($file);

    list($this->_protocol, $this->_base_host, $this->_base_path, $filename) = $parsed_url;

    if ( !PARSERHTML_ENABLE_REMOTE &&
         ($this->_protocol != "" && $this->_protocol !== "file://") ) {
      record_warnings_parser(E_USER_WARNING, "Remote CSS file '$file' requested, but PARSERHTML_ENABLE_REMOTE is false.", __FILE__, __LINE__);
      return;
    }

    // Fix submitted by Nick Oostveen for aliased directory support:
    if ( $this->_protocol == "" )
      $file = $this->_base_path . $filename;
    else
      $file = build_url_parser($this->_protocol, $this->_base_host, $this->_base_path, $filename);

    // set_error_handler("record_warnings_parser");
    @$css = file_get_contents($file);
    // restore_error_handler();

    if ( $css == "" ) {
      record_warnings_parser(E_USER_WARNING, "Unable to load css file $file", __FILE__, __LINE__);
      return;
    }

    $this->_parse_css($css);
  }

  /**
   * @link http://www.w3.org/TR/CSS21/cascade.html#specificity}
   *
   * @param string $selector
   * @return int
   */
  private function _specificity($selector) {
    // http://www.w3.org/TR/CSS21/cascade.html#specificity
    // ignoring the ":" pseudoclass modifyers
    // also ignored in _css_selector_to_xpath

    $a = ($selector === "!style attribute") ? 1 : 0;

    $b = min(mb_substr_count($selector, "#"), 255);

    $c = min(mb_substr_count($selector, ".") +
             mb_substr_count($selector, "["), 255);

    $d = min(mb_substr_count($selector, " ") +
             mb_substr_count($selector, ">") +
             mb_substr_count($selector, "+"), 255);

    //If a normal element name is at the begining of the string,
    //a leading whitespace might have been removed on whitespace collapsing and removal
    //therefore there might be one whitespace less as selected element names
    //this can lead to a too small specificity
    //see _css_selector_to_xpath

    if ( !in_array($selector[0], array(" ", ">", ".", "#", "+", ":", "[")) ) {
      $d++;
    }

    return ($a << 24) | ($b << 16) | ($c << 8) | ($d);
  }

  /**
   * converts a CSS selector to an XPath query.
   *
   * @param string $selector
   * @return string
   */
  private function _css_selector_to_xpath($selector, $first_pass = false) {

    // Collapse white space and strip whitespace around delimiters
//     $search = array("/\\s+/", "/\\s+([.>#+:])\\s+/");
//     $replace = array(" ", "\\1");
//     $selector = preg_replace($search, $replace, trim($selector));

    // Initial query (non-absolute)
    $query = "//";

    // Will contain :before and :after if they must be created
    $pseudo_elements = array();

    // Will contain :link, etc
    $pseudo_classes = array();

    // Parse the selector
    //$s = preg_split("/([ :>.#+])/", $selector, -1, PREG_SPLIT_DELIM_CAPTURE);

    $delimiters = array(" ", ">", ".", "#", "+", ":", "[", "(");

    // Add an implicit * at the beginning of the selector
    // if it begins with an attribute selector
    if ( $selector[0] === "[" )
      $selector = "*$selector";

    // Add an implicit space at the beginning of the selector if there is no
    // delimiter there already.
    if ( !in_array($selector[0], $delimiters) )
      $selector = " $selector";

    $tok = "";
    $len = mb_strlen($selector);
    $i = 0;

    while ( $i < $len ) {

      $s = $selector[$i];
      $i++;

      // Eat characters up to the next delimiter
      $tok = "";
      $in_attr = false;
      $in_func = false;

      while ($i < $len) {
        $c = $selector[$i];
        $c_prev = $selector[$i-1];
        
        if (!$in_func && !$in_attr && in_array($c, $delimiters) && !(($c == $c_prev) == ":")) {
          break;
        }

        if ( $c_prev === "[" ) {
          $in_attr = true;
        }
        if ($c_prev === "(") {
          $in_func = true;
        }

        $tok .= $selector[$i++];

        if ($in_attr && $c === "]") {
          $in_attr = false;
          break;
        }
        if ($in_func && $c === ")") {
          $in_func = false;
          break;
        }
      }

      switch ($s) {

      case " ":
      case ">":
        // All elements matching the next token that are direct children of
        // the current token
        $expr = $s === " " ? "descendant" : "child";

        if ( mb_substr($query, -1, 1) !== "/" ) {
          $query .= "/";
        }

        // Tag names are case-insensitive
        $tok = strtolower($tok);

        if ( !$tok ) {
          $tok = "*";
        }

        $query .= "$expr::$tok";
        $tok = "";
        break;

      case ".":
      case "#":
        // All elements matching the current token with a class/id equal to
        // the _next_ token.

        $attr = $s === "." ? "class" : "id";

        // empty class/id == *
        if ( mb_substr($query, -1, 1) === "/" ) {
          $query .= "*";
        }

        // Match multiple classes: $tok contains the current selected
        // class.  Search for class attributes with class="$tok",
        // class=".* $tok .*" and class=".* $tok"

        // This doesn't work because libxml only supports XPath 1.0...
        //$query .= "[matches(@$attr,\"^${tok}\$|^${tok}[ ]+|[ ]+${tok}\$|[ ]+${tok}[ ]+\")]";

        // Query improvement by Michael Sheakoski <michael@mjsdigital.com>:
        $query .= "[contains(concat(' ', @$attr, ' '), concat(' ', '$tok', ' '))]";
        $tok = "";
        break;

      case "+":
        // All sibling elements that folow the current token
        if ( mb_substr($query, -1, 1) !== "/" )
          $query .= "/";

        $query .= "following-sibling::$tok";
        $tok = "";
        break;

      case ":":
        $i2 = $i - strlen($tok) - 2; // the char before ":"
        if (($i2 < 0 || !isset($selector[$i2]) || (in_array($selector[$i2], $delimiters) && $selector[$i2] != ":")) && substr($query, -1) != "*") {
            $query .= "*";
        }

        $last = false;

        // Pseudo-classes
        switch ($tok) {

        case "first-child":
          $query .= "[1]";
          $tok = "";
          break;

        case "last-child":
          $query .= "[not(following-sibling::*)]";
          $tok = "";
          break;

        case "first-of-type":
          $query .= "[position() = 1]";
          $tok = "";
          break;

        case "last-of-type":
          $query .= "[position() = last()]";
          $tok = "";
          break;

        case "nth-of-type":
          //FIXME: this fix-up is pretty ugly, would parsing the selector in reverse work better generally?
          $descendant_delimeter = strrpos($query, "::");
          $isChild = substr($query, $descendant_delimeter-5, 5) == "child";
          $el = substr($query, $descendant_delimeter+2);
          $query = substr($query, 0, strrpos($query, "/")) . ($isChild ? "/" : "//") . $el;

          $pseudo_classes[$tok] = true;
          $p = $i + 1;
          $nth = trim(mb_substr($selector, $p, strpos($selector, ")", $i) - $p));
          // 1
          if (preg_match("/^\d+$/", $nth)) {;
              $condition = "position() = $nth";
          } // odd
          elseif ($nth === "odd") {
              $condition = "(position() mod 2) = 1";
          } // even
          elseif ($nth === "even") {
              $condition = "(position() mod 2) = 0";
          } // an+b
          else {
              $condition = $this->_selector_an_plus_b($nth, $last);
          }
          $query .= "[$condition]";
          $tok = "";
          break;

        case "nth-child":
          //FIXME: this fix-up is pretty ugly, would parsing the selector in reverse work better generally?
          $descendant_delimeter = strrpos($query, "::");
          $isChild = substr($query, $descendant_delimeter-5, 5) == "child";
          $el = substr($query, $descendant_delimeter+2);
          $query = substr($query, 0, strrpos($query, "/")) . ($isChild ? "/" : "//") . "*";
          $pseudo_classes[$tok] = true;
          $p = $i + 1;
          $nth = trim(mb_substr($selector, $p, strpos($selector, ")", $i) - $p));
          // 1
          if (preg_match("/^\d+$/", $nth)) {
              $condition = "position() = $nth";
          } // odd
          elseif ($nth === "odd") {
              $condition = "(position() mod 2) = 1";
          } // even
          elseif ($nth === "even") {
              $condition = "(position() mod 2) = 0";
          } // an+b
          else {
              $condition = $this->_selector_an_plus_b($nth, $last);
          }
          $query .= "[$condition]";
          if ($el != "*") {
              $query .= "[name() = '$el']";
          }
          $tok = "";
          break;

        //TODO: bit of a hack attempt at matches support, currently only matches against elements
        case "matches":
          $pseudo_classes[$tok] = true;
          $p = $i + 1;
          $matchList = trim(mb_substr($selector, $p, strpos($selector, ")", $i) - $p));
          // Tag names are case-insensitive
          $elements = array_map("trim", explode(",", strtolower($matchList)));
          foreach ($elements as &$element) {
              $element = "name() = '$element'";
          }
          $query .= "[" . implode(" or ", $elements) . "]";
          $tok = "";
          break;

        case "link":
          $query .= "[@href]";
          $tok = "";
          break;

        case "first-line": // TODO
        case "first-letter": // TODO

        // N/A
        case "active":
        case "visited":
          $query .= "[@dummy]";
          $tok = "";
          break;

        /* Pseudo-elements */
        case "before":
        case "after":
          if ( $first_pass )
            $pseudo_elements[$tok] = $tok;
          else
            $query .= "/*[@$tok]";

          $tok = "";
          break;

        case "empty":
          $query .= "[not(*) and not(normalize-space())]";
          $tok = "";
          break;

        case "disabled":
        case "checked":
          $query .= "[@$tok]";
          $tok = "";
          break;

        case "enabled":
          $query .= "[not(@disabled)]";
          $tok = "";
          break;
        }

        break;

      case "[":
        // Attribute selectors.  All with an attribute matching the following token(s)
        $attr_delimiters = array("=", "]", "~", "|", "$", "^", "*");
        $tok_len = mb_strlen($tok);
        $j = 0;

        $attr = "";
        $op = "";
        $value = "";

        while ( $j < $tok_len ) {
          if ( in_array($tok[$j], $attr_delimiters) )
            break;
          $attr .= $tok[$j++];
        }

        switch ( $tok[$j] ) {

        case "~":
        case "|":
        case "$":
        case "^":
        case "*":
          $op .= $tok[$j++];

          if ( $tok[$j] !== "=" )
            throw new \Exception("Invalid CSS selector syntax: invalid attribute selector: $selector");

          $op .= $tok[$j];
          break;

        case "=":
          $op = "=";
          break;

        }

        // Read the attribute value, if required
        if ( $op != "" ) {
          $j++;
          while ( $j < $tok_len ) {
            if ( $tok[$j] === "]" )
              break;
            $value .= $tok[$j++];
          }
        }

        if ( $attr == "" )
          throw new \Exception("Invalid CSS selector syntax: missing attribute name");

        $value = trim($value, "\"'");

        switch ( $op ) {

        case "":
          $query .=  "[@$attr]";
          break;

        case "=":
          $query .= "[@$attr=\"$value\"]";
          break;

        case "~=":
          // FIXME: this will break if $value contains quoted strings
          // (e.g. [type~="a b c" "d e f"])
          $values = explode(" ", $value);
          $query .=  "[";

          foreach ( $values as $val )
            $query .= "@$attr=\"$val\" or ";

          $query = rtrim($query, " or ") . "]";
          break;

        case "|=":
          $values = explode("-", $value);
          $query .= "[";

          foreach ( $values as $val )
            $query .= "starts-with(@$attr, \"$val\") or ";

          $query = rtrim($query, " or ") . "]";
          break;

        case "$=":
          $query .= "[substring(@$attr, string-length(@$attr)-".(strlen($value) - 1).")=\"$value\"]";
          break;
          
        case "^=":
          $query .= "[starts-with(@$attr,\"$value\")]";
          break;
          
        case "*=":
          $query .= "[contains(@$attr,\"$value\")]";
          break;
        }

        break;
      }
    }
    $i++;

//       case ":":
//         // Pseudo selectors: ignore for now.  Partially handled directly
//         // below.

//         // Skip until the next special character, leaving the token as-is
//         while ( $i < $len ) {
//           if ( in_array($selector[$i], $delimiters) )
//             break;
//           $i++;
//         }
//         break;

//       default:
//         // Add the character to the token
//         $tok .= $selector[$i++];
//         break;
//       }

//    }


    // Trim the trailing '/' from the query
    if ( mb_strlen($query) > 2 )
      $query = rtrim($query, "/");

    return array("query" => $query, "pseudo_elements" => $pseudo_elements);
  }

  /**
   * applies all current styles to a particular document tree
   *
   * apply_styles() applies all currently loaded styles to the provided
   * {@link FrameParser_Tree}.  Aside from parsing CSS, this is the main purpose
   * of this class.
   *
   * @param FrameParser_Tree $tree
   */
  function apply_styles(FrameParser_Tree $tree) {

    // Use XPath to select nodes.  This would be easier if we could attach
    // FrameParser objects directly to DOMNodes using the setUserData() method, but
    // we can't do that just yet.  Instead, we set a _node attribute_ in
    // FrameParser->set_id() and use that as a handle on the FrameParser object via
    // FrameParser_Tree::$_registry.

    // We create a scratch array of styles indexed by frame id.  Once all
    // styles have been assigned, we order the cached styles by specificity
    // and create a final style object to assign to the frame.

    // FIXME: this is not particularly robust...

    $styles = array();
    $xp = new \DOMXPath($tree->get_dom());

    // Add generated content
    foreach ($this->_styles as $selector => $style) {
      if (strpos($selector, ":before") === false &&
          strpos($selector, ":after") === false) continue;

      $query = $this->_css_selector_to_xpath($selector, true);

      // Retrieve the nodes
      $nodes = @$xp->query($query["query"]);
      if ($nodes == null) {
        record_warnings_parser(E_USER_WARNING, "The CSS selector '$selector' is not valid", __FILE__, __LINE__);
        continue;
      }

      foreach ($nodes as $i => $node) {
        foreach ($query["pseudo_elements"] as $pos) {
          if (($src = $this->_image($style->content)) !== "none") {
            $new_node = $node->ownerDocument->createElement("img_generated");
            $new_node->setAttribute("src", $src);
          }
          else {
            $new_node = $node->ownerDocument->createElement("dompdf_generated");
          }
          $new_node->setAttribute($pos, $pos);
          
          $tree->insert_node($node, $new_node, $pos);
        }
      }
    }

    // Apply all styles in stylesheet
    foreach ($this->_styles as $selector => $style) {
      $query = $this->_css_selector_to_xpath($selector);

      // Retrieve the nodes
      $nodes = @$xp->query($query["query"]);
      if ($nodes == null) {
        record_warnings_parser(E_USER_WARNING, "The CSS selector '$selector' is not valid", __FILE__, __LINE__);
        continue;
      }

      foreach ($nodes as $node) {
        // Retrieve the node id
        if ( $node->nodeType != XML_ELEMENT_NODE ) // Only DOMElements get styles
          continue;

        $id = $node->getAttribute("frame_id");

        // Assign the current style to the scratch array
        $spec = $this->_specificity($selector);
        $styles[$id][$spec][] = $style;
      }
    }

    // Now create the styles and assign them to the appropriate frames.  (We
    // iterate over the tree using an implicit FrameParser_Tree iterator.)
    $root_flg = false;
    foreach ($tree->get_frames() as $frame) {
      // pre_r($frame->get_node()->nodeName . ":");
      if ( !$root_flg && $this->_page_style ) {
        $style = $this->_page_style;
        $root_flg = true;
      } else
        $style = $this->create_style();

      // Find nearest DOMElement parent
      $p = $frame;
      while ( $p = $p->get_parent() )
        if ($p->get_node()->nodeType == XML_ELEMENT_NODE )
          break;

      // StyleParsers can only be applied directly to DOMElements; anonymous
      // frames inherit from their parent
      if ( $frame->get_node()->nodeType != XML_ELEMENT_NODE ) {
        if ( $p )
          $style->inherit($p->get_style());
        $frame->set_style($style);
        continue;
      }

      $id = $frame->get_id();

      // Handle HTML 4.0 attributes
      Attribute_Translator_Parser::translate_attributes($frame);

      // Locate any additional style attributes
      if ( ($str = $frame->get_node()->getAttribute("style")) !== "" ) {
        // Destroy CSS comments
        $str = preg_replace("'/\*.*?\*/'si", "", $str);

        $spec = $this->_specificity("!style attribute");
        $styles[$id][$spec][] = $this->_parse_properties($str);
      }

      // Grab the applicable styles
      if ( isset($styles[$id]) ) {

        $applied_styles = $styles[ $frame->get_id() ];

        // Sort by specificity
        ksort($applied_styles);

        // Merge the new styles with the inherited styles
        foreach ($applied_styles as $arr) {
          foreach ($arr as $s)
            $style->merge($s);
        }
      }

      // Inherit parent's styles if required
      if ( $p ) {

        $style->inherit( $p->get_style() );
      }

      $frame->set_style($style);

    }

    // We're done!  Clean out the registry of all styles since we
    // won't be needing this later.
    foreach ( array_keys($this->_styles) as $key ) {
      $this->_styles[$key] = null;
      unset($this->_styles[$key]);
    }

  }

    protected function _selector_an_plus_b($expr, $last = false)
    {
        $expr = preg_replace("/\s/", "", $expr);
        if (!preg_match("/^(?P<a>-?[0-9]*)?n(?P<b>[-+]?[0-9]+)?$/", $expr, $matches)) {
            return "false()";
        }
        $a = ((isset($matches["a"]) && $matches["a"] !== "") ? intval($matches["a"]) : 1);
        $b = ((isset($matches["b"]) && $matches["b"] !== "") ? intval($matches["b"]) : 0);

        $position = ($last ? "(last()-position())" : "position()");
        if ($b == 0) {
            return "($position mod $a) = 0";
        } else {
            $compare = (($a < 0) ? "<=" : ">=");
            $b2 = -$b;
            if ($b2 >= 0) {
                $b2 = "+$b2";
            }
            return "($position $compare $b) and ((($position $b2) mod " . abs($a) . ") = 0)";
        }
    }


  /**
   * parse a CSS string using a regex parser
   *
   * Called by {@link StyleParsersheet::parse_css()}
   *
   * @param string $str
   */
  private function _parse_css($str) {

    $str = trim($str);

    // Destroy comments and remove HTML comments
    $css = preg_replace(array(
      "'/\*.*?\*/'si",
      "/^<!--/",
      "/-->$/"
    ), "", $str);

    // remove CDATA comments
    $css = str_replace('<![CDATA[', '', $css);
    $css = str_replace(']]>', '', $css);

    // FIXME: handle '{' within strings, e.g. [attr="string {}"]

    // Something more legible:
    $re =
      "/\s*                                   # Skip leading whitespace                             \n".
      "( @([^\s]+)\s+([^{;]*) (?:;|({)) )?    # Match @rules followed by ';' or '{'                 \n".
      "(?(1)                                  # Only parse sub-sections if we're in an @rule...     \n".
      "  (?(4)                                # ...and if there was a leading '{'                   \n".
      "    \s*( (?:(?>[^{}]+) ({)?            # Parse rulesets and individual @page rules           \n".
      "            (?(6) (?>[^}]*) }) \s*)+?  \n".
      "       )                               \n".
      "   })                                  # Balancing '}'                                \n".
      "|                                      # Branch to match regular rules (not preceeded by '@')\n".
      "([^{]*{[^}]*}))                        # Parse normal rulesets\n".
      "/xs";

    if ( preg_match_all($re, $css, $matches, PREG_SET_ORDER) === false )
      // An error occured
      throw new \Exception("Error parsing css file: preg_match_all() failed.");

    // After matching, the array indicies are set as follows:
    //
    // [0] => complete text of match
    // [1] => contains '@import ...;' or '@media {' if applicable
    // [2] => text following @ for cases where [1] is set
    // [3] => media types or full text following '@import ...;'
    // [4] => '{', if present
    // [5] => rulesets within media rules
    // [6] => '{', within media rules
    // [7] => individual rules, outside of media rules
    //
    //pre_r($matches);
    foreach ( $matches as $match ) {
      $match[2] = trim($match[2]);

      if ( $match[2] !== "" ) {
        // Handle @rules
        switch ($match[2]) {

        case "import":
          $this->_parse_import($match[3]);
          break;

        case "media":
          $acceptedmedia = self::$ACCEPTED_GENERIC_MEDIA_TYPES;
          if ( defined("PARSERHTML_DEFAULT_MEDIA_TYPE") ) {
            $acceptedmedia[] = PARSERHTML_DEFAULT_MEDIA_TYPE;
          } else {
            $acceptedmedia[] = self::$ACCEPTED_DEFAULT_MEDIA_TYPE;
          }
          if ( in_array(mb_strtolower(trim($match[3])), $acceptedmedia ) ) {
            $this->_parse_sections($match[5]);
          }
          break;

        case "page":
          //This handles @page to be applied to page oriented media
          //Note: This has a reduced syntax:
          //@page { margin:1cm; color:blue; }
          //Not a sequence of styles like a full.css, but only the properties
          //of a single style, which is applied to the very first "root" frame before
          //processing other styles of the frame.
          //Working properties:
          // margin (for margin around edge of paper)
          // font-family (default font of pages)
          // color (default text color of pages)
          //Non working properties:
          // border
          // padding
          // background-color
          //Todo:Reason is unknown
          //Other properties (like further font or border attributes) not tested.
          //If a border or background color around each paper sheet is desired,
          //assign it to the <body> tag, possibly only for the css of the correct media type.

          // If the page has a name, skip the style.
          if ($match[3] !== "")
            return;

          // Store the style for later...
          if ( is_null($this->_page_style) )
            $this->_page_style = $this->_parse_properties($match[5]);
          else
            $this->_page_style->merge($this->_parse_properties($match[5]));
          break;

        case "font-face":
          $this->_parse_font_face($match[5]);
          break;

        default:
          // ignore everything else
          break;
        }

        continue;
      }

      if ( $match[7] !== "" )
        $this->_parse_sections($match[7]);

    }
  }

  /* See also style.cls StyleParser::_image(), refactoring?, works also for imported css files */
  protected function _image($val) {

    if ( mb_strpos($val, "url") === false ) {
      $path = "none"; //Don't resolve no image -> otherwise would prefix path and no longer recognize as none
    }
    else {
      $val = preg_replace("/url\(['\"]?([^'\")]+)['\"]?\)/","\\1", trim($val));

      // Resolve the url now in the context of the current stylesheet
      $parsed_url = explode_url_parser($val);
      if ( $parsed_url["protocol"] == "" && $this->get_protocol() == "" ) {
        if ($parsed_url["path"][0] === '/' || $parsed_url["path"][0] === '\\' ) {
          $path = $_SERVER["DOCUMENT_ROOT"].'/';
        } else {
          $path = $this->get_base_path();
        }
        $path .= $parsed_url["path"] . $parsed_url["file"];
        $path = realpath($path);
        // If realpath returns FALSE then specifically state that there is no background image
        // FIXME: Is this causing problems for imported CSS files? There are some './none' references when running the test cases.
        if (!$path) { $path = 'none'; }
      } else {
        $path = build_url_parser($this->get_protocol(),
                          $this->get_host(),
                          $this->get_base_path(),
                          $val);
      }
    }

    return $path;
  }

  /**
   * parse @import{} sections
   *
   * @param string $url  the url of the imported CSS file
   */
  private function _parse_import($url) {
    $arr = preg_split("/[\s\n,]/", $url,-1, PREG_SPLIT_NO_EMPTY);
    $url = array_shift($arr);
    $accept = false;

    if ( count($arr) > 0 ) {

      $acceptedmedia = self::$ACCEPTED_GENERIC_MEDIA_TYPES;
      if ( defined("PARSERHTML_DEFAULT_MEDIA_TYPE") ) {
        $acceptedmedia[] = PARSERHTML_DEFAULT_MEDIA_TYPE;
      } else {
        $acceptedmedia[] = self::$ACCEPTED_DEFAULT_MEDIA_TYPE;
      }

      // @import url media_type [media_type...]
      foreach ( $arr as $type ) {
        if ( in_array(mb_strtolower(trim($type)), $acceptedmedia) ) {
          $accept = true;
          break;
        }
      }

    } else {
      // unconditional import
      $accept = true;
    }

    if ( $accept ) {
      // Store our current base url properties in case the new url is elsewhere
      $protocol = $this->_protocol;
      $host = $this->_base_host;
      $path = $this->_base_path;

      // $url = str_replace(array('"',"url", "(", ")"), "", $url);
      // If the protocol is php, assume that we will import using file://
      // $url = build_url_parser($protocol == "php://" ? "file://" : $protocol, $host, $path, $url);
      // Above does not work for subfolders and absolute urls.
      // Todo: As above, do we need to replace php or file to an empty protocol for local files?

      $url = $this->_image($url);

      $this->load_css_file($url);

      // Restore the current base url
      $this->_protocol = $protocol;
      $this->_base_host = $host;
      $this->_base_path = $path;
    }

  }

  /**
   * parse @font-face{} sections
   * http://www.w3.org/TR/css3-fonts/#the-font-face-rule
   *
   * @param string $str CSS @font-face rules
   * @return StyleParser
   */
  private function _parse_font_face($str) {
    $descriptors = $this->_parse_properties($str);

    preg_match_all("/(url|local)\s*\([\"\']?([^\"\'\)]+)[\"\']?\)\s*(format\s*\([\"\']?([^\"\'\)]+)[\"\']?\))?/i", $descriptors->src, $src);

    $sources = array();
    foreach($src[0] as $i => $value) {
      $sources[] = array(
        "local"  => strtolower($src[1][$i]) === "local",
        "uri"    => $src[2][$i],
        "format" => $src[4][$i],
      );
    }

    //@todo download font file, ttf2afm, etc
  }

  /**
   * parse regular CSS blocks
   *
   * _parse_properties() creates a new StyleParser object based on the provided
   * CSS rules.
   *
   * @param string $str  CSS rules
   * @return StyleParser
   */
  private function _parse_properties($str) {
    $properties = preg_split("/;(?=(?:[^\(]*\([^\)]*\))*(?![^\)]*\)))/", $str);

    // Create the style
    $style = new StyleParser($this);
    foreach ($properties as $prop) {
      // If the $prop contains an url, the regex may be wrong
      // @todo: fix the regex so that it works everytime
      /*if (strpos($prop, "url(") === false) {
        if (preg_match("/([a-z-]+)\s*:\s*[^:]+$/i", $prop, $m))
          $prop = $m[0];
      }*/
      //A css property can have " ! important" appended (whitespace optional)
      //strip this off to decode core of the property correctly.
      //Pass on in the style to allow proper handling:
      //!important properties can only be overridden by other !important ones.
      //$style->$prop_name = is a shortcut of $style->__set($prop_name,$value);.
      //If no specific set function available, set _props["prop_name"]
      //style is always copied completely, or $_props handled separately
      //Therefore set a _important_props["prop_name"]=true to indicate the modifier

      /* Instead of short code, prefer the typical case with fast code
      $important = preg_match("/(.*?)!\s*important/",$prop,$match);
      if ( $important ) {
        $prop = $match[1];
      }
      $prop = trim($prop);
      */

      $important = false;
      $prop = trim($prop);
      if (substr($prop,-9) === 'important') {
        $prop_tmp = rtrim(substr($prop,0,-9));
        if (substr($prop_tmp,-1) === '!') {
          $prop = rtrim(substr($prop_tmp,0,-1));
          $important = true;
        }
      }

      if ($prop == "") {
        continue;
      }

      $i = mb_strpos($prop, ":");
      if ( $i === false ) {
        continue;
      }

      $prop_name = rtrim(mb_strtolower(mb_substr($prop, 0, $i)));
      $value = ltrim(mb_substr($prop, $i+1));

      //New style, anyway empty
      //if ($important || !$style->important_get($prop_name) ) {
      //$style->$prop_name = array($value,$important);
      //assignment might be replaced by overloading through __set,
      //and overloaded functions might check _important_props,
      //therefore set _important_props first.
      if ($important) {
        $style->important_set($prop_name);
      }
      //For easier debugging, don't use overloading of assignments with __set
      $style->$prop_name = $value;
      //$style->props_set($prop_name, $value);
    }

    return $style;
  }

  /**
   * parse selector + rulesets
   *
   * @param string $str  CSS selectors and rulesets
   */
  private function _parse_sections($str) {
    // Pre-process: collapse all whitespace and strip whitespace around '>',
    // '.', ':', '+', '#'

    $patterns = array("/[\\s\n]+/", "/\\s+([>.:+#])\\s+/");
    $replacements = array(" ", "\\1");
    $str = preg_replace($patterns, $replacements, $str);

    $sections = explode("}", $str);

    foreach ($sections as $sect) {
      $i = mb_strpos($sect, "{");

      $selectors = explode(",", mb_substr($sect, 0, $i));

      $style = $this->_parse_properties(trim(mb_substr($sect, $i+1)));

      // Assign it to the selected elements
      foreach ($selectors as $selector) {
        $selector = trim($selector);

        if ($selector == "") {
          continue;
        }

        $this->add_style($selector, $style);
      }
    }
  }

}
