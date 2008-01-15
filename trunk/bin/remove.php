<?php
/**
 * File remove.php
 *
 * @package import
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */ 
/*
Remove imported Contenobjects:

examples:

php extension/import/bin/remove.php --class=folder   
   => remove all folder contentobjects which remoteId starts with 'ezimport'

php extension/import/bin/remove.php --class=folder,article 
	=> remove all folder and article contentobjects  which remoteId starts with 'ezimport'
   
php extension/import/bin/remove.php --class=1,article 
	=> remove all folder(1) and article contentobjects  which remoteId starts with 'ezimport'   

php extension/import/bin/remove.php --class=1,2 
	=> remove all folder(1) and article(2) contentobjects  which remoteId starts with 'ezimport'    

php extension/import/bin/remove.php --class=folder --namespace=FolderImport
   	=> remove all folder contentobjects which remoteId starts with 'ezimport:FolderImport'   

php extension/import/bin/remove.php --class=folder --namespace=FolderImport --simulate
   	=> Simulation: remove all folder contentobjects which remoteId starts with 'ezimport:FolderImport'    
   	=> to test which contentobject will be deleted
*/

$readme  = <<< README

README:
This scrip tis dependant on the extension IMPORT from the PUBSVN.
README;
include_once( 'lib/ezutils/classes/ezcli.php' );
include_once( 'kernel/classes/ezscript.php' );
$cli =& eZCLI::instance();
$script =& eZScript::instance( array( 'description' => ( "Remove import script. " . $readme .
                                                         "\n" .
                                                         "php extension/import/bin/remove.php --class=2,10\n".
                                                         "php extension/import/bin/remove.php --class=article --namespace=ArticleImport" ),
                                      'use-session' => true,
                                      'use-modules' => true,
                                      'use-extensions' => true ) );

$script->startup();

$options = $script->getOptions( "[class:][namespace;][simulate]",
                                "",
                                array( 'class' =>  "Which class indentifier to remove (list of numbers or strings e.g. folder,article)",
                                       'namespace' => "Optional: Which import namespace to remove ezimport:<namespace> e.g. ArticleImport",
                                       'simulate' => "If set no data will be deleted but shows which data would be deleted"
                                        )
                                       );

$script->initialize();


$sys =& eZSys::instance();

// get all classes 

$classIdList = array();

if ( empty( $options['class'] ) /*or !is_object( $class )*/ )
{
	
	$script->showHelp();
	return $script->shutdown();
}
else 
{
	$classListOption = explode(',',$options['class']);
	
	include_once( 'kernel/classes/ezcontentobject.php' );
	foreach ( $classListOption as $class )
	{
		// if class id is set
		if( is_numeric($class) )
		{	
			$classObject = eZContentClass::fetch( $class );				
		}
		else 
		{
			// if classidentifier is set get class Id
			$classObject = eZContentClass::fetchByIdentifier( $class );			
		}
		
		if( is_object( $classObject ))
			array_push( $classIdList, $classObject->attribute('id') );
		else 
			$cli->output("ClassIdentifier/ClassId: $class not exists!!!!!");
			
	}

	$classIdList = array_unique($classIdList);
	
}


if( count($classIdList) == 0 )
{
	
	$cli->output('++++ No available ClassIds or ClassIdentifer are set +++++');
	$script->showHelp();
	return $script->shutdown();
}



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

// default namespace
$deletestring ="ezimport";
if ( $options['namespace'] )
	$deletestring ="ezimport:".$options['namespace'];

$classIdString = '';
$count=0;
foreach ( $classIdList as $class_id )
{
	if( $count == 0)
		$classIdString .= ' (';
	
	if( $count > 0)
		$classIdString .= ' OR';
	$classIdString .= " contentclass_id='$class_id'";
	$count++;
}
$classIdString .= ') ';



$sqlText="SELECT * FROM   ezcontentobject 
WHERE  remote_id like '%".$deletestring."%' AND ".$classIdString."  
ORDER BY id ASC";

/*$sqlText="SELECT * FROM   ezcontentobject 
WHERE  remote_id like '%".$deletestring."%' AND contentclass_id='". $class->ID ."'  
ORDER BY id ASC";*/

$db = eZDB::instance();
$rows = $db->arrayQuery( $sqlText );

$moveToTrash = false;

$classes = implode(',',$classIdList);
if( $options['simulate'] )
$cli->output('########### This is only a Simulation - no data will be deleted ############');

$cli->output("++ Deleting all ContentObjects which ++ \n=> Class: $classes\n=> RemoteId start with: $deletestring \n==========================================");
$count = 0;
foreach( $rows as $row )
{
    $item = new eZContentObject( $row );
    $deleteIDArray = array( $item->attribute( 'main_node_id' ) );
    $cli->output("Delete ID: ".$item->attribute( 'id' )." NodeID: ".$item->attribute( 'main_node_id' ) ." RemoteId: ".$item->attribute( 'remote_id' ));
    
    if($options['simulate'] != true)
    	eZContentObjectTreeNode::removeSubtrees( $deleteIDArray, $moveToTrash );
	$count++;
}
$cli->output("++ Delete $count ContentObjects ++");
if ( $options['simulate'] === true )
$cli->output('########### This is only a Simulation - no data were deleted ############');


return $script->shutdown();
?>