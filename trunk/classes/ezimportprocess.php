<?php
/**
 * File ezimportprocess.php
 *
 * @package import
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
define( "EZ_IMPORT_PRESERVED_KEY_NODE_ID", "node_id" );
define( "EZ_IMPORT_PRESERVED_KEY_OWNER_ID", "owner_id" );
define( "EZ_IMPORT_PRESERVED_KEY_PARENT_NODE_ID", "parentNodeID" );
define( "EZ_IMPORT_PRESERVED_KEY_CLASS_ID", "contentClassID" );
define( "EZ_IMPORT_PRESERVED_KEY_CLASS", "contentClass" );
define( "EZ_IMPORT_PRESERVED_KEY_CREATION_TIMESTAMP", "creation_timestamp" );
define( "EZ_IMPORT_PRESERVED_KEY_MODIFICATION_TIMESTAMP", "modification_timestamp" );
define( "EZ_IMPORT_PRESERVED_KEY_REMOTE_ID", "remote_id" );
define( "EZ_IMPORT_REMOTE_ID_TAG", "ezimport" );
define( "EZ_IMPORT_LANGUAGE_TAG", "language" );

// typ of importing Data
//-------------------------------------------------------
define("EZ_IMPORT_METHOD", "import_method");

	// Values of EZ_IMPORT_METHOD
	// updaten if possible
	define( "EZ_IMPORT_METHOD_AUTO", "auto" );
	define( "EZ_IMPORT_METHOD_UPDATE", "update" );
	
	// never update records always create
	define( "EZ_IMPORT_METHOD_NO_UPDATE", "always_create" );
	define( "EZ_IMPORT_METHOD_ALWAYS_CREATE", "always_create" );
	
	// if record exists - no update - no creation - only return node
	define( "EZ_IMPORT_METHOD_NO_UPDATE_IF_EXIST", "no_update_if_exist" );
	
//-------------------------------------------------------	

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
        	$handler = new $handlerClassName;
            $handler->setOptions( $options );
            return $handler;
        }
        else return false;
	}
	function setNamespace( $namespace )
	{
		$this->namespace = $namespace;
	}
	function setOptions( $array = array () )
	{
		$this->options = $array;
	}
	public function run ( &$data, $namespace, $options )
	{
		
	}
}

?>