<?php
/**
 * File containing the eZImportFramework class.
 *
 * @package import
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
include_once( 'lib/ezutils/classes/ezextension.php' );
include_once( 'extension/import/classes/ezimportprocess.php' );
include_once( 'extension/import/classes/ezkeyconverter.php' );
include_once( 'extension/import/classes/ezimportconverter.php' );
include_once( "lib/ezutils/classes/ezlog.php" );

class eZImportFramework
{
	var $processHandler;
	var $data;
	var $eZKeyConverter;
	var $source;
	var $namespaces = array();
	function eZImportFramework( )
	{
		$this->eZKeyConverter =& eZKeyConverter::instance();
		if ( !$GLOBALS['eZImportFrameworkEnabled'] )
		{
			$GLOBALS['eZImportFrameworkEnabled'] = true;
			eZImportFramework::log( "--------------------------------" );
			eZImportFramework::log( "STARTING IMPORT" );
			eZImportFramework::log( "--------------------------------" );
		}
	}
	function destroy()
	{
	    $this->freeMem();
		$GLOBALS['eZImportFrameworkEnabled'] = false;
		eZImportFramework::log( "--------------------------------" );
		eZImportFramework::log( "STOPING IMPORT" );
		eZImportFramework::log( "--------------------------------" );
	}
	function setDataSource( &$source, $parameters = array() )
	{
		$this->source =& $source;
		$this->parameters =& $parameters;
	}
	function freeMem()
	{
		unset( $this->data );
	}
	//does something to cleanup this mess
	function reset()
	{
		
	}
	function &instance( $handlerName = 'default' )
	{
		if ( eZExtension::findExtensionType( array( 'ini-name' => 'import.ini.append.php',
                                                    'repository-group' => 'ImportSettings',
                                                    'repository-variable' => 'RepositoryDirectories',
                                                    'extension-group' => 'ImportSettings',
                                                    'extension-variable' => 'ExtensionDirectories',
                                                    'subdir' => 'importhandlers',
                                                    'extension-subdir' => 'importhandlers',
                                                    'suffix-name' => 'import.php',
                                                    'type-directory' => false,
                                                    'type' => $handlerName,
                                                    'alias-group' => 'ImportSettings',
                                                    'alias-variable' => 'HandlerAlias' ),
                                             $result ) )
        {
			$handlerFile = $result['found-file-path'];
            if ( file_exists( $handlerFile ) )
            {
                include_once( $handlerFile );
                $handlerClassName = $result['type'] . 'ImportHandler';
                if ( isset( $handlers[$result['type']] ) )
                {
                    $handler =& $handlers[$result['type']];
                    $handler->reset();
                }
                else
                {
                    $handler =& new $handlerClassName;
                    $handlers[$result['type']] =& $handler;
                }
            }
            else
            {
            	$handler =& new eZImportFramework();
            }
		}
		if ( isset( $handler ) )
		{
		    return $handler;
		}
		else
		{
		    eZDebug::writeError( "Handler not found. Extension not loaded?", "Import" );
		    return false;
		}
	}
	function getData( $namespace )
	{
		$this->namespaces[] = $namespace;
		$this->namespaces = array_unique( $this->namespaces );
		if ( is_object( $this->source ) and is_a( $this->source, 'eZDBInterface' ) )
		{
			
			$this->data[$namespace] =& $this->source->arrayQuery("SELECT * FROM " . $namespace );
		}
	}
	function processData( $namespace = false, $options = false )
	{
		if ( $namespace and array_key_exists( $namespace, $this->data ) )
			$data = & $this->data[$namespace];
		else
			$data = & $this->data;
    	for ( $i=0; $i < count ( $data ) ; $i++ )
    	{
    		if ( array_key_exists( 'map', $options ) )
    		{
    			unset( $new );
    			$new = array();
    			foreach ( $this->data[$namespace][$i] as $key => $value )
    			{
    				if ( array_key_exists( $key, $options['map'] ) )
    				{
    					$new[$options['map'][$key]]=$this->data[$namespace][$i][$key];
    				}
    			}
    			$this->data[$namespace][$i]['data'] = $new;
    		}
    	}
	}
	function log( $log )
	{
		eZLog::write( $log, "import.log" );
	}
	
	function importData( $processHandler,  $namespace = false, $options=array() )
	{
		$result = null;
		
		if ( array_key_exists( "access", $options ) )
			$this->changeSiteAccess( $options['access'] );
		$processHandlerImp = eZImportProcess::instance( $processHandler );
		$processHandlerImp->setOptions( $options );
		 
		if ( $namespace and !$options['ignore_data_namespace'] )
		{
			$processHandlerImp->setNamespace( $namespace );
			$result = $processHandlerImp->run( $this->data[$namespace], $namespace );
		}
		elseif ( $namespace and $options['ignore_data_namespace'] )
		{
			
			$processHandlerImp->setNamespace( $namespace );
			$result = $processHandlerImp->run( $this->data, $namespace );
		}
		else
		{
			$processHandlerImp->setNamespace( null );
			$result = $processHandlerImp->run( $this->data, null );
		}
		if ( array_key_exists( "access", $options ) )
			$this->resetSiteAccess( );
			
		unset( $processHandlerImp );
		
		return $result;
	}
	//staic
	function changeSiteAccess( $name )
	{
		
		$GLOBALS['eZImportOldAccess'] = $GLOBALS['eZCurrentAccess'];
		$GLOBALS['eZImportOldDefaultLanguage'] = $GLOBALS['eZContentObjectDefaultLanguage'];

		$access = array( 'name' => $name,
                         'type' => EZ_ACCESS_TYPE_STATIC );
        $access = changeAccess( $access );
        $GLOBALS['eZCurrentAccess'] =& $access;
        $ini =& eZINI::instance();
		$GLOBALS['eZContentObjectDefaultLanguage'] = $ini->variable( 'RegionalSettings', 'ContentObjectLocale' );
	}
	//static
	function resetSiteAccess()
	{
	    $GLOBALS['eZContentObjectDefaultLanguage'] = $GLOBALS['eZImportOldDefaultLanguage'];
		$access = $GLOBALS['eZImportOldAccess'];
        $access = changeAccess( $access );
        $GLOBALS['eZCurrentAccess'] =& $access;
	}
}
?>
