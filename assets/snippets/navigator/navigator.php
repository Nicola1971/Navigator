<?php

// ===================================================================
// Snippet: Navigator
// ===================================================================
// Version: 1.0.5
// Date: 2026.11.03
// Author: PMS
// Licence: GPL GNU - Public
// Credit: I used some code from the Wayfinder snippet when figuring out how to do templating.

// Version History:
// 0.1   - initial release
// 0.1.1 - converted to be PHP 4 compatible
// 0.1.2 - fixed a bug affecting skipping of unpublished documents
// 0.1.3 - fixed bug affecting the use of templates stored in files and in chunks.
// 1.0.3 - A magic jump in version number
// 1.0.4 - added isFolderAction (by Nicola1971)
// 1.0.5 - PHP 8.x compatibility (by Nicola1971):
//         - __construct() instead of PHP 4 style constructor (not called on PHP 8)
//         - null-safe parameters, empty ids removed from stopIds/offIds
//         - getPageInfo()/getTemplateVarOutput() returning false handled
//         - TV and document values mapped by name instead of by position
//         - @FILE: paths relative to MODX_BASE_PATH, missing file handled

class Navigator
{

  // Internal parameters
  var $relVals;
  var $weblinkActionVals;
  var $unpublishedActionVals;
  var $notInMenuActionVals;
  var $isFolderActionVals;
  var $placeHolderFieldVals;

  // User supplied parameters
  var $rel;
  var $stopIds;
  var $offIds;
  var $transcend;
  var $weblinkAction;
  var $unpublishedAction;
  var $notInMenuAction;
  var $isFolderAction;
  var $templateSource;
  var $modx;

  // Derived parameters
  var $stopIdArray;
  var $offIdArray;
  var $template;


  function __construct( $params = array() )
  {
    global $modx;
    $this->modx = $modx;

    if ( ! is_array( $params ) )
    {
      $params = array();
    }

    // Set allowed values
    $this->relVals = array('up', 'prev', 'next');
    $this->weblinkActionVals = array('skip', 'stop', 'link');
    $this->unpublishedActionVals = array('skip', 'stop', 'link');
    $this->notInMenuActionVals = array('skip', 'stop', 'link');
    $this->isFolderActionVals = array('skip', 'stop', 'link');
    $this->placeHolderFieldVals = array('nav.rel');

    // Set default values
    $this->rel = 'up';
    $this->stopIds = '';
    $this->offIds = '';
    $this->transcend = 1;
    $this->weblinkAction = 'skip';
    $this->unpublishedAction = 'skip';
    $this->notInMenuAction = 'skip';
    $this->isFolderAction = 'skip';
    $this->templateSource = '';
    $this->template = '';

    $this->stopIdArray = array();
    $this->offIdArray = array();

    if ( isset( $params['rel'] ) && in_array( $params['rel'], $this->relVals, true ) )
    {
      $this->rel = $params['rel'];
    }

    if ( isset( $params['stopIds'] ) )
    {
      $this->stopIds = (string)$params['stopIds'];
      $this->stopIdArray = $this->IdList( $this->stopIds );
    }

    if ( isset( $params['offIds'] ) )
    {
      $this->offIds = (string)$params['offIds'];
      $this->offIdArray = $this->IdList( $this->offIds );
    }

    if ( isset( $params['transcend'] ) )
    {
      $this->transcend = $params['transcend'] ? 1 : 0;
    }

    if ( isset( $params['weblinkAction'] ) && in_array( $params['weblinkAction'], $this->weblinkActionVals, true ) )
    {
      $this->weblinkAction = $params['weblinkAction'];
    }

    if ( isset( $params['unpublishedAction'] ) && in_array( $params['unpublishedAction'], $this->unpublishedActionVals, true ) )
    {
      $this->unpublishedAction = $params['unpublishedAction'];
    }

    if ( isset( $params['notInMenuAction'] ) && in_array( $params['notInMenuAction'], $this->notInMenuActionVals, true ) )
    {
      $this->notInMenuAction = $params['notInMenuAction'];
    }

    if ( isset( $params['isFolderAction'] ) && in_array( $params['isFolderAction'], $this->isFolderActionVals, true ) )
    {
      $this->isFolderAction = $params['isFolderAction'];
    }

    if ( isset( $params['template'] ) )
    {
      $this->templateSource = (string)$params['template'];
    }

  }

