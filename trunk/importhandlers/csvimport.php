<?php

/**
 * File containing the CSVImportHandler class.
 *
 * @package import
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */

require_once ( "File/CSV.php" );

class CSVImportHandler extends eZImportFramework
{

    function CSVImport( $processHandler )
    {
        parent::eZImportFramework( $processHandler );
    }

    function getData( $file, $namespace = false )
    {
        if ( ! file_exists( $file ) )
        {
            $cli = eZCLI::instance();
            $cli->output( "No such file '" . $file . "'" );
            return false;
        }
        $conf = File_CSV::discoverFormat( $file );
        
        while ( $row = File_CSV::read( $file, $conf ) )
        {
            if ( ! empty( $row ) )
                $fields[] = $row;
        }

        $meta = array_shift( $fields );
        for ( $i = 0; count( $meta ) > $i; $i ++ )
        {
            $meta[$i] = utf8_decode( $meta[$i] );
            $meta[$i] = str_replace( '?', '', $meta[$i] );
            $meta[$i] = str_replace( '"', '', $meta[$i] );
            $meta[$i] = str_replace( "'", '', $meta[$i] );
        }

        for ( $i = 0; $i <= count( $fields ); $i ++ )
        {
            if ( count( $fields[0] ) > 0 )
            {
                for ( $j = 0; $j < count( $fields[$i] ); $j ++ )
                {
                    if ( trim( $fields[$i][$j] ) )
                    {
                        $result[$i][strtolower( trim( $meta[$j] ) )] = trim( $fields[$i][$j] );
                        //the PEAR CSV does not handle csv escape chars properly, so we try to fix it
                        $result[$i][strtolower( trim( $meta[$j] ) )] = str_replace( '""', '"', $result[$i][strtolower( trim( $meta[$j] ) )] );
                    }
                    else
                    {
                        $result[$i][strtolower( trim( $meta[$j] ) )] = null;
                    }
                }
            }
        }
        if ( $namespace )
        {
            if ( !array_key_exists($namespace,$this->data) or !is_array($this->data[$namespace]))        
            {
                $this->data[$namespace] = array();
            }
            $this->data[$namespace] = array_merge( $this->data[$namespace], $result );
        }
        else
        {
            $this->data = array_merge( $this->data, $result ); 
        }            
    }
}
?>
