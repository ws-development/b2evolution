<?php
/**
 * This file implements the DataObjectCache class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants Fran�ois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Fran�ois PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Data Object Cache Class
 *
 * @package evocore
 * @version beta
 */
class DataObjectCache
{
	/**#@+
	 * @access private
	 */
	var	$objtype;
	var	$dbtablename;
	var $dbprefix;
	var $dbIDname;
	var $cache = array();
	var $load_add = false;
	var $all_loaded = false;
	/**#@-*/

	/**
	 * Constructor
	 *
	 * {@internal DataObjectCache::DataObjectCache(-) }}
	 *
	 * @param string Name of DataObject class we are cacheing
	 * @param boolean true if it's OK to just load all items!
	 * @param string Name of table in database
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 */
	function DataObjectCache( $objtype, $load_all, $tablename, $prefix = '', $dbIDname = 'ID' )
	{
		$this->objtype = $objtype;
		$this->load_all = $load_all;
		$this->dbtablename = $tablename;
		$this->dbprefix = $prefix;
		$this->dbIDname = $dbIDname;
	}


	/**
	 * Load the cache **extensively**
	 *
	 * {@internal DataObjectCache::load_all(-) }}
	 */
	function load_all()
	{
		global $DB, $Debuglog;

		if( $this->all_loaded )
			return	false;	// Already loaded;

		$Debuglog->add( get_class($this).' - Loading <strong>'.$this->objtype.'(ALL)</strong> into cache', 'dataobjects' );
		$sql = "SELECT * FROM $this->dbtablename";
		$dbIDname = $this->dbIDname;
		$objtype = $this->objtype;
		foreach( $DB->get_results( $sql ) as $row )
		{
			if( $objtype == 'Element' )
			{ // Instanciate a dataobject with its params:
				$this->cache[ $row->$dbIDname ] = new Element( $this->dbtablename, $this->dbprefix, $this->dbIDname, $row ); // COPY!
			}
			else
			{ // Instantiate a custom object
				$this->cache[ $row->$dbIDname ] = new $objtype( $row ); // COPY!
			}
			// $obj = $this->cache[ $row->$dbIDname ];
			// $obj->disp( 'name' );
		}

		$this->all_loaded = true;

		return true;
	}


	/**
	 * Load a list of objects into the cache
	 *
	 * {@internal DataObjectCache::load_list(-) }}
	 *
	 * @param string list of IDs of objects to load
	 */
	function load_list( $req_list )
	{
		global $DB, $Debuglog;

		$Debuglog->add( "Loading <strong>$this->objtype($req_list)</strong> into cache", 'dataobjects' );

		if( empty( $req_list ) )
		{
			return false;
		}

		$sql = "SELECT *
							FROM $this->dbtablename
						 WHERE $this->dbIDname IN ($req_list)";
		$dbIDname = $this->dbIDname;
		$objtype = $this->objtype;
		foreach( $DB->get_results( $sql ) as $row )
		{
			$this->cache[ $row->$dbIDname ] = new $objtype( $row ); // COPY!
			// $obj = $this->cache[ $row->$dbIDname ];
			// $obj->disp( 'name' );
		}
	}


	function get_ID_array()
	{
		$IDs = array();

		foreach( $this->cache as $obj )
		{
			$IDs[] = $obj->ID;
		}

		return $IDs;
	}


	/**
	 * Add a dataobject to the cache
	 *
	 * {@internal DataObjectCache::add(-) }}
	 */
	function add( & $Obj )
	{
		if( !empty($Obj->ID) && !isset($this->cache[$Obj->ID]) )
		{	// If the object is valid and not already cached:
			$this->cache[$Obj->ID] = & $Obj;
			return true;
		}
		return false;
	}


	/**
	 * Instantiate a DataObject from a table row and then cache it.
	 *
	 * @param Object Database row
	 */
	function instantiate( & $db_row )
	{
		// Get ID of the object we'ere preparing to instantiate...
		$obj_ID = $db_row->{$this->dbIDname};

 		if( !empty($obj_ID) && !isset($this->cache[$obj_ID]) )
		{	// If the object ID is valid and not already cached:
			$Obj = new $this->objtype( $db_row ); // COPY !!
			$this->add( $Obj );
		}
	}


	/**
	 * Clear the cache **extensively**
	 *
	 * {@internal DataObjectCache::clear(-) }}
	 */
	function clear()
	{
		$this->cache = array();
		$this->all_loaded = false;
	}