  // Converts a comma separated list of ids into an array of integers (empty values removed)
  function IdList( $value )
  {
    $ids = array();
    foreach ( explode( ',', str_replace( ' ', '', (string)$value ) ) as $item )
    {
      if ( $item !== '' && is_numeric( $item ) )
      {
        $ids[] = (int)$item;
      }
    }
    return $ids;
  }

  function Calculate( )
  {

    // ======================================================
    // Calculation
    // ======================================================

    if ( ! is_object( $this->modx ) || empty( $this->modx->documentIdentifier ) )
    {
      return '';
    }

    // Get the current document id
    $currentId = (int)$this->modx->documentIdentifier;

    // If the snippet has been switched off for this document, don't display it
    if ( in_array( $currentId, $this->offIdArray ) )
    {
      return '';
    }

    // Get the content
    $this->template = $this->GetTemplate( $this->templateSource );
    if ( $this->template === FALSE || $this->template === '' )
    {
      // Could create a template based on type here...
      return '';
    }

    while ( true )
    {

      switch ( $this->rel )
      {
        case 'up':
          $id = $this->GetParentId( $currentId );
          break;
        case 'prev':
          $id = $this->GetPreviousDocId( $currentId );
          break;
        case 'next':
          $id = $this->GetNextDocId( $currentId );
          break;
        default:
          return '';
      }

      $id = (int)$id;

      if ( $id <= 0 )
      {
        return '';
      }

      if ( $this->IsStopId( $id ) )
      {
        return '';
      }

      if ( $this->IsSkipId( $id ) )
      {
        $currentId = $id;
      }
      else
      {
        break;
      }
    }

    return $this->GetOutput( $id );
  }

  function GetTemplate( $source )
  {
    // based on a version in Wayfinder 2.0... which was...
    // based on version by Doze at http://modxcms.com/forums/index.php/topic,5344.msg41096.html#msg41096
    $source = (string)$source;
    if ( $source === '' )
    {
      return FALSE;
    }

    $mode = substr( $source, 0, 6 );
    $content = substr( $source, 6 );

    if ( $mode == '@FILE:' )
    {
      return $this->GetFileContents( trim( $content ) );
    }
    if ( $mode == '@CODE:' )
    {
      return $content;
    }

    $template = (string)$this->modx->getChunk( $source );
    if ( $template !== '' )
    {
      return $template;
    }

    return FALSE;
  }

  function GetFileContents( $filename )
  {
    // Relative paths are resolved from the site root
    if ( ! is_file( $filename ) && defined( 'MODX_BASE_PATH' ) )
    {
      $filename = MODX_BASE_PATH . ltrim( $filename, '/' );
    }
    if ( ! is_file( $filename ) || ! is_readable( $filename ) )
    {
      return FALSE;
    }
    $fcontents = file_get_contents( $filename );
    return ( $fcontents === FALSE ) ? FALSE : $fcontents;
  }

  function GetPlaceHolderFieldsArray( $tpl )
  {
    // Extract the place holders from the document...
    preg_match_all( '~\[\+(.*?)\+\]~', (string)$tpl, $matches );
    return array_unique( $matches[1] );
  }

  function GetTVArray()
  {
    // Gets an array of all template variables
    $table = $this->modx->getFullTableName( 'site_tmplvars' );
    $tvs = $this->modx->db->select( 'name', $table );
    // TODO: make it so that it only pulls those that apply to the current template
    $dbfields = array();
    while ( $dbfield = $this->modx->db->getRow( $tvs ) )
    {
      $dbfields[] = $dbfield['name'];
    }
    return $dbfields;
  }

