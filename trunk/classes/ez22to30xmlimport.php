<?php
/**
 * File containing the eZ22To30XMLImport class.
 *
 * @package import
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
include_once ( "lib/ezxml/classes/ezxml.php" );
include_once ( "kernel/classes/datatypes/ezxmltext/handlers/input/ezsimplifiedxmlinput.php" );

class eZ22To30XMLImport
{

    function eZ22To30XMLImport( $id = null, $prefix = null )
    {
        $this->ArticleID = $id;
        $this->Prefix = $prefix;
    }

    /*!
     \static
    */
    function convertTo22InputXML( $dom )
    {
        // print( "My prefix: " . $this->Prefix . "\n" );
        if ( ! is_object( $dom ) )
            return false;
        $node = & $dom->elementsByName( "intro" );
        $output[] = eZ22To30XMLImport::inputPage( $node[0] );
        
        $node = & $dom->elementsByName( "page" );
        
        if ( is_object( $node[0] ) )
            $htmlnode = & $node[0]->elementsByName( "html" );
        if ( is_object( $htmlnode ) )
        {
            $node[0] = & $htmlnode;
        }
        
        $output[] = eZ22To30XMLImport::inputPage( $node[0] );
        
        return $output;
    }

    /*!
     \private
     \return the user input format for the given section
    */
    function &inputPage( &$page )
    {
        $output = "";
        foreach ( $page->children() as $childNode )
        {
            $output .= eZ22To30XMLImport::inputTag( $childNode );
        }
        return $output;
    }

    /*!
     \private

     */
    function &inputTag( $tag )
    {
        $output = "";
        $childTagText = "";
        $tagName = $tag->name();
        
        // render children tags
        $tagChildren = $tag->children();
        foreach ( $tagChildren as $childTag )
        {
            $childTagText .= eZ22To30XMLImport::inputTag( $childTag );
        }
        
        switch ( $tagName )
        {
            case '#text':
                {
                    $output .= $tag->content();
                }
                break;
            
            case 'br':
                {
                    $output .= "\n";
                }
                break;
            
            case 'linebreak':
                {
                    $output .= "<linebreak>";
                }
                break;
            
            case 'literalbr':
                {
                    $output .= "<literalbr>";
                }
                break;
            
            case 'literalp':
                {
                    $output .= "<literalp>";
                }
                break;
            
            case 'p':
                {
                    $output .= "<paragraph>$childTagText</paragraph>";
                }
                break;
            
            case 'header':
                {
                    $level = $tag->attributeValue( 'level' );
                    switch ( $level )
                    {
                        case '1':
                            {
                                $output .= "<header level='1'>" . $childTagText . "</header>";
                            }
                            break;
                        case '2':
                            {
                                $output .= "<header level='2'>" . $childTagText . "</header>";
                            }
                            break;
                        case '3':
                            {
                                $output .= "<header level='3'>" . $childTagText . "</header>";
                            }
                            break;
                        case '4':
                            {
                                $output .= "<header level='4'>" . $childTagText . "</header>";
                            }
                            break;
                        case '5':
                            {
                                $output .= "<header level='5'>" . $childTagText . "</header>";
                            }
                            break;
                        case '6':
                            {
                                $output .= "<header level='6'>" . $childTagText . "</header>";
                            }
                            break;
                        default:
                            {
                                $output .= "<header level='1'>" . $childTagText . "</header>";
                            }
                            break;
                    }
                
                }
                break;
            
            case 'bold':
            case 'strong':
                {
                    $output .= "<bold>" . $childTagText . "</bold>";
                }
                break;
            
            case 'strike':
                {
                    $output .= "<custom name='strike'>" . $childTagText . "</custom>";
                }
                break;
            
            case 'quote':
                {
                    $output .= "<custom name='quote'>" . $childTagText . "</custom>";
                }
                break;
            
            case 'factbox':
                {
                    $output .= "<custom name='factbox'>" . $childTagText . "</custom>";
                }
                break;
            
            case 'rollover':
                {
                    $output .= "<custom name='rollover'>" . $childTagText . "</custom>";
                }
                break;
            
            case 'underline':
                {
                    $output .= "<custom name='underline'>" . $childTagText . "</custom>";
                }
                break;
            
            case 'italic':
                {
                    $output .= "<emphasize>" . $childTagText . "</emphasize>";
                }
                break;
            
            case 'bullet':
            case 'list':
                {
                    $oldBullet = true;
                    $listContent = "";
                    // find all list elements
                    foreach ( $tag->children() as $listItemNode )
                    {
                        if ( isset( $listItemNode->name ) and $listItemNode->name == "li" )
                        {
                            $oldBullet = false;
                        }
                        $listItemContent = "";
                        foreach ( $listItemNode->children() as $itemChildNode )
                        {
                            $listItemContent .= eZ22To30XMLImport::inputTag( $itemChildNode );
                        }
                        $listContent .= "  <li>$listItemContent</li>\n";
                    }
                    
                    // Convert old style bullet to standard
                    if ( $oldBullet == true )
                    {
                        $content = "";
                        foreach ( $tag->children() as $listItemNode )
                        {
                            $content .= eZ22To30XMLImport::inputTag( $listItemNode );
                        }
                        $content = trim( $content );
                        $lines = explode( "\n", $content );
                        $listContent = "";
                        foreach ( $lines as $line )
                        {
                            $listContent .= "<li>$line</li>";
                        }
                        if ( $tagName == 'bullet' )
                            $output .= "<ul>$listContent</ul>";
                        else
                            $output .= "<ol>$listContent</ol>";
                    }
                    else
                    {
                        if ( $tagName == 'bullet' )
                            $output .= "<ul>$listContent</ul>";
                        else
                            $output .= "<ol>$listContent</ol>";
                    }
                
                }
                break;
            
            case 'image':
                {
                    $align = $tag->attributeValue( 'align' );
                    $size = $tag->attributeValue( 'size' );
                    $placement = $tag->attributeValue( 'id' );
                    if ( $align == "float" )
                        $align = "inline";
                    if ( $size == "original" )
                        $size = "reference";
                    
                    $db = & eZDB::instance();
                    $db->setIsSQLOutputEnabled( false );
                    $imageArray = $db->arrayQuery( "SELECT ImageID
	                                            FROM eZArticle_ArticleImageLink
                                                WHERE Placement = '$placement' AND ArticleID = '$this->ArticleID'" );
                    $imageID = $imageArray[0]['ImageID'];
                    
                    $remoteImageID = $this->Prefix . "_image_" . $imageID;
                    $imageObjectArray = $db->arrayQuery( "SELECT id FROM ezcontentobject
                                                      WHERE remote_id = '$remoteImageID'" );
                    $imageObjectID = $imageObjectArray[0]['id'];
                    if ( $imageObjectID != null )
                        $output .= "<object id=\"$imageObjectID\" align=\"$align\" size=\"$size\" />";
                }
                break;
            
            case 'table':
                {
                    
                    $tableRows = "";
                    $border = $tag->attributeValue( 'border' );
                    $width = $tag->attributeValue( 'width' );
                    
                    // find all table rows
                    foreach ( $tag->children() as $tableRow )
                    {
                        $tableData = "";
                        foreach ( $tableRow->children() as $tableCell )
                        {
                            $colspan = $tableCell->attributeValue( 'colspan' );
                            $rowspan = $tableCell->attributeValue( 'rowspan' );
                            $tdWidth = $tableCell->attributeValue( 'width' );
                            $cellContent = "";
                            if ( $tableCell->Name == "th" )
                            {
                                foreach ( $tableCell->children() as $tableCellChildNode )
                                {
                                    $cellContent .= eZ22To30XMLImport::inputTag( $tableCellChildNode );
                                }
                                if ( $tableCell->name() != "br" )
                                {
                                    $tableData .= "  <th";
                                    if ( $tdWidth != null )
                                    {
                                        $tableData .= " width=\"$tdWidth\"";
                                    }
                                    if ( $colspan != null )
                                    {
                                        $tableData .= " colspan=\"$colspan\"";
                                    }
                                    if ( $rowspan != null )
                                    {
                                        $tableData .= " rowspan=\"$rowspan\"";
                                    }
                                    $tableData .= ">" . trim( $cellContent ) . "</th>";
                                }
                            
                            }
                            else
                            {
                                foreach ( $tableCell->children() as $tableCellChildNode )
                                {
                                    $cellContent .= eZ22To30XMLImport::inputTag( $tableCellChildNode );
                                }
                                if ( $tableCell->name() != "br" and $tableCell->name() != "linebreak" )
                                {
                                    $tableData .= "  <td";
                                    if ( $tdWidth != null )
                                    {
                                        $tableData .= " width=\"$tdWidth\"";
                                    }
                                    if ( $colspan != null )
                                    {
                                        $tableData .= " colspan=\"$colspan\"";
                                    }
                                    if ( $rowspan != null )
                                    {
                                        $tableData .= " rowspan=\"$rowspan\"";
                                    }
                                    $tableData .= ">" . trim( $cellContent ) . "</td>";
                                }
                            }
                        }
                        if ( $tableRow->name() != "br" and $tableRow->name() != "linebreak" )
                            $tableRows .= "<tr>$tableData</tr>";
                    }
                    $tableAttributes = "";
                    if ( $width != null )
                        $tableAttributes .= " width='$width'";
                    if ( $border != null )
                        $tableAttributes .= " border='$border'";
                    $output .= "<table$tableAttributes>$tableRows</table>";
                }
                break;
            
            case 'ezanchor':
                {
                    $name = $tag->attributeValue( 'href' );
                    $output .= "<anchor name=\"$name\" />";
                }
                break;
            
            case 'media':
                {
                    // not support currently
                }
                break;
            
            case 'file':
                {
                    $fileID = $tag->attributeValue( 'id' );
                    
                    $db = & eZDB::instance();
                    $db->setIsSQLOutputEnabled( false );
                    
                    $fileLinkArray = $db->arrayQuery( "SELECT FileID FROM eZArticle_ArticleFileLink
                                                     WHERE ArticleID ='$this->ArticleID'" );
                    $placement = array();
                    if ( $fileLinkArray != null )
                    {
                        foreach ( $fileLinkArray as $fileLink )
                        {
                            $realID = $fileLink['FileID'];
                            $placement[] = $realID;
                        }
                    }
                    
                    $remoteFileID = $this->Prefix . "_file_" . $placement[$fileID - 1];
                    
                    // $remoteFileID = "file_" . $fileID;
                    $fileObjectArray = $db->arrayQuery( "SELECT id FROM ezcontentobject
                                                     WHERE remote_id = '$remoteFileID'" );
                    $fileObjectID = $fileObjectArray[0]['id'];
                    if ( $fileObjectID != null )
                        $output .= "<object id=\"$fileObjectID\" />";
                }
                break;
            
            case 'form':
                {
                    // not support currently
                }
                break;
            
            case 'link':
                {
                    $target = $tag->attributeValue( 'target' );
                    $text = $tag->attributeValue( 'text' );
                    $href = $tag->attributeValue( 'href' );
                    $articlePrefix = $this->Prefix;
                    
                    if ( preg_match( "/\/article\/articleview\//i", $href ) and ! preg_match( "/http:\/\//i", $href ) )
                    {
                        //$href = str_replace( '/article/articleview/', "" , $href );
                        $href = preg_replace( "/(.*)\/article\/articleview\//e", "", $href );
                        list ( $articleID, $page, $categoryID ) = split( "/", $href );
                        
                        $remoteArticleID = $articlePrefix . "_article_article_" . $articleID;
                        $anchor = "";
                        if ( preg_match( "/#/i", $articleID ) )
                        {
                            list ( $realArticleID, $anchor ) = split( "#", $articleID );
                            $articleID = $realArticleID;
                            $anchor = "#" . $anchor;
                        }
                        
                        $db = & eZDB::instance();
                        $db->setIsSQLOutputEnabled( false );
                        
                        $articleNodeArray = $db->arrayQuery( "SELECT ezcontentobject_tree.node_id, ezcontentobject_tree.parent_node_id FROM ezcontentobject, ezcontentobject_tree
                                                          WHERE ezcontentobject.remote_id = '$remoteArticleID' AND ezcontentobject.id = ezcontentobject_tree.contentobject_id" );
                        
                        if ( $categoryID != null )
                        {
                            $remoteCategoryID = $articlePrefix . "_article_category_" . $categoryID;
                            $categoryNodeArray = $db->arrayQuery( "SELECT ezcontentobject_tree.node_id FROM ezcontentobject, ezcontentobject_tree
                                                               WHERE ezcontentobject.remote_id = '$remoteCategoryID' AND ezcontentobject.id = ezcontentobject_tree.contentobject_id" );
                            $categoryNodeID = $categoryNodeArray[0]['node_id'];
                            
                            foreach ( $articleNodeArray as $articleNode )
                            {
                                $parentNodeID = $articleNode['parent_node_id'];
                                if ( $parentNodeID == $categoryNodeID )
                                    $articleNodeID = $articleNode['node_id'];
                            }
                        }
                        else
                            $articleNodeID = $articleNodeArray[0]['node_id'];
                        $href = "/content/view/full/" . $articleNodeID . $anchor;
                    }
                    
                    if ( preg_match( "/\/article\/view\//i", $href ) and ! preg_match( "/http:\/\//i", $href ) )
                    {
                        // $href = str_replace( '/article/view/', "" , $href );
                        $href = preg_replace( "/(.*)\/article\/view\//e", "", $href );
                        list ( $articleID, $page, $categoryID ) = split( "/", $href );
                        
                        $remoteArticleID = $articlePrefix . "_article_article_" . $articleID;
                        $anchor = "";
                        if ( preg_match( "/#/i", $articleID ) )
                        {
                            list ( $realArticleID, $anchor ) = split( "#", $articleID );
                            $articleID = $realArticleID;
                            $anchor = "#" . $anchor;
                        }
                        
                        $db = & eZDB::instance();
                        $db->setIsSQLOutputEnabled( false );
                        
                        $articleNodeArray = $db->arrayQuery( "SELECT ezcontentobject_tree.node_id, ezcontentobject_tree.parent_node_id FROM ezcontentobject, ezcontentobject_tree
                                                          WHERE ezcontentobject.remote_id = '$remoteArticleID' AND ezcontentobject.id = ezcontentobject_tree.contentobject_id" );
                        if ( $categoryID != null )
                        {
                            $remoteCategoryID = $articlePrefix . "_article_category_" . $categoryID;
                            $categoryNodeArray = $db->arrayQuery( "SELECT ezcontentobject_tree.node_id FROM ezcontentobject, ezcontentobject_tree
                                                               WHERE ezcontentobject.remote_id = '$remoteCategoryID' AND ezcontentobject.id = ezcontentobject_tree.contentobject_id" );
                            $categoryNodeID = $categoryNodeArray[0]['node_id'];
                            
                            foreach ( $articleNodeArray as $articleNode )
                            {
                                $parentNodeID = $articleNode['parent_node_id'];
                                if ( $parentNodeID == $categoryNodeID )
                                    $articleNodeID = $articleNode['node_id'];
                            }
                        }
                        else
                            $articleNodeID = $articleNodeArray[0]['node_id'];
                        $href = "/content/view/full/" . $articleNodeID . $anchor;
                    }
                    
                    if ( preg_match( "/^(#)/i", $href ) )
                    {
                        $db = & eZDB::instance();
                        $db->setIsSQLOutputEnabled( false );
                        
                        $remoteArticleID = $this->Prefix . "_article_article_" . $this->ArticleID;
                        $articleNodeArray = $db->arrayQuery( "SELECT ezcontentobject_tree.node_id FROM ezcontentobject, ezcontentobject_tree
                                                          WHERE ezcontentobject.remote_id = '$remoteArticleID' AND ezcontentobject.id = ezcontentobject_tree.contentobject_id" );
                        
                        $articleNodeID = $articleNodeArray[0]['node_id'];
                        $href = "/content/view/full/" . $articleNodeID . $href;
                    }
                    
                    if ( preg_match( "/\/filemanager\/download\//i", $href ) and ! preg_match( "/http:\/\//i", $href ) )
                    {
                        /*$href = str_replace( '/', "" , $href );
                    $href = str_replace( 'filemanager', "" , $href );
                    $fileID = str_replace( 'download', "" , $href );*/
                        
                        //$href = str_replace( '/filemanager/download/', "" , $href );
                        $href = preg_replace( "/(.*)\/filemanager\/download\//e", "", $href );
                        
                        if ( preg_match( "/\//i", $href ) )
                        {
                            list ( $fileID, $fileName ) = split( "/", $href );
                        }
                        else
                        {
                            $fileID = $href;
                        }
                        
                        $db = & eZDB::instance();
                        $db->setIsSQLOutputEnabled( false );
                        $remoteFileID = $this->Prefix . "_file_" . $fileID;
                        
                        $fileObjectArray = $db->arrayQuery( "SELECT id FROM ezcontentobject
                                                         WHERE remote_id = '$remoteFileID'" );
                        
                        $fileObjectID = $fileObjectArray[0]['id'];
                        
                        $fileAttributeArray = $db->arrayQuery( "SELECT id FROM ezcontentobject_attribute
                                                            WHERE contentobject_id = '$fileObjectID' AND version = 1 " );
                        
                        $fileAttributeID = $fileAttributeArray[2]['id'];
                        $href = "/content/download/" . $fileObjectID . "/" . $fileAttributeID . "/file/";
                    }
                    
                    if ( preg_match( "/\/article\/archive\//i", $href ) )
                    {
                        $href = str_replace( '/article/archive/', "", $href );
                        
                        list ( $categoryID, $aID ) = split( "/", $href );
                        $db = & eZDB::instance();
                        $db->setIsSQLOutputEnabled( false );
                        
                        $remoteCategoryID = $articlePrefix . "_article_category_" . $categoryID;
                        $categoryNodeArray = $db->arrayQuery( "SELECT ezcontentobject_tree.node_id FROM ezcontentobject, ezcontentobject_tree
                                                               WHERE ezcontentobject.remote_id = '$remoteCategoryID' AND ezcontentobject.id = ezcontentobject_tree.contentobject_id" );
                        $categoryNodeID = $categoryNodeArray[0]['node_id'];
                        $href = "/content/view/full/" . $categoryNodeID;
                    }
                    
                    if ( preg_match( "/^(www)/i", $href ) )
                    {
                        $href = "http://" . $href;
                    }
                    
                    if ( $target != null )
                        $output .= "<link href='$href' target='$target'>" . $text . "</link>";
                    else
                        $output .= "<link href='$href'>" . $text . "</link>";
                }
                break;
            
            case 'mail':
                {
                    $to = $tag->attributeValue( 'to' );
                    $text = $tag->attributeValue( 'text' );
                    $output .= "<link href='mailto:$to'>" . $text . "</link>";
                }
                break;
            case 'pre':
                {
                    $tagContent = htmlspecialchars( $childTagText );
                    $tagContent = preg_replace( "#&lt;br&gt;#", "\n", $tagContent );
                    $tagContent = preg_replace( "#&lt;p&gt;#", "\n\n", $tagContent );
                    $tagContent = preg_replace( "#&lt;linebreak&gt;#", "\n", $tagContent );
                    $tagContent = preg_replace( "#&lt;literalbr&gt;#", "&lt;br&gt;", $tagContent );
                    $tagContent = preg_replace( "#&lt;literalp&gt;#", "&lt;p&gt;", $tagContent );
                    $output .= "<literal>$tagContent</literal>";
                }
                break;
            case 'html':
                {
                    $tagContent = htmlspecialchars( $childTagText );
                    $tagContent = preg_replace( "#&lt;br&gt;#", "\n", $tagContent );
                    $tagContent = preg_replace( "#&lt;p&gt;#", "\n\n", $tagContent );
                    $tagContent = preg_replace( "#&lt;linebreak&gt;#", "\n", $tagContent );
                    $tagContent = preg_replace( "#&lt;literalbr&gt;#", "&lt;br&gt;", $tagContent );
                    $tagContent = preg_replace( "#&lt;literalp&gt;#", "&lt;p&gt;", $tagContent );
                    $output .= "<literal class='html'>$tagContent</literal>";
                }
                break;
            default:
                {
                    $output .= "<custom name='$tagName'>" . $childTagText . "</custom>";
                }
        }
        return $output;
    }
    
    /// Contains the article ID
    var $ArticleID;
    var $Prefix;
}
?>
