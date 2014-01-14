<?php

/*
=====================================================

RogEE "Entry Exporter"
an add-on for ExpressionEngine 2
by Michael Rog and Aaron Waldon

Contact Michael with questions, feedback, suggestions, bugs, etc.
>> http://rog.ee

=====================================================
*/

if (!defined('APP_VER') || !defined('BASEPATH')) { exit('No direct script access allowed'); }


class Exporter {

	public $return_data;

	public function __construct()
	{
		$this->EE = get_instance();
	}

	/**
	 * Export one or more entries from an action URL
	 */
	public function export()
	{
		//this is an action url; we don't want this method to be called from the template parser
		if ( isset( $this->EE->TMPL ) )
		{
			exit();
		}

		//load the language file
		$this->EE->lang->loadfile( 'exporter' );

		//the default values
		$filename = '{url_title}.html';
		$package_name = '{entry_id}';
		$template_id = '';
		$relative_path = '';
		$zip = 'y';

		//settings
		$setting_keys = array( 'filename', 'package', 'template_id', 'relative_path', 'zip' );

		//do we have a config?
		$config_id = $this->EE->input->get_post( 'config_id', TRUE );

		//entry_ids
		$entry_ids = $this->EE->input->get_post( 'entry_ids', TRUE );

		//try to prep the entry_ids as much as possible
		if ( ! empty( $entry_ids ) && ! is_array( $entry_ids ) )
		{
			if ( is_numeric( $entry_ids ) )
			{
				$entry_ids = (array) $entry_ids;
			}
			else if ( strpos( $entry_ids, '|' ) !== false )
			{
				$entry_ids = explode( '|', $entry_ids );
			}
		}

		//if we have a config id, let's load its settings
		if ( ! empty( $config_id ) )
		{
			//ensure the config id is numeric
			if ( ! is_numeric( $config_id ) )
			{
				exit( lang('exporter_exception_config_invalid') );
			}

			//load the model
			$this->EE->load->model( 'exporter_config_model' );

			//get the config
			if ( ! empty( $config_id ) && is_numeric( $config_id ) && $config_id > 0 )
			{
				$result = $this->EE->exporter_config_model->get_config( $config_id );

				if ( empty( $result ) )
				{
					throw new Exception( lang('exporter_exception_config_not_found') );
				}
			}

			//ensure the needed information is present
			foreach ( $setting_keys as $setting )
			{
				if ( ! isset( $result[ $setting ] ) )
				{
					throw new Exception( lang('exporter_exception_config_invalid_info') );
				}

				$$setting = $result[ $setting ];
			}
		}

		//now check for setting overrides
		foreach ( $setting_keys as $setting )
		{
			$temp = $this->EE->input->get_post( $setting, TRUE );
			if ( $temp !== false )
			{
				$$setting = $temp;
			}
		}

		//load the exporter library
		$this->EE->load->add_package_path( PATH_THIRD.'exporter/' );
		$this->EE->load->library('exporter_lib');

		//export the items from the config
		try {
			$this->EE->exporter_lib->export( $entry_ids, $filename, $package_name, $template_id, $relative_path, $zip );
		}
		catch ( Exception $e )
		{
			exit( $e->getMessage() );
		}

		//success
		exit( lang('exporter_export_success') );
	}
}