  function GetOutput( $id )
  {
    // Creates the output in the given format.

    // Check that the id is valid
    $id = (int)$id;
    if ( $id <= 0 )
    {
      return '';
    }

    $placeHolderFieldsArray = $this->GetPlaceHolderFieldsArray( $this->template );

    // Navigator placeholders
    $nlPlaceHoldersArray = array();
    $nlPlaceHolderValuesArray = array();
    foreach ( $placeHolderFieldsArray as $item => $field )
    {
      if ( in_array( $field, $this->placeHolderFieldVals, true ) )
      {
        $nlPlaceHoldersArray[] = '[+' . $field . '+]';
        switch ( $field )
        {
          case 'nav.rel':
            $nlPlaceHolderValuesArray[] = $this->rel;
            break;
          default:
            $nlPlaceHolderValuesArray[] = '';
        }
        unset( $placeHolderFieldsArray[$item] );
      }
    }

    // Template variable placeholders
    $tvPlaceHoldersArray = array();
    $tvPlaceHolderFieldsArray = array();
    if ( count( $placeHolderFieldsArray ) > 0 )
    {
      $tvArray = $this->GetTVArray();
      foreach ( $placeHolderFieldsArray as $item => $field )
      {
        if ( in_array( $field, $tvArray, true ) )
        {
          $tvPlaceHoldersArray[] = '[+' . $field . '+]';
          $tvPlaceHolderFieldsArray[] = $field;
          unset( $placeHolderFieldsArray[$item] );
        }
      }
    }

    $tvPlaceHolderValuesArray = array();
    if ( count( $tvPlaceHolderFieldsArray ) > 0 )
    {
      $tvOutput = $this->modx->getTemplateVarOutput( $tvPlaceHolderFieldsArray, $id );
      foreach ( $tvPlaceHolderFieldsArray as $field )
      {
        $tvPlaceHolderValuesArray[] = ( is_array( $tvOutput ) && isset( $tvOutput[$field] ) )
          ? (string)$tvOutput[$field]
          : '';
      }
    }

    // Document field placeholders
    $docPlaceHoldersArray = array();
    $docPlaceHolderFieldsArray = array();
    foreach ( $placeHolderFieldsArray as $field )
    {
      // only plain field names can be passed to the query
      if ( preg_match( '~^[a-zA-Z_][a-zA-Z0-9_]*$~', $field ) )
      {
        $docPlaceHoldersArray[] = '[+' . $field . '+]';
        $docPlaceHolderFieldsArray[] = $field;
      }
    }

    $docPlaceHolderValuesArray = array();
    if ( count( $docPlaceHolderFieldsArray ) > 0 )
    {
      $doc = $this->modx->getPageInfo( $id, 0, implode( ',', $docPlaceHolderFieldsArray ) );
      foreach ( $docPlaceHolderFieldsArray as $field )
      {
        $docPlaceHolderValuesArray[] = ( is_array( $doc ) && isset( $doc[$field] ) )
          ? (string)$doc[$field]
          : '';
      }
    }

    return str_replace(
      array_merge( $nlPlaceHoldersArray, $tvPlaceHoldersArray, $docPlaceHoldersArray ),
      array_merge( $nlPlaceHolderValuesArray, $tvPlaceHolderValuesArray, $docPlaceHolderValuesArray ),
      $this->template
    );

  }

  function GetDocFlags( $id )
  {
    $doc = $this->modx->getPageInfo( (int)$id, 0, 'type,published,hidemenu,isfolder,deleted' );
    return is_array( $doc ) ? $doc : FALSE;
  }

  function IsSkipId( $id )
  {
    if ( $id < 0 )
    {
      return TRUE;
    }
    if ( $id == 0 )
    {
      return FALSE;
    }
    $doc = $this->GetDocFlags( $id );
    if ( $doc === FALSE )
    {
      return TRUE;
    }
    if ( ! empty( $doc['deleted'] ) )
    {
      return TRUE;
    }
    if ( $this->weblinkAction == 'skip' && $doc['type'] == 'reference' )
    {
      return TRUE;
    }
    if ( $this->unpublishedAction == 'skip' && empty( $doc['published'] ) )
    {
      return TRUE;
    }
    if ( $this->notInMenuAction == 'skip' && ! empty( $doc['hidemenu'] ) )
    {
      return TRUE;
    }
    if ( $this->isFolderAction == 'skip' && ! empty( $doc['isfolder'] ) )
    {
      return TRUE;
    }
    return FALSE;

  }

  function IsStopId( $id )
  {
    if ( $id <= 0 )
    {
      return TRUE;
    }
    if ( in_array( (int)$id, $this->stopIdArray ) )
    {
      return TRUE;
    }
    $doc = $this->GetDocFlags( $id );
    if ( $doc === FALSE )
    {
      return TRUE;
    }
    if ( ! empty( $doc['deleted'] ) )
    {
      // deleted documents are skipped, not treated as a stop
      return FALSE;
    }
    if ( $this->weblinkAction == 'stop' && $doc['type'] == 'reference' )
    {
      return TRUE;
    }
    if ( $this->unpublishedAction == 'stop' && empty( $doc['published'] ) )
    {
      return TRUE;
    }
    if ( $this->notInMenuAction == 'stop' && ! empty( $doc['hidemenu'] ) )
    {
      return TRUE;
    }
    if ( $this->isFolderAction == 'stop' && ! empty( $doc['isfolder'] ) )
    {
      return TRUE;
    }
    return FALSE;

  }