	/**
	 * Get an object from cache by ID
	 *
	 * Load the cache if necessary (all at once if allowed).
	 *
	 * {@internal DataObjectCache::get_by_ID(-) }}
	 *
	 * @param integer ID of object to load
	 * @param boolean true if function should die on error
	 * @param boolean true if function should die on empty/null
	 * @return reference on cached object
	 */
	function & get_by_ID( $req_ID, $halt_on_error = true, $halt_on_empty = true )
	{
		global $DB, $Debuglog;

		if( empty($req_ID) )
		{
			if($halt_on_empty) die( "Requested $this->objtype from $this->dbtablename without ID!" );
			return NULL;
		}

		if( !empty( $this->cache[ $req_ID ] ) )
		{ // Already in cache
			// $Debuglog->add( "Accessing $this->objtype($req_ID) from cache", 'dataobjects' );
			return $this->cache[ $req_ID ];
		}
		elseif( !$this->all_loaded )
		{ // Not in cache, but not everything is loaded yet
			if( $this->load_all )
			{ // It's ok to just load everything:
				$this->load_all();
			}
			else
			{ // Load just the requested object:
				$Debuglog->add( "Loading <strong>$this->objtype($req_ID)</strong> into cache", 'dataobjects' );
				$sql = "SELECT * FROM $this->dbtablename WHERE $this->dbIDname = $req_ID";

				if( $row = $DB->get_row( $sql, OBJECT, 0, 'DataObjectCache::get_by_ID()' ) )
				{
					//$Debuglog->add( 'success', 'dataobjects' );
					if( ! $this->add( new $this->objtype( $row ) ) )
					{
						$Debuglog->add( 'Could not add() object to cache!', 'dataobjects' );
					}
				}
				else
				{
					$Debuglog->add( 'Could not get DataObject by ID. Query: '.$sql, 'dataobjects' );
				}
			}
		}

		if( empty( $this->cache[ $req_ID ] ) )
		{ // Requested object does not exist
			// $Debuglog->add( 'failure', 'dataobjects' );
			if( $halt_on_error )
			{
				die( "Requested $this->objtype does not exist!" );
			}
			return false;
		}

		return $this->cache[ $req_ID ];
	}


	/**
	 * Display form option list with cache contents
	 *
	 * Load the cache if necessary
	 *
	 * {@internal DataObjectCache::option_list(-) }}
	 *
	 * @param integer selected ID
	 * @param boolean provide a choice for "none" with ID ''
	 */
	function option_list( $default = 0, $allow_none = false, $method ='name' )
	{
		if( (! $this->all_loaded) && $this->load_all )
		{ // We have not loaded all items so far, but we're allowed to... so let's go:
			$this->load_all();
		}

		if( $allow_none )
		{
			echo '<option value=""';
			if( empty($default) ) echo ' selected="selected"';
			echo '>', T_('None') ,'</option>'."\n";
		}

		foreach( $this->cache as $loop_Obj )
		{
			echo '<option value="'.$loop_Obj->ID.'"';
			if( $loop_Obj->ID == $default ) echo ' selected="selected"';
			echo '>';
			$loop_Obj->$method();
			echo '</option>'."\n";
		}
	}


	/**
	 * Returns form option list with cache contents
	 *
	 * Load the cache if necessary
	 *
	 * {@internal DataObjectCache::option_list_return(-) }}
	 *
	 * @param integer selected ID
	 * @param boolean provide a choice for "none" with ID ''
	 */
	function option_list_return( $default = 0, $allow_none = false, $method = 'name_return' )
	{
		if( (! $this->all_loaded) && $this->load_all )
		{ // We have not loaded all items so far, but we're allowed to... so let's go:
			$this->load_all();
		}

		$r = '';

		if( $allow_none )
		{
			$r .= '<option value=""';
			if( empty($default) ) $r .= ' selected="selected"';
			$r .= '>'.T_('None').'</option>'."\n";
		}

		foreach( $this->cache as $loop_Obj )
		{
			$r .=  '<option value="'.$loop_Obj->ID.'"';
			if( $loop_Obj->ID == $default ) $r .= ' selected="selected"';
			$r .= '>';
			$r .= $loop_Obj->$method();
			$r .=  '</option>'."\n";
		}

		return $r;
	}
}

/*
 * $Log$
 * Revision 1.22  2005/09/06 17:13:54  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.21  2005/08/02 18:15:14  fplanque
 * fix for correct NULL handling
 *
 * Revision 1.20  2005/07/15 18:10:07  fplanque
 * allow instantiating of member objects (used for preloads)
 *
 * Revision 1.19  2005/06/10 18:25:44  fplanque
 * refactoring
 *
 * Revision 1.18  2005/05/16 15:17:13  fplanque
 * minor
 *
 * Revision 1.17  2005/05/11 13:21:38  fplanque
 * allow disabling of mediua dir for specific blogs
 *
 * Revision 1.16  2005/04/19 16:23:02  fplanque
 * cleanup
 * added FileCache
 * improved meta data handling
 *
 * Revision 1.15  2005/03/14 20:22:19  fplanque
 * refactoring, some cacheing optimization
 *
 * Revision 1.14  2005/03/02 15:24:29  fplanque
 * allow get_by_ID(NULL) in some situations
 *
 * Revision 1.13  2005/02/28 09:06:32  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.12  2005/02/14 21:17:45  blueyed
 * optimized cache handling
 *
 * Revision 1.11  2005/02/09 00:27:13  blueyed
 * Removed deprecated globals / userdata handling
 *
 * Revision 1.10  2005/02/08 04:45:02  blueyed
 * improved $DB get_results() handling
 *
 * Revision 1.9  2005/01/20 18:46:26  fplanque
 * debug
 *
 * Revision 1.8  2005/01/13 19:53:50  fplanque
 * Refactoring... mostly by Fabrice... not fully checked :/
 *
 * Revision 1.7  2004/12/27 18:37:58  fplanque
 * changed class inheritence
 *
 * Revision 1.5  2004/12/21 21:18:38  fplanque
 * Finished handling of assigning posts/items to users
 *
 * Revision 1.4  2004/12/17 20:38:52  fplanque
 * started extending item/post capabilities (extra status, type)
 *
 * Revision 1.3  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.2  2004/10/14 16:28:40  fplanque
 * minor changes
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.18  2004/10/12 10:27:18  fplanque
 * Edited code documentation.
 *
 */
?>