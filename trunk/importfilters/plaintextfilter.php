<?php

/**
 * File containing the plaintextfilter class.
 *
 * @package import
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
class plaintextfilter extends eZImportFilter
{

    public function filter( $data )
    {
        
        $search = array( 
            '/\<br.*\/>/Ui' , 
            '/\<br.*\>/Ui' 
        );
        
        $replace = array( 
            "\n" , 
            "\n" 
        );
        
        $data = preg_replace( $search, $replace, $data );
        $data = strip_tags( $data );
        $data = self::removeHTMLEntities( $data );
        return $data;
    }

    function removeHTMLEntities( $string )
    {
        // replace numeric entities
        $string = preg_replace( '~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string );
        $string = preg_replace( '~&#([0-9]+);~e', 'chr("\\1")', $string );
        // replace literal entities
        $trans_tbl = get_html_translation_table( HTML_ENTITIES );
        $trans_tbl = array_flip( $trans_tbl );
        return strtr( $string, $trans_tbl );
    }
}
?>