<?php

/**
 * File ezimportprocess.php
 *
 * @package import
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */

class eZImportProcess
{
    public $options;
    public $namespace;

    function eZImportProcess()
    {
    
    }

    function &instance( $handlerName, $options )
    {
        $handlerClassName = $handlerName . 'ImportProcess';
        if ( class_exists( $handlerClassName ) )
        {
            $handler = new $handlerClassName( );
            $handler->setOptions( $options );
            return $handler;
        }
        else
        {
            throw new Exception( "eZImportProcess named " . $handlerName . " not found." );
        }
    }

    function setNamespace( $namespace )
    {
        $this->namespace = $namespace;
    }

    function setOptions( $array = array () )
    {
        $this->options = $array;
    }

    public function run( &$data, $namespace, $options )
    {
    
    }
}

?>