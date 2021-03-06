<?php
/**
 * File example.php
 *
 * @package import
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
include_once( 'lib/ezutils/classes/ezcli.php' );
include_once( 'kernel/classes/ezscript.php' );
include_once( 'kernel/classes/ezcontentobject.php');

$cli =& eZCLI::instance();
$script =& eZScript::instance( array( 'description' => ( "Example import script.\n" .
                                                         "./extension/import/example/example.php" ),
                                      'use-session' => true,
                                      'use-modules' => true,
                                      'use-extensions' => true ) );

$script->startup();

$scriptp_options = $script->getOptions( "",
                                "",
                                array( ) );

$script->initialize();

$cli->output( 'Using Siteaccess '.$GLOBALS['eZCurrentAccess']['name'] );

// login as admin
include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );
$user = eZUser::fetchByName( 'admin' );

if ( is_object( $user ) )
{
	if ( $user->loginCurrent() )
	   $cli->output( "Logged in as 'admin'" );
}
else
{
	$cli->error( 'No admin.' );
    $script->shutdown( 1 );
}

// start import Framework
// -----------------------------------------------------
include_once( 'lib/ezutils/classes/ezextension.php' );
ext_class( 'import' , 'ezimportframework' );

/*
// get an Instance of the import Framework with importhandler 'csv' set start log message in import.log
$if =& eZImportFramework::instance( 'csv' );

// set all data to $if->datasets
$if->getData( "extension/import/example.csv" );
*/

/* get an Instance of the import Framework with importhandler 'default'
  set start log message in import.log
  @see importhandlers/ [handlername]import.php
  you can write your own importhandlser - standard is default
  ====================================================================== */
$iframework =& eZImportFramework::instance( 'default' );
if ( $iframework === false ) 
{
    $cli->error( 'Did you enable the import extension in the ini settings?' );
    return $script->shutdown( 1 );
}

/* some static stuff
========================================================================*/

// Where to import the contenobjects
$containerNodeId = 2;
// Which ContentClass to use for import
$classIdentifier = "folder";

// used for RemoteId  => import:$namespace: $remote_id_old||$remote_id_custom
// e.g import:TestDefault:lskjfojwe23ewrljfso
// so you can remove all import objects with the remove.php script
$namespace = "TestDefault";



/* set Inputfilter to convert data before setting in an  ez content object
===========================================================================*/

/*$html= "some <br> html <h1>stuff</h1> ";
$conv = new eZImportConverter( $html );
$conv->addFilter( "plaintext");
$conv->addFilter( "plaintext33");
*/

$name_conf = new eZImportConverter( 'felix name 1 with <h1>html in it</h1>' );
// adding a filter to remove all html stuff
// you can write your own filters @see importfilters/ [filtername]filter.php
$name_conf->addFilter( "plaintext");

$description_conf = new eZImportConverter( 'some other <b>html</b> stuff ' );
$description_conf->addFilter( "plaintext");


/* generate data array with ezattributes as key
=============================================================================
e.g. $dataset['description'] point to contentclass_attribute 'description'
*/

$dataset[0] = array(	'name' 				=> $name_conf,
						'short_name'		=> null,
						'description'		=> $description_conf,
						EZ_IMPORT_PRESERVED_KEY_CREATION_TIMESTAMP => time(),
						EZ_IMPORT_PRESERVED_KEY_MODIFICATION_TIMESTAMP => time(),
						EZ_IMPORT_PRESERVED_KEY_REMOTE_ID => 'id 5',
						EZ_IMPORT_METHOD => EZ_IMPORT_METHOD_AUTO
						// if an object with remote_id exists update this contentobject and generate a new Version
	);

$dataset[1] = array(	'name' 				=> 'name 2',
						'short_name'		=> 'short name 2 <br> name 2',
						'description'		=> 'some stuff for <bold>the</bold> xmlfield',
						EZ_IMPORT_PRESERVED_KEY_MODIFICATION_TIMESTAMP => time(),
						EZ_IMPORT_METHOD => EZ_IMPORT_METHOD_NO_UPDATE	// always create new objects no update
	);


/* set data to $iframework->dataset for later import
=====================================================*/
$iframework->getData( $dataset, $namespace );


$user = eZUser::fetchByName("admin");
$userID = $user->attribute( 'contentobject_id' );
$class = eZContentClass::fetchByIdentifier( $classIdentifier );


$options = array(
					'contentClassID' => $class->attribute( 'id' ),
					EZ_IMPORT_PRESERVED_KEY_OWNER_ID => $userID,
					'parentNodeID' => $containerNodeId
				);




// ----------- import all Data	----------------------------------
$iframework->importData( 'ezcontentobject', $namespace, $options );

//free memory if you need to adn if you have multiple sets to import. 
$iframework->freeMem();

$iframework->log( "Import Done - this will write stuff to import.log" );

// set stop log message in import.log
$iframework->destroy();
$cli->output( "Import completed" );
$cli->output( "You can review imported items in var/log/import.log" );
return $script->shutdown();

?>