<?php
/**
 * File containing the eZContentObjectImportProcess class.
 *
 * @package import
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
 
include_once( 'lib/ezutils/classes/ezoperationhandler.php' ); 
include_once( 'lib/ezlocale/classes/ezdatetime.php' );
include_once( 'kernel/classes/datatypes/ezenum/ezenumtype.php' );
include_once( 'extension/import/classes/ezimportprocess.php' );

#include_once( 'extension/ezdhtml/ezxmltext/handlers/input/ezdhtmlinputparser.php' );
include_once( 'kernel/classes/datatypes/ezxmltext/handlers/input/ezsimplifiedxmlinputparser.php' );
include_once( 'kernel/classes/datatypes/ezxmltext/handlers/input/ezsimplifiedxmlinput.php' );

class eZContentObjectImportProcess extends eZImportProcess
{
	function eZContentObjectImportProcess()
	{

	}

	function &run( &$data )
	{
		$result = array();

		$optionContentClassID = 0;
		if( array_key_exists( EZ_IMPORT_PRESERVED_KEY_CLASS_ID, $this->options ))
		{
		    $optionContentClassID = $this->options[EZ_IMPORT_PRESERVED_KEY_CLASS_ID];
		    $optionClass = eZContentClass::fetch( $optionContentClassID );
		}

		if( array_key_exists( EZ_IMPORT_PRESERVED_KEY_CLASS, $this->options ) && !is_object( $optionClass ))
		{
		    $optionClass = eZContentClass::fetchByIdentifier( $this->options[EZ_IMPORT_PRESERVED_KEY_CLASS] );
		    if ( is_object( $optionClass ) )
	           $optionContentClassID = $optionClass->attribute('id');
		}


		foreach ( $data as $item )
		{

		    $contentClassID = $optionContentClassID;
		    $class = $optionClass;

		    if( array_key_exists( 'class_identifier', $item ) )
		    {

		        $classIdentifier = $item['class_identifier'];
		        $classItem  = eZContentClass::fetchByIdentifier( $classIdentifier );

		        if( is_object( $classItem ))
		        {
		            $class = $classItem;
		        }
		    }

		    // If no class object could be create Skipping
		    if( !is_object( $class ) )
		    {
		        eZImportFramework::log( "SKIPPING: No Class could be created for ClassId: $contentClassID - ClassIdentifer:  [import/.../ezcontentobjectimortprocess.php]" );
				continue;
		    }

			$parentNodeID=null;
			if ( array_key_exists( 'parentfind', $this->options ) and $this->options['parentfind'] )
			{
				$parentco =& eZContentObjectTreeNode::fetchObjectList( eZContentObject::definition($contentClassID),
				null,
				array ( 'contentclass_id' => $this->options['parentfind']['contentClassID'], 'name' => $item[$this->options['parentfind']['attribute'][1]] ),
				null,
				null,
				true
				);
				$parentNodeID=eZContentObjectTreeNode::findMainNode($parentco[0]->ID);
			}
			if ( !$parentNodeID )
			{
				if ( array_key_exists( 'parentNodeID', $this->options ) and is_numeric( $this->options['parentNodeID'] ) )
					$parentNodeID = $this->options['parentNodeID'];
				else if ( is_numeric( $item[ $this->options['parentNodeID'] ] ) and $item[ $this->options['parentNodeID'] ] )
					$parentNodeID = $item[ $this->options['parentNodeID'] ];
			}
			if ( !$parentNodeID )
			{
				if ( array_key_exists( 'parentNodeID', $this->options ) and is_numeric( $item['parentNodeID'] ) )
					$parentNodeID = $item['parentNodeID'];
			}
			if ( !$parentNodeID )
			{
				if ( is_array( $item['parentNodes'] ) and array_key_exists( 'parentNodes', $item ) and is_numeric( $item['parentNodes'][0] ) )
					$parentNodeID = $item['parentNodes'][0];
			}
			if ( !$parentNodeID )
			{
				eZImportFramework::log( "SKIPPING: No Parent Node ID" );
				continue;
			}

			$locale =& eZLocale::instance();
			if ( array_key_exists( EZ_IMPORT_PRESERVED_KEY_CREATION_TIMESTAMP, $item ) )
			{
				$datetime_create = new eZDateTime( $item[EZ_IMPORT_PRESERVED_KEY_CREATION_TIMESTAMP] );
			}
			else
			{
				$datetime_create = new eZDateTime();
			}

			if ( array_key_exists( EZ_IMPORT_PRESERVED_KEY_MODIFICATION_TIMESTAMP, $item ) )
			{
				$datetime_modify = new eZDateTime( $item[EZ_IMPORT_PRESERVED_KEY_MODIFICATION_TIMESTAMP] );
			}
			else
			{
				$datetime_modify = new eZDateTime();
			}

			$datetime_create->setLocale( $locale );
			$datetime_modify->setLocale( $locale );

			$parentContentObjectTreeNode = eZContentObjectTreeNode::fetch( $parentNodeID );
			if ( !is_object( $parentContentObjectTreeNode ) )
			{
				eZImportFramework::log( "SKIPPING: No Node ID '". $parentNodeID ."'" );
				continue;
			}
			$parentContentObject = $parentContentObjectTreeNode->attribute("object");
			$sectionID = $parentContentObject->attribute( 'section_id' );

			// TODO create a new object or get an existing version

            // ================================================
 			// Create a new Version or Update an existing item
			//=================================================

			// set default import Method
			if ( !array_key_exists( EZ_IMPORT_METHOD, $item ) and array_key_exists( EZ_IMPORT_METHOD, $this->options ) )
    			$item[EZ_IMPORT_METHOD] = $this->options[EZ_IMPORT_METHOD];
			if( !array_key_exists(EZ_IMPORT_METHOD, $item) )
			{
				$item[EZ_IMPORT_METHOD] = EZ_IMPORT_METHOD_NO_UPDATE;
			}


			$version = null;
			$attribs = null;
			$contentObject = null;
			$logMessageStart = null;
			$remoteIdString = null;

			// if contentobject_id is set => set remote_id
			if( array_key_exists( EZ_IMPORT_PRESERVED_KEY_CONTENT_OBJECT_ID, $item ) )
			{
			     $objectId = $item[ EZ_IMPORT_PRESERVED_KEY_CONTENT_OBJECT_ID ];
                 $contentObject = eZContentObject::fetch( $objectId );

                 if( is_object($contentObject) )
                 {
                    $item[ EZ_IMPORT_PRESERVED_KEY_REMOTE_ID ] = $contentObject->attribute('remote_id');
                    $remoteIdString = $item[ EZ_IMPORT_PRESERVED_KEY_REMOTE_ID ];
                 }

                 unset( $contentObject, $objectId );
			}
			// set remote_id if set
			elseif ( array_key_exists( EZ_IMPORT_PRESERVED_KEY_REMOTE_ID, $item ) )
			{
			    $remoteIdString = EZ_IMPORT_REMOTE_ID_TAG . ":".$this->namespace.":".$item[EZ_IMPORT_PRESERVED_KEY_REMOTE_ID];
			}
			else
			{
			    $remoteIdString = EZ_IMPORT_REMOTE_ID_TAG . ":".$this->namespace.":" . md5( (string)mt_rand() . (string)mktime() );
			}


			$owner = null;

			// set Owner
			if ( array_key_exists(EZ_IMPORT_PRESERVED_KEY_OWNER_ID, $item) and is_object( eZUser::fetch( $item[EZ_IMPORT_PRESERVED_KEY_OWNER_ID] ) ) )
				$owner = $item[EZ_IMPORT_PRESERVED_KEY_OWNER_ID];
			elseif( array_key_exists( EZ_IMPORT_PRESERVED_KEY_OWNER_ID, $this->options ) )
				$owner = $this->options[EZ_IMPORT_PRESERVED_KEY_OWNER_ID];



			// only can update if a remote id is set and the object exists
			if( $remoteIdString != '' )
			{

				$contentObject = eZContentObject::fetchByRemoteID( $remoteIdString );
			}


			$isSetNoUpdateNoCreate=false;

			// create or update object?
			switch ( $item[EZ_IMPORT_METHOD] )
			{
				case EZ_IMPORT_METHOD_AUTO:
				case EZ_IMPORT_METHOD_UPDATE:

					// only can update if a remote id is set and the object exists
					if( is_object($contentObject) )
					{

					        $contentINI =& eZINI::instance( 'content.ini' );
                            $versionlimit = $contentINI->variable( 'VersionManagement', 'DefaultVersionHistoryLimit' );
                            $limitList = $contentINI->variable( 'VersionManagement', 'VersionHistoryClass' );
                            foreach ( array_keys ( $limitList ) as $key )
                            {
                                if ( $class->attribute('id') == $key )
                                    $versionlimit =& $limitList[$key];
                            }
                            if ( $versionlimit < 2 )
                                $versionlimit = 2;
                            $versionCount = $contentObject->getVersionCount();
                            if ( $versionCount < $versionlimit )
                            {

//TODO wenn versionslimit erreicht altere löschen und neue anlegen
					   /*     $db =& eZDB::instance();
                            $db->begin();
                            $version = $contentObject->createNewVersionn( $EditLanguage, $FromLanguage );
                            $version->setAttribute( 'status', EZ_VERSION_STATUS_INTERNAL_DRAFT );
                            $version->store();
*/
    							// get a new version of the content object
    							$version = $contentObject->createNewVersion();

                            }
                            else

                            {
                                $objectId = $contentObject->attribute('id');
                                $remoteId = $contentObject->attribute('remote_id');
                                $log = "[EXIST] ContentObject already exists! ObjectId=($objectId) RemoteId=($remoteId)";

                               // $version = $contentObject->currentVersion();
                                $log = "[ERROR NEW VERSION] Could not Create New Version for ObjectId=($objectId) RemoteId=($remoteId) (". $contentObject->attribute('class_identifier') . ") ";
                                eZImportFramework::log( $log );
                                return false;
                            }

							// if new version delete all existing objectrelations
							// because they will be set again otherwise there are double entities


							// DELETE: All relations from actual version
							// see storeAttributes .... objectrelationlist
							$toObjectID = false; // if false all related objects are removed
							$fromObjectVersion = $version->attribute('version');
							$fromObjectID = $contentObject->attribute('id');
							$attributeID = false;

							eZContentObject::removeContentObjectRelation($toObjectID, $fromObjectVersion, $fromObjectID, $attributeID);





							$version->setAttribute( 'user_id', $owner );
							//$version->setAttribute( 'owner_id', $owner );
							//$attribs = $version->contentObjectAttributes();
							$logMessageStart = 'New Version ('. $version->attribute('version') .')';
							break;
					}
				case EZ_IMPORT_METHOD_NO_UPDATE_IF_EXIST:
					// if exist no update only return node e.g. for KeyConverter
					if( is_object($contentObject) )
					{


						array_push($result, $contentObject);

						$objectId = $contentObject->attribute('id');
						$remoteId = $contentObject->attribute('remote_id');
						$log = "[EXIST] ContentObject already exists! ObjectId=($objectId) RemoteId=($remoteId)";

						eZImportFramework::log( $log );

						$isSetNoUpdateNoCreate=true;

						break;

					}

				case EZ_IMPORT_METHOD_NO_UPDATE:
				default:

					// set remote_id null if there is an object with the same remote_id
					// because we want to have no Updates !!!
					if( is_object($contentObject) )
					{

						$log = "[Create] Remote_id already exists! Resetting (". $remoteIdString .") to default";
						$item[EZ_IMPORT_PRESERVED_KEY_REMOTE_ID] = null;
						eZImportFramework::log( $log );
					}

					// create new ContentObject
					$contentObject = $class->instantiate( $owner, $sectionID );

					/*
					if ( $item[EZ_IMPORT_PRESERVED_KEY_OWNER_ID] and is_object( eZUser::fetch( $item[EZ_IMPORT_PRESERVED_KEY_OWNER_ID] ) ) )
						$contentObject = $class->instantiate( $item[EZ_IMPORT_PRESERVED_KEY_OWNER_ID], $sectionID );
					elseif( array_key_exists( EZ_IMPORT_PRESERVED_KEY_OWNER_ID, $this->options ) )
						$contentObject = $class->instantiate( $this->options[EZ_IMPORT_PRESERVED_KEY_OWNER_ID], $sectionID );
			*/

					$version = $contentObject->currentVersion();
					$logMessageStart = "New Object (". $contentObject->attribute('class_identifier') . ") ";
				//	$attribs = $contentObject->contentObjectAttributes();

				break;
			}


			if( $isSetNoUpdateNoCreate == true )
			{
				continue;
			}

			//$version->setAttribute( 'status', EZ_VERSION_STATUS_DRAFT );
			$version->setAttribute( 'status', EZ_VERSION_STATUS_INTERNAL_DRAFT );
    		$version->store();

			// === End of Switch - create or update object
			// ===========================================


					if ( !is_object( $contentObject ) )
					{
						eZImportFramework::log( "SKIPPING: No owner defined." );
						continue;
					}

					//default node settings
					$node_defaults = array();
					if ( array_key_exists('sort_field', $this->options ) )
						$node_defaults['sort_field'] = $this->options['sort_field'];
					if ( array_key_exists('sort_order', $this->options ) )
						$node_defaults['sort_order'] = $this->options['sort_order'];
					//create nodes
					$merged_node_array  = array_merge(
							$node_defaults,
							array( 'contentobject_id' => $contentObject->attribute( 'id' ),
/* TODO version*/				   'contentobject_version' => $contentObject->attribute( 'current_version' ),
								   'parent_node' => $parentContentObjectTreeNode->attribute( 'node_id' ),
								   'is_main' => 1
							) );

					$nodeAssignment =& eZNodeAssignment::create( $merged_node_array );
					$nodeAssignment->store();

					if ( array_key_exists( 'parentNodes', $item ) and is_array( $item['parentNodes'] ) )
					{
						$skip=true;
						foreach ( $item['parentNodes']  as $node_id )
						{
							if ( $skip )
							{
								$skip=false;
								continue;
							}
							if ( is_numeric( $node_id ) and is_object( eZContentObjectTreeNode::fetch( $node_id ) ) )
							{
								$nodeAssignment =& eZNodeAssignment::create(
									array_merge(
										$node_defaults,
										array(
										'contentobject_id' => $contentObject->attribute( 'id' ),
										'contentobject_version' => $contentObject->attribute( 'current_version' ),
										'parent_node' => $node_id,
										'is_main' => 0
										)
									)
								);
								$nodeAssignment->store();
							}
						}
					}



					// if $item[EZ_IMPORT_PRESERVED_KEY_REMOTE_ID] == null ez will generate a remoteid
					$contentObject->setAttribute( 'remote_id', $item[EZ_IMPORT_PRESERVED_KEY_REMOTE_ID] );
					//$contentObject->setAttribute( 'remote_id', $item[$remote_id] );
	//				$contentObject->setAttribute( 'name', $item[$object_name] );



					$contentObject->setAttribute( 'modified', $datetime_modify->timeStamp() );
					$contentObject->setAttribute( 'published', $datetime_create->timeStamp() );
				//	$contentObject->setAttribute( 'owner_id', $owner );

					// to generate a remot_id if needed
				//	$contentObject->store();



			//$contentObject->name();

			// get all attributes and modify data if needed
			// ----------------------------------------------
			$attribs =& $contentObject->contentObjectAttributes();

			for($i=0;$i<count($attribs);$i++){
				$ident = $attribs[$i]->attribute("contentclass_attribute_identifier");
				if ( array_key_exists( $ident , $item ) and $item[$ident] )
				{
					// modify the input data
					$this->storeAttribute($item[$ident],$attribs[$i]);
				}
			}



			$contentObject->setAttribute( 'modified', $datetime_modify->timeStamp() );
			$contentObject->setAttribute( 'published', $datetime_create->timeStamp() );
			$contentObject->setAttribute( 'status', EZ_VERSION_STATUS_DRAFT );




			// set remote_id : if $item['remote_id']=null the system genereate a new remote id
			// e.g.   ezimport:namespace:remote_id
			$contentObject->setAttribute( 'remote_id', EZ_IMPORT_REMOTE_ID_TAG . ":".$this->namespace.":".$contentObject->attribute( 'remote_id' ) );
			$contentObject->store();


			$operationResult = eZOperationHandler::execute(
				'content', 'publish', array(
							'object_id' => $contentObject->attribute( 'id' ),
							'version' => $version->attribute('version')
				));


			// update objectname

			$newName = $class->contentObjectName( $contentObject );
			$contentObject->setName( $newName, $version->attribute('version') );




			// set the date after publish - needed if a creation or modification date is set by item
			$contentObject->setAttribute( 'modified', $datetime_modify->timeStamp() );
			$contentObject->setAttribute( 'published', $datetime_create->timeStamp() );
			// set status on publish - otherwise you can't use this object as an related object
			$contentObject->setAttribute( 'status', EZ_VERSION_STATUS_PUBLISHED );
			$contentObject->store();



			// TODO Update or Create ContentObject


			$log =  $logMessageStart.": ".$contentObject->attribute("name")." #".$contentObject->attribute("id") ;
			if ( $item['parentNodes'] )
			{
				$log .= " with Nodes ". join( ",", $item['parentNodes']) ;
			}
			$log .= " owner #". $contentObject->attribute("owner_id") ;
			if ( $contentObject->attribute("main_node_id") )
				$log .= " and Main Node ". $contentObject->attribute("main_node_id");
			else
				$log .= " and no Main Node ";

			eZImportFramework::log( $log );

			//Free some memory
			eZContentObject::clearCache();

			if ( array_key_exists( EZ_IMPORT_LANGUAGE_TAG, $this->options ) and $this->options[EZ_IMPORT_LANGUAGE_TAG] != eZContentObject::defaultLanguage() )
			{

				eZContentObjectImportProcess::changeLanguageForObject( $contentObject->attribute("id"), eZContentObject::defaultLanguage(),  $this->options[EZ_IMPORT_LANGUAGE_TAG] );
			}

			array_push($result, $contentObject);
		}
		return $result;
	}
	function changeLanguageForObject( $id, $from, $to )
	{
		$db = eZDB::instance();
	#$db->begin();


		$db->query( "
UPDATE ezcontentobject_attribute
SET
  language_code='".$to."'
WHERE
  language_code='".$from."' AND contentobject_id=".$id );
		$db->query( "UPDATE ezcontentobject_name
SET   real_translation='".$to."'
WHERE
  real_translation='".$from."' AND contentobject_id=" . $id );

	#	$db->commit();

	}
	function storeAttribute( $data, &$contentObjectAttribute )
	{
		$contentClassAttribute = $contentObjectAttribute->attribute( 'contentclass_attribute' );
		$dataTypeString = $contentClassAttribute->attribute( 'data_type_string' );

		if ( is_a( $data, 'ezimportconverter' ) )
		{
			$data = $data->run();
		}
		switch( $dataTypeString )
		{
			case 'ezuser';
				$userID = $contentObjectAttribute->attribute( 'contentobject_id' );
            		$user = eZUser::fetch( $userID );
            	if ( !is_object( $user ) )
            	{
                	$user = eZUser::create( $userID );
            	}
            	$user->setAttribute( 'login', $data['login'] );
            	$user->setAttribute( 'email', $data['email'] );
            	$user->setAttribute( 'password_hash', $data['password_hash'] );
           	 	$user->setAttribute( 'password_hash_type', $data['password_hash_type'] );
            	$user->store();

                include_once( "kernel/classes/datatypes/ezuser/ezusersetting.php" );
                $setting = eZUserSetting::create( $contentObjectAttribute->attribute( 'contentobject_id' ), true );
                $setting->store();
            	$contentObjectAttribute->store();
			break;
			case 'ezgis';
				include_once( "extension/gis/datatypes/ezgis/ezgisposition.php" );
				$contentObjectAttribute->setAttribute( 'data_text', eZContentObjectImportProcess::arrayToXML('data', $data ) );
				$contentObjectAttribute->store();
				$gp = new eZGISPosition(
					array(
						'contentobject_attribute_id' => $contentObjectAttribute->attribute( 'id' ),
    		 			'contentobject_attribute_version' => $contentObjectAttribute->attribute( 'version' ),
    		 			'x' => $data['x'],
    		 			'y' => $data['y']
    		 			) );
    		 	$gp->store();

			break;
			case 'ezfloat' :
			$contentObjectAttribute->setAttribute( 'data_float', $data );
			$contentObjectAttribute->store();
			break;

			case 'ezoption2' :
			    include_once( "extension/ezoption2/datatypes/ezoption2/ezoption2.php" );
                $option = new eZOption2( );
                foreach ( $data as $key => $item )
                {
                    $option->addOption( array( 'value' => $item['name'],
                    'comment' => $item['comment'],
                    'additional_price' => ( isset( $item['price'] ) ? $item['price'] : 0 ) ) );
                }
                $contentObjectAttribute->setContent( $option );
                $contentObjectAttribute->store();
			break;
			case 'ezprice' :
				$contentObjectAttribute->setAttribute( 'data_float', $data );
				$contentObjectAttribute->store();
				break;
			case 'ezboolean' :
				if ( $data )
				{
					$contentObjectAttribute->setAttribute( 'data_int', 1 );
					$contentObjectAttribute->store();
				}
				else
				{
					$contentObjectAttribute->store();
				}
				break;
			case 'ezdate' :
				$contentObjectAttribute->setAttribute( 'data_int', $data );
				$contentObjectAttribute->store();
				break;
			case 'ezdatetime' :
				$contentObjectAttribute->setAttribute( 'data_int', $data );
				$contentObjectAttribute->store();
				break;
			case 'ezinteger' :
				$contentObjectAttribute->setAttribute( 'data_int', $data );
				$contentObjectAttribute->store();
				break;
			case 'ezsubtreesubscription' :
				break;
			case 'ezimage':
				if ( is_string($data) and file_exists( $data ) )
				{
					eZImageType::insertRegularFile( $object, $objectVersion, $objectLanguage,
	                                $contentObjectAttribute, $data,
	                                $result );
					$contentObjectAttribute->store();
				}
				break;
			case 'ezbinaryfile':
				if ( is_string($data) and file_exists( $data ) )
				{
				    $binary = eZBinaryFile::create( $contentObjectAttribute->attribute('id'), null );
				    $binary->store();
					eZBinaryFileType::insertRegularFile( $object, $contentObjectAttribute->attribute('version'), $objectLanguage,
	                                $contentObjectAttribute, $data,
	                                $result );
					$contentObjectAttribute->store();
				}
				break;
			case 'eztime' :
				$contentObjectAttribute->setAttribute( 'data_int', $data );
				$contentObjectAttribute->store();
				break;
			case 'ezkeyword' :
			    $keyword = new eZKeyword();
            	$keyword->initializeKeyword( $data );
            	$contentObjectAttribute->setContent( $keyword );
				$contentObjectAttribute->store();
            	break;
			case 'ezemail' :
				$contentObjectAttribute->setAttribute( 'data_text', $data );
				$contentObjectAttribute->store();
				break;
			case 'ezisbn' :
				$contentObjectAttribute->setAttribute( 'data_text', $data );
				$contentObjectAttribute->store();
				break;
			case 'ezstring' :
				$contentObjectAttribute->setAttribute( 'data_text', $data );
				$contentObjectAttribute->store();
				break;
		    case 'ezobjectrelation' :
				$contentObjectAttribute->setAttribute( 'data_int', (int)$data );
				$contentObjectAttribute->store();
				break;
			case 'ezobjectrelationlist':

				/*
				data_text=
				<?xml version="1.0" encoding="utf-8"?>
				<related-objects>
				  <relation-list>
				    <relation-item priority="1"
				                   contentobject-id="1182"
				                   contentobject-version="1"
				                   node-id="1114"
				                   parent-node-id="1107"
				                   contentclass-id="31"
				                   contentclass-identifier="faq_category" />
				    <relation-item priority="2"
				                   contentobject-id="1177"
				                   contentobject-version="1"
				                   node-id="1110"
				                   parent-node-id="1107"
				                   contentclass-id="31"
				                   contentclass-identifier="faq_category" />
				  </relation-list>
				</related-objects>
				*/

				$objectRelationListAttribute = $contentObjectAttribute;

				$content = $objectRelationListAttribute->content();
				$priority = 1;


				// DELETE: All relations from actual version
				// see create new version
				$content = $objectRelationListAttribute->content();
				$content['relation_list']=array();

				//eZObjectRelationListType::storeObjectAttributeContent( $objectRelationListAttribute, $content );

				$objectRelationListAttribute->setContent( $content );
				$objectRelationListAttribute->store();

				// add all object_ids as relation for actual version
				$objectsIdsToRelate = $data;
				foreach ( $objectsIdsToRelate as $object_id )
				{
					$content['relation_list'][] = eZObjectRelationListType::appendObject( $object_id, $priority, $objectRelationListAttribute );
					//eZObjectRelationListType::storeObjectAttributeContent( $objectRelationListAttribute, $content );
					$objectRelationListAttribute->setContent( $content );

					// createobject link in tabel ezcontentobject_link
					$toObjectID = $object_id;
					$attributeID = $objectRelationListAttribute->attribute(id);

					eZContentObject::addContentObjectRelation( $toObjectID, $fromObjectVersion=false, $fromObjectID=false, $attributeID );

					$objectRelationListAttribute->store();
					$priority++;

				}
	            break;
			case 'eztext' :
				$contentObjectAttribute->setAttribute( 'data_text', $data );
				$contentObjectAttribute->store();
		    	break;
		    case 'ezcountry' :
				$contentObjectAttribute->setAttribute( 'data_text', $data );
				$contentObjectAttribute->store();
		   		break;
		    case 'ezselection' :
		        $cc = $contentObjectAttribute->attribute( 'class_content' );
		        $found = false;
		        foreach ( $cc['options'] as $option )
		        {
		            if ( $option['name'] == $data )
		            {
		                $found = true;
		                break;
		            }
		        }
		        if ( $found )
		        {
				    $contentObjectAttribute->setAttribute( 'data_text', $option['id'] );
				    $contentObjectAttribute->store();
		        }
		   		break;
			case 'ezurl' :
				if ( is_array( $data) )
				{
					//set data and text
				 	$contentObjectAttribute->setAttribute( 'data_text', $data['text'] );
				 	$contentObjectAttribute->setContent( $data['url'] );
				}
				else
				{
	            	$contentObjectAttribute->setContent( $data );
				}
				$contentObjectAttribute->store();
				break;
			case 'ezxmltext' :
				if ( is_object( $data ) )
				{
					$contentObjectAttribute->setAttribute( "data_text", eZXMLTextType::domString( $data ) );
				}
				else
				{
					$dummy = "";

					$converter = new text2xml( $dummy, 0, $contentObjectAttribute );
					$converter->validateInput( $data, $contentObjectAttribute );

				}
				$contentObjectAttribute->setAttribute( 'data_int', EZ_XMLTEXT_VERSION_TIMESTAMP );
				$contentObjectAttribute->store();
				break;
			case 'ezenum' :
				if( is_array( $data )) {
					$this->store_enum( $data, $contentObjectAttribute );
				} else {
					$this->store_enum( array( $data ), $contentObjectAttribute );
				}
				break;
			case 'ezauthor':


			default :
				$contentObjectAttribute->setContent( $data );
				$contentObjectAttribute->store();
		}
	}
    function &arrayToXML($name,$val)
    {
    	$node = eZDOMDocument::createElementNodeFromArray($name,$val);
    	$doc = new eZDOMDocument();
		$doc->setRoot( $node );
    	return $doc->toString();
    }
	function store_enum( $array_selectedEnumElement, &$contentObjectAttribute )
	{
		// Adapted from function fetchObjectAttributeHTTPInput(...) in class eZEnumType;
		$contentObjectAttributeID = $contentObjectAttribute->attribute( 'id' );
		$contentObjectAttributeVersion = $contentObjectAttribute->attribute( 'version' );
		$contentClassAttribute = $contentObjectAttribute->attribute( 'contentclass_attribute' );
		$contentClassAttributeID = $contentClassAttribute->attribute('id' );
		$contentClassAttributeVersion = $contentClassAttribute->attribute('version' );
		$array_enumValue = ezEnumValue::fetchAllElements( $contentClassAttributeID,
		$contentClassAttributeVersion );
		eZEnum::removeObjectEnumerations( $contentObjectAttributeID, $contentObjectAttributeVersion );
		foreach( $array_enumValue as $enumValue )
		{
			foreach( $array_selectedEnumElement as $selectedEnumElement )
			{
				if( $enumValue->EnumValue === $selectedEnumElement )
				{
					eZEnum::storeObjectEnumeration( $contentObjectAttributeID,
					$contentObjectAttributeVersion, $enumValue->ID,
					$enumValue->EnumElement, $enumValue->EnumValue );
				}
			}
		}
	}
}
include_once( 'kernel/classes/datatypes/ezxmltext/handlers/input/ezsimplifiedxmlinput.php' );
class text2xml extends eZSimplifiedXMLInput
{
    function text2xml(  &$xmlData, $aliasedType, $contentObjectAttribute )
    {
        parent::eZSimplifiedXMLInput(  $xmlData, $aliasedType, $contentObjectAttribute );
    }
        /*!
     \reimp
     Validates the input and returns true if the input was valid for this datatype.
    */
    function validateInput( &$http, &$contentObjectAttribute )
    {
        $contentObjectID = $contentObjectAttribute->attribute( "contentobject_id" );
        $contentObjectAttributeID = $contentObjectAttribute->attribute( "id" );
        $contentObjectAttributeVersion = $contentObjectAttribute->attribute('version');
        if ( $http )
        {
            $data = $http;

            // Set original input to a global variable
            $originalInput = "originalInput_" . $contentObjectAttributeID;
            $GLOBALS[$originalInput] = $data;

            // Set input valid true to a global variable
            $isInputValid = "isInputValid_" . $contentObjectAttributeID;
            $GLOBALS[$isInputValid] = true;

            include_once( 'kernel/classes/datatypes/ezxmltext/handlers/input/ezsimplifiedxmlinputparser.php' );

            $text = $data;

            $text = preg_replace('/\r/', '', $text);
            $text = preg_replace('/\t/', ' ', $text);

            // first empty paragraph
            $text = preg_replace('/^\n/', '<p></p>', $text );

            $parser = new eZSimplifiedXMLInputParser( $contentObjectID );
            $parser->setParseLineBreaks( true );

            $document = $parser->process( $text );

            if ( !is_object( $document ) )
            {
                $GLOBALS[$isInputValid] = false;
                $errorMessage = implode( ' ', $parser->getMessages() );
                $contentObjectAttribute->setValidationError( $errorMessage );
                return EZ_INPUT_VALIDATOR_STATE_INVALID;
            }

            $xmlString = eZXMLTextType::domString( $document );

            //eZDebug::writeDebug( $xmlString, '$xmlString' );

            $relatedObjectIDArray = $parser->getRelatedObjectIDArray();
            $urlIDArray = $parser->getUrlIDArray();

            if ( count( $urlIDArray ) > 0 )
            {
                $this->updateUrlObjectLinks( $contentObjectAttribute, $urlIDArray );
            }

            if ( count( $relatedObjectIDArray ) > 0 )
            {
// TODO fatal erro undefined function ez >= 3.9 in 3.8 it works   $this->updateRelatedObjectsList( $contentObjectAttribute, $relatedObjectIDArray );
            }

            $classAttribute =& $contentObjectAttribute->contentClassAttribute();
            if ( $classAttribute->attribute( "is_required" ) == true )
            {
                $root =& $document->Root;
                if ( !count( $root->Children ) )
                {
                    $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                         'Content required' ) );
                    return EZ_INPUT_VALIDATOR_STATE_INVALID;
                }
            }
            $contentObjectAttribute->setValidationLog( $parser->getMessages() );

            $contentObjectAttribute->setAttribute( "data_text", $xmlString );
            return EZ_INPUT_VALIDATOR_STATE_ACCEPTED;
        }
        return EZ_INPUT_VALIDATOR_STATE_ACCEPTED;
    }
}

?>
