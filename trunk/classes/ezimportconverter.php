<?php

/**
 * File containing the eZImportConverter class.
 *
 * @package import
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
class eZImportConverter
{
    public $filters = array();

    function eZImportConverter( $text )
    {
        $this->text = &$text;
    }

    function addFilter( $name )
    {
        $this->filters[] = $name;
    }

    function run()
    {
        foreach ( $this->filters as $filter )
        {
            $impl = $this->filterInstance( $filter );
            $return = $this->recursiveFilter( $this->text, $impl );
        }
        return $return;
    }

    function recursiveFilter( $data, $filter )
    {
        if ( is_array( $data ) )
        {
            foreach ( $data as $key => $row )
            {
                if ( is_array( $data[$key] ) )
                    $data[$key] = $this->recursiveFilter( $row );
                else
                    $data[$key] = $filter->filter( $row );
            }
        }
        else
            $data = $filter->filter( $data );
        return $data;
    }

    function filterInstance( $handlerName )
    {
        $handlerClassName = $handlerName . 'filter';
        if ( class_exists( $handlerClassName ) )
        {
            $handler = new $handlerClassName( );
            return $handler;
        }
        else
            return false;
    }
}

?>