  function GetParentId( $id )
  {
    // Gets the id of the parent.
    // If the document is the root, or can't be found, -1 is returned
    if ( $id <= 0 )
    {
      return -1;
    }
    $currentDoc = $this->modx->getPageInfo( (int)$id, 0, 'parent' );
    if ( ! is_array( $currentDoc ) || ! isset( $currentDoc['parent'] ) )
    {
      return -1;
    }
    return (int)$currentDoc['parent'];
  }

  function GetChildren( $id )
  {
    if ( $id < 0 )
    {
      return array();
    }
    $children = $this->modx->getAllChildren( (int)$id, 'menuindex', 'ASC', 'id' );
    return is_array( $children ) ? array_values( $children ) : array();
  }

  function GetFirstChildId( $id )
  {
    // Gets the id of the first child of the document
    // Returns -1 if it can't find one
    $children = $this->GetChildren( $id );
    if ( count( $children ) > 0 && isset( $children[0]['id'] ) )
    {
      return (int)$children[0]['id'];
    }
    return -1;
  }

  function GetLastChildId( $id )
  {
    // Gets the id of the last child of the document
    // Returns -1 if it can't find one
    $children = $this->GetChildren( $id );
    $nChildren = count( $children );
    if ( $nChildren > 0 && isset( $children[$nChildren - 1]['id'] ) )
    {
      return (int)$children[$nChildren - 1]['id'];
    }
    return -1;
  }

  function GetSiblingId( $id )
  {
    // Gets the previous/next sibling (depending on rel).
    // Returns -1 if one doesn't exist

    // If the document is the document root, then it has no siblings
    if ( $id <= 0 )
    {
      return -1;
    }

    $parentId = $this->GetParentId( $id );
    if ( $parentId < 0 )
    {
      return -1;
    }

    // Get the immediate siblings (and the current document)
    $siblings = $this->GetChildren( $parentId );
    $lastIndex = count( $siblings ) - 1;

    $currentIndex = -1;
    foreach ( $siblings as $index => $sibling )
    {
      if ( isset( $sibling['id'] ) && (int)$sibling['id'] == (int)$id )
      {
        $currentIndex = $index;
        break;
      }
    }
    if ( $currentIndex == -1 )
    {
      return -1;
    }

    switch ( $this->rel )
    {
      case 'prev':
        if ( $currentIndex > 0 )
        {
          return (int)$siblings[$currentIndex - 1]['id'];
        }
        break;
      case 'next':
        if ( $currentIndex < $lastIndex )
        {
          return (int)$siblings[$currentIndex + 1]['id'];
        }
        break;
    }

    return -1;
  }

  function GetPreviousDocId( $id )
  {
    $siblingId = $this->GetSiblingId( $id );

    if ( ! $this->transcend )
    {
      return ( $siblingId >= 0 ) ? $siblingId : -1;
    }

    // If the current document has a previous sibling, go down to its last descendant
    while ( $siblingId >= 0 )
    {
      $lastChildId = $this->GetLastChildId( $siblingId );
      if ( $lastChildId < 0 )
      {
        return $siblingId;
      }
      $siblingId = $lastChildId;
    }

    // No previous sibling: the previous document is the parent
    $parentId = $this->GetParentId( $id );
    return ( $parentId >= 0 ) ? $parentId : -1;
  }

  function GetNextDocId( $id )
  {
    if ( ! $this->transcend )
    {
      $siblingId = $this->GetSiblingId( $id );
      return ( $siblingId >= 0 ) ? $siblingId : -1;
    }

    // If the current document has children, the next document is the first child
    $firstChildId = $this->GetFirstChildId( $id );
    if ( $firstChildId >= 0 )
    {
      return $firstChildId;
    }

    // Otherwise the next sibling
    $siblingId = $this->GetSiblingId( $id );
    if ( $siblingId >= 0 )
    {
      return $siblingId;
    }

    // Otherwise the next sibling of the nearest ancestor that has one
    $parentId = $this->GetParentId( $id );
    while ( $parentId > 0 )
    {
      $parentsSiblingId = $this->GetSiblingId( $parentId );
      if ( $parentsSiblingId >= 0 )
      {
        return $parentsSiblingId;
      }
      $parentId = $this->GetParentId( $parentId );
    }

    return -1;
  }

}
