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

class htmlparserfilter extends eZImportConverter
{
	function htmlparserfilter()
	{
		
	}
	// return a domdocument with ezxml
	function filter ( $data )
	{
		$data = str_replace( "\r", '', $data );
        $data = str_replace( "\n", '', $data );
        $data = str_replace( "\t", ' ', $data );
        $parser = new eZSimplifiedXMLInputParser( false, false, 0, false );
        $data = $parser->process( $data );
		return $data;
	}
}
?>