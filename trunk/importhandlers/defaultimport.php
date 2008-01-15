<?php
/**
 * File containing the DefaultImportHandler class.
 *
 * @package import
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
    
class DefaultImportHandler extends eZImportFramework 
{
    function DefaultImport( $processHandler )
    {
        parent::eZImportFramework( $processHandler );
    }
  
    
    function getData( $dataArr, $namespace = false )
    {
    	 if ( $namespace )
            $this->data[$namespace] = $dataArr;
        else
            $this->data = $dataArr;
    }
}
?>