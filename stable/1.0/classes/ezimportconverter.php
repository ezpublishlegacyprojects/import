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
	var $filters = array();
	function eZImportConverter( $text )
	{
		$this->text = &$text;
	}
	function addFilter( $name )
	{
		$this->filters[] = $name; 
	}
	function &run()
	{
		foreach ( $this->filters as $filter )
		{
			$impl = $this->filterInstance( $filter );
			$this->text = $impl->recursiveFilter( $this->text );
		}
		return $this->text;
	}
	function recursiveFilter( $data )
	{
	    if( is_array( $data ) )
	    {
	        foreach ( $data as $key => $row  )
	        {
	            if( is_array( $data[$key] ) )
	               $data[$key] = $this->recursiveFilter( $row );
	            else
	               $data[$key] = $this->filter( $row );
	        }
	    }
	    else
	       $data = $this->filter( $data );
	    return $data;
	}
	function filterInstance( $handlerName )
	{
		if ( eZExtension::findExtensionType( array( 'ini-name' => 'import.ini',
                                                    'repository-group' => 'ImportSettings',
                                                    'repository-variable' => 'FilterRepositoryDirectories',
                                                    'extension-group' => 'ImportSettings',
                                                    'extension-variable' => 'FilterExtensionDirectories',
                                                    'subdir' => 'importfilters',
                                                    'extension-subdir' => 'importfilters',
                                                    'suffix-name' => 'filter.php',
                                                    'type-directory' => false,
                                                    'type' => $handlerName,
                                                    'alias-group' => 'ImportSettings',
                                                    'alias-variable' => 'FilterAlias' ),
                                             $result ) )
        {
			$handlerFile = $result['found-file-path'];
            if ( file_exists( $handlerFile ) )
            {
                include_once( $handlerFile );
                $handlerClassName = $result['type'] . 'Filter';
                    $handler =& new $handlerClassName;
            
            }
            else
            {
            	$handler =& new eZImportFilter();
            }
		}
		return $handler;
	}
}

class eZImportFilter
{
	function eZImportFilter()
	{
		
	}
	function filter( $text )
	{
		return $text;
	}
}
?>