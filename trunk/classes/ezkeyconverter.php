<?php

/**
 * File containing the eZKeyConverter class.
 *
 * @package import
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
class eZKeyConverter
{
    public $data = array();
    public $defaults = array();

    public function setDefault( $namespace, $value )
    {
        $this->defaults[$namespace] = $value;
    }

    static function generateKey( $old )
    {
        // @TODO $old should accept arrays too
        return md5( $old );
    }

    public function convert( $namespaces, $old )
    {
        if ( ! is_array( $namespaces ) )
        {
            $namespaces = array( 
                $namespaces 
            );
        }
        $hash = eZKeyConverter::generateKey( $old );
        foreach ( $namespaces as $namespace )
        {
            if ( array_key_exists( $namespace, $this->data ) and array_key_exists( $hash, $this->data[$namespace] ) )
            {
                return $this->data[$namespace][$hash];
            }
        }
        foreach ( $namespaces as $namespace )
        {
            if ( array_key_exists( $namespace, $this->defaults ) )
            {
                return $this->defaults[$namespace];
            }
        }
        return false;
    }

    public function hasRegister( $namespace = false, $old = false )
    {
        if ( $namespace and $old )
        {
            $hash = self::generateKey( $old );
            if ( array_key_exists( $namespace, $this->data ) and array_key_exists( $hash, $this->data[$namespace] ) )
                return true;
        }
        return false;
    }

    public function register( $namespace, $old, $new, $force = false )
    {
        if ( ! $old or ! $new )
            return false;
        
        $hash = self::generateKey( $old );
        $this->data[$namespace][$hash] = $new;
    }

    //does something to cleanup this mess
    public function reset()
    {
        $this->data = array();
    }

    static function &instance()
    {
        if ( array_key_exists( "eZKeyConverter", $GLOBALS ) and ( $GLOBALS["eZKeyConverter"] ) )
        {
            $impl = & $GLOBALS["eZKeyConverter"];
        }
        else
        {
            $impl = & $GLOBALS['eZKeyConverter'];
            $impl = new eZKeyConverter( );
            $GLOBALS["eZKeyConverter"] = & $impl;
        }
        return $impl;
    }
}
?>