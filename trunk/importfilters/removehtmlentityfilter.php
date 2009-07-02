<?php

/**
 * File containing the plaintextfilter class.
 *
 * @package import
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
class removeHTMLEntityfilter extends eZImportFilter
{

    public function filter( $data )
    {
 $data = self::convertSmartQuotes( $data );
$data = html_entity_decode($data, ENT_COMPAT, 'UTF-8' );
        return $data;
    }
static public function convertSmartQuotes($string) 
{ 
    $search = array('&#145;', 
                    '&#146;', 
                    '&#147;', 
                    '&#148;', 
                    '&#151;',
                    '&#132;'
); 
 
    $replace = array("'", 
                     "'", 
                     '"', 
                     '"', 
                     '-',
		       '"'); 
 
    return str_replace($search, $replace, $string); 
} 
}
?>