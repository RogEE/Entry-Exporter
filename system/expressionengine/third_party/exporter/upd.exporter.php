<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Exporter - Module Update File
 *
 * @author		Aaron Waldon <http://www.causingeffect.com> for Michael Rog
 * @copyright	Copyright (c) 2013
 * @license		All rights reserved
 */

if ( ! defined('EXPORTER_VERSION') )
{
	include( PATH_THIRD . 'exporter/config.php' );
}

class Exporter_upd {
	
	public $version = EXPORTER_VERSION;
	
	private $EE;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE = get_instance();
	}
	
	/**
	 * Installation Method
	 *
	 * @return boolean true
	 */
	public function install()
	{
		$mod_data = array(
			'module_name'			=> 'Exporter',
			'module_version'		=> $this->version,
			'has_cp_backend'		=> 'y',
			'has_publish_fields'	=> 'n'
		);
		
		$this->EE->db->insert('modules', $mod_data);

		//setup the tables
		$this->setup_tables();

		//add actions
		$this->EE->db->insert( 'actions', array( 'class' => 'Exporter', 'method' => 'export' ) );

		return true;
	}

	/**
	 * Uninstall
	 *
	 * @return boolean true
	 */	
	public function uninstall()
	{
		$this->EE->db->cache_off();
		
		//get the module id
		$mod_id = $this->EE->db->select( 'module_id' )->get_where( 'modules', array( 'module_name'	=> 'Exporter' ) )->row( 'module_id' );
		
		//remove the module by id from the module member groups
		$this->EE->db->where( 'module_id', $mod_id )->delete( 'module_member_groups' );
		
		//remove the module
		$this->EE->db->where( 'module_name', 'Exporter' )->delete( 'modules' );
		
		//remove the actions
		$this->EE->db->where( 'class', 'Exporter' );
		$this->EE->db->delete( 'actions' );

		//remove the installed tables
		if ( $this->EE->db->table_exists( 'exporter_exported' ) )
		{
			$this->EE->load->dbforge();
			$this->EE->dbforge->drop_table( 'exporter_exported' );
		}

		if ( $this->EE->db->table_exists( 'exporter_configs' ) )
		{
			$this->EE->load->dbforge();
			$this->EE->dbforge->drop_table( 'exporter_configs' );
		}

		return true;
	}
	
	/**
	 * Update
	 *
	 * @param string $current
	 * @return 	boolean 	TRUE
	 */	
	public function update($current = '')
	{
		//if up-do-date or a new install, then there's no update
		if ( $current == $this->version )
		{
			return false;
		}

		/*
		if ( version_compare( $current, '1.1', '<' )  )
		{
		}
		*/

		return true;
	}

	private function setup_tables()
	{
		$this->EE->db->cache_off();

		//since one or more tables may have been dropped, let's clear the table name cache.
		unset( $this->EE->db->data_cache['table_names'] );

		//create the cache table for the db driver
		if ( ! $this->EE->db->table_exists( 'exporter_exported' ) )
		{
			$this->EE->load->dbforge();

			//specify the fields
			$fields = array(
				'entry_id' => array( 'type' => 'INT', 'constraint' => '10', 'null' => false, 'unsigned' => true, 'auto_increment' => false ),
				'exported_date' => array( 'type' => 'INT', 'constraint' => '10' )
			);
			$this->EE->dbforge->add_field( $fields );
			$this->EE->dbforge->add_key( 'entry_id', true );
			$this->EE->dbforge->create_table( 'exporter_exported' );
		}

		//create the cache breaking table
		if ( ! $this->EE->db->table_exists( 'exporter_configs' ) )
		{
			$this->EE->load->dbforge();

			//specify the fields
			$fields = array(
				'id' => array( 'type' => 'INT', 'constraint' => '10', 'null' => false, 'unsigned' => true, 'auto_increment' => true ),
				'name' => array( 'type' => 'VARCHAR', 'constraint' => '250', 'default' => '' ),
				'filename' => array( 'type' => 'VARCHAR', 'constraint' => '250', 'default' => '' ),
				'package' => array( 'type' => 'VARCHAR', 'constraint' => '250', 'default' => '' ),
				'relative_path' => array( 'type' => 'VARCHAR', 'constraint' => '250', 'default' => '' ),
				'template_id' => array( 'type' => 'INT', 'constraint' => '10', 'null' => false, 'unsigned' => true ),
				'zip' => array( 'type' => 'VARCHAR', 'constraint' => '1', 'default' => 'y' )
			);
			$this->EE->dbforge->add_field( $fields );
			$this->EE->dbforge->add_key( 'id', true );
			$this->EE->dbforge->create_table( 'exporter_configs' );
		}
	}
}