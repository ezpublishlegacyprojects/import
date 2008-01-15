<?php

/**
 * File containing the htmlparserfilter class.
 *
 * @package import
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
// this parser should be used for ezxml datatype
include_once( "kernel/classes/datatypes/ezxmltext/handlers/input/ezsimplifiedxmlinputparser.php" );
class htmlparserfilter extends eZImportConverter
{
	function htmlparserfilter()
	{
		
	}
	// return a domdocument with ezxml
	function filter ( &$data )
	{
        $parser = new eZSimplifiedXMLInputParser( );
        $parser->setParseLineBreaks( true );
        $data = $parser->process( $data );
		return $data;
	}
}
?>