<?php

/**
 * File containing the plaintextfilter class.
 *
 * @package import
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
class toUTF8filter extends eZImportFilter
{

    public function filter( $data )
    {
 $data = self::convertCharset( $data );
        return $data;
    }
    static public function convertCharset( $mix, $from = 'UTF-8, ISO-8859-1', $to = 'UTF-8' )
    {
if (is_array($mix))
{
foreach( $mix as $key => $value )
{
$mix[$key] = self::convertCharset( $mix[$key], $from, $to );
}
return $mix;
}
elseif( is_string($mix))
{
	return mb_convert_encoding( $mix, $to, mb_detect_encoding( $mix, $from, true ) );
}
    }
}
?>