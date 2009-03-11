<?php
/**
 * File containing the eZImportFramework class.
 *
 * @package import
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
class eZImportFramework
{
    /**
     * Constants
     */
    const PRESERVED_KEY_NODE_ID = "node_id";
    const PRESERVED_KEY_OWNER_ID = "owner_id";
    const PRESERVED_KEY_PARENT_NODE_ID = "parentNodeID";
    const PRESERVED_KEY_CLASS_ID = "contentClassID";
    const PRESERVED_KEY_CLASS = "contentClass";
    const PRESERVED_KEY_CREATION_TIMESTAMP = "creation_timestamp";
    const PRESERVED_KEY_MODIFICATION_TIMESTAMP = "modification_timestamp";
    const PRESERVED_KEY_REMOTE_ID = "remote_id";
    const REMOTE_ID_TAG = "ezimport";
    const LANGUAGE_TAG = "language";
    // typ of importing Data
    //-------------------------------------------------------
    const METHOD = "import_method";
    // Values of EZ_IMPORT_METHOD
    // updaten if possible
    const METHOD_AUTO = "auto";
    const METHOD_UPDATE = "update";
    // never update records always create
    const METHOD_NO_UPDATE = "always_create";
    const METHOD_ALWAYS_CREATE = "always_create";
    // if record exists - no update - no creation - only return node
    const METHOD_NO_UPDATE_IF_EXIST = "no_update_if_exist";
    public $processHandler;
    public $data;
    public $eZKeyConverter;
    public $source;
    public $namespaces = array( );

    function __construct()
    {
        $this->eZKeyConverter = eZKeyConverter::instance();
        if ( ! $GLOBALS['eZImportFrameworkEnabled'] )
        {
            $GLOBALS['eZImportFrameworkEnabled'] = true;
            eZImportFramework::log( 
                "--------------------------------" );
            eZImportFramework::log( 
                "STARTING IMPORT" );
            eZImportFramework::log( 
                "--------------------------------" );
        }
    }

    function destroy()
    {
        $this->freeMem();
        $GLOBALS['eZImportFrameworkEnabled'] = false;
        eZImportFramework::log( 
            "--------------------------------" );
        eZImportFramework::log( 
            "STOPING IMPORT" );
        eZImportFramework::log( 
            "--------------------------------" );
    }

    function setDataSource ( &$source, $parameters = array() )
    {
        $this->source = & $source;
        $this->parameters = & $parameters;
    }

    function freeMem ()
    {
        unset( 
            $this->data );
    }

    //does something to cleanup this mess
    function reset ()
    {
    }

    static function &instance ( $handlerName = 'Default' )
    {
		$handlerClassName = $handlerName . 'ImportHandler';
        if ( class_exists( $handlerClassName ) )
        {
        	return new $handlerClassName( );
        }
		throw new Exception( "Import handler (" . $handlerClassName . ") not found." );
    }

    function getData ( $namespace )
    {
        $this->namespaces[] = $namespace;
        $this->namespaces = array_unique( 
            $this->namespaces );
        if ( is_object( 
            $this->source ) and is_a( 
            $this->source, 
            'eZDBInterface' ) )
        {
            $this->data[$namespace] = & $this->source->arrayQuery( 
                "SELECT * FROM " . $namespace );
        }
    }

    function processData ( $namespace = false, $options = false )
    {
        if ( $namespace and array_key_exists( 
            $namespace, 
            $this->data ) )
            $data = & $this->data[$namespace]; else
            $data = & $this->data;
        for ( $i = 0; $i < count( 
            $data ); $i ++ )
        {
            if ( array_key_exists( 
                'map', 
                $options ) )
            {
                unset( 
                    $new );
                $new = array( );
                foreach ( $this->data[$namespace][$i] as $key => $value )
                {
                    if ( array_key_exists( 
                        $key, 
                        $options['map'] ) )
                    {
                        $new[$options['map'][$key]] = $this->data[$namespace][$i][$key];
                    }
                }
                $this->data[$namespace][$i]['data'] = $new;
            }
        }
    }

    function log ( $log )
    {
        eZLog::write( 
            $log, 
            "import.log" );
    }

    function importData ( $processHandler, $namespace = false, $options = array() )
    {
        $result = null;
        if ( array_key_exists( 
            "access", 
            $options ) )
            $this->changeSiteAccess( 
                $options['access'] );
        $processHandlerImp = eZImportProcess::instance( 
            $processHandler );
        $processHandlerImp->setOptions( 
            $options );
        if ( $namespace and !$options['ignore_data_namespace'] )
        {
            $processHandlerImp->setNamespace( $namespace );
            $result = $processHandlerImp->run( $this->data[$namespace], $namespace );
        }
        elseif ( $namespace and $options['ignore_data_namespace'] )
        {
            $processHandlerImp->setNamespace( 
                $namespace );
            $result = $processHandlerImp->run( 
                $this->data, 
                $namespace );
        }
        else
        {
            $processHandlerImp->setNamespace( 
                null );
            $result = $processHandlerImp->run( 
                $this->data, 
                null );
        }
        if ( array_key_exists( 
            "access", 
            $options ) )
            $this->resetSiteAccess();
        unset( 
            $processHandlerImp );

        return $result;
    }

    //staic
    function changeSiteAccess ( $name )
    {
        $GLOBALS['eZImportOldAccess'] = $GLOBALS['eZCurrentAccess'];
        $GLOBALS['eZImportOldDefaultLanguage'] = $GLOBALS['eZContentObjectDefaultLanguage'];
        $access = array( 
            'name' => $name , 
            'type' => EZ_ACCESS_TYPE_STATIC );
        $access = changeAccess( 
            $access );
        $GLOBALS['eZCurrentAccess'] = & $access;
        $ini = & eZINI::instance();
        $GLOBALS['eZContentObjectDefaultLanguage'] = $ini->variable( 
            'RegionalSettings', 
            'ContentObjectLocale' );
    }

    //static
    function resetSiteAccess ()
    {
        $GLOBALS['eZContentObjectDefaultLanguage'] = $GLOBALS['eZImportOldDefaultLanguage'];
        $access = $GLOBALS['eZImportOldAccess'];
        $access = changeAccess( 
            $access );
        $GLOBALS['eZCurrentAccess'] = & $access;
    }
}
?>
