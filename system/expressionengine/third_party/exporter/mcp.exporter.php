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

class Exporter_mcp {
	
	public $return_data;
	private $base_url;
	private $is_legacy;
	
	//pagination per page
	private $perpage = 50;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
		
		$this->EE->load->add_package_path( 'exporter' );
		
		if ( ! $this->EE->input->is_ajax_request() )
		{
			//ensure BASE is defined - http://devot-ee.com/add-ons/support/wyvern/viewthread/2238#7469
			if ( ! defined('BASE') )
			{
				$s = ( $this->EE->config->item('admin_session_type' ) != 'c') ? $this->EE->session->userdata('session_id') : 0;
				define( 'BASE', SELF.'?S='.$s.'&amp;D=cp' );
			}
	
			$this->base_url = BASE . AMP . 'C=addons_modules' . AMP . 'M=show_module_cp' . AMP . 'module=exporter';

			if ( isset( $this->EE->cp ) ) //run this check just in case the is_ajax_request is wrong
			{
				$this->EE->cp->set_right_nav(
					array(
						'exporter_index' => $this->base_url,
						'exporter_configurations' => $this->base_url . AMP . 'method=configs',
						'exporter_action_url' => $this->base_url . AMP . 'method=action_url'
					)
				);
			}
		}

		$this->is_legacy = version_compare( APP_VER, '2.6.0', '<' );
	}

	/**
	 * View and export entries.
	 * 
	 * @return mixed
	 */
	public function index()
	{
		if ( $this->is_legacy )
		{
			$this->EE->cp->set_variable( 'cp_page_title', lang( 'exporter_index') );
		}
		else
		{
			$this->EE->view->cp_page_title = lang( 'exporter_index');
		}

		//load needed classes
		$this->EE->load->library( 'table' );
		$this->EE->load->helper( 'form' );

		//whether or not to show the table view
		$show_table = true;

		//the error message
		$error_message = '';

		//handle form submission
		if ( $this->EE->input->post( 'export_submit', true ) !== false ) //the form was submitted
		{
			//configuration id
			$config_id = $this->EE->input->post( 'config_id' );
			if ( empty( $config_id ) || ! is_numeric( $config_id ) )
			{
				$error_message = lang( 'exporter_config_export_no_config_selected' );
			}

			//entry_ids
			$entry_ids = $this->EE->input->post( 'toggle' );
			if( empty( $entry_ids ) || ! is_array( $entry_ids ) || count( $entry_ids ) < 1 )
			{
				$error_message = lang( 'exporter_config_export_no_entries_selected' );
			}

			//handle any error messages
			if ( ! empty( $error_message ) )
			{
				$this->EE->session->set_flashdata('message_failure', $error_message );
				$this->EE->functions->redirect( $this->base_url );
			}

			//load the exporter library
			$this->EE->load->add_package_path( PATH_THIRD.'exporter/' );
			$this->EE->load->library('exporter_lib');

			//export the items from the config
			try {
				$this->EE->exporter_lib->export_from_config( $entry_ids, $config_id );
			}
			catch ( Exception $e )
			{
				$this->EE->session->set_flashdata('message_failure', $e->getMessage() );
				$this->EE->functions->redirect( $this->base_url );
			}

			$this->EE->session->set_flashdata('message_success', lang('exporter_export_success') );
			$this->EE->functions->redirect( $this->base_url );
		}

		//create the table
		$this->EE->table->set_columns( array(
			'entry_id' => array( 'header' => lang('exporter_entry_id') ),
			'title' => array( 'header' => lang('exporter_title') ),
			'entry_date' => array( 'header' => lang('exporter_date') ),
			'channel_title' => array( 'header' => lang('exporter_channel') ),
			'exported_date' => array( 'header' => lang('exporter_last_exported_date') ),
			'_check'		=> array(
				'header' => form_checkbox('select_all', 'true', false, 'class="toggle_all"'),
				'sort' => false
			)
		));
		//initial sort
		$initial_state = array(
			'sort'	=> array('entry_date' => 'desc')
		);

		//params
		$params = array(
			'perpage'	=> $this->perpage
		);
		$vars = $this->EE->table->datasource('_entries_filter', $initial_state, $params);

		//the form action url
		$vars[ 'action_url' ] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=exporter'.AMP.'method=index';

		//the config dropdown data
		$this->EE->load->model( 'exporter_config_model' );
		$vars['configs'] = $this->EE->exporter_config_model->get_config_dropdown_data();

		//the error message, if any
		$vars['error_message'] = $error_message;

		//get the theme URL
		$theme_url = $this->get_theme_url();

		//add the css
		$this->EE->cp->add_to_head( '<link rel="stylesheet" href="' . $theme_url . 'css/exporter.css">' );

		$javascript = '<script type="text/javascript">
$(document).ready( function() {
	$("#exporter-form").submit( function() {
		//make sure a config item is selected
		if ( $("#exporter-config-select").val() == "" )
		{
			alert( "' . lang('exporter_index_no_config_selected' ) . '" );
			return false;
		}

		//make sure at least one entry is checked
		if ( $(\'input[name="toggle[]"]:checked\').length < 1 )
		{
			alert( "' . lang('exporter_index_no_ids_selected' ) . '" );
			return false;
		}
	});
});
</script>';


		//add the js
		$this->EE->cp->add_to_foot( $javascript );

		//return the view
		return $this->EE->load->view( 'entries', $vars, true );
	}

	/**
	 * The configs page.
	 *
	 * @return string
	 */
	public function configs()
	{
		//set the breadcrumb for the index page
		$this->EE->cp->set_breadcrumb( $this->base_url . AMP . 'method=index', lang('exporter_module_name') );

		//set the page title
		if ( $this->is_legacy )
		{
			$this->EE->cp->set_variable( 'cp_page_title', lang( 'exporter_configurations') );
		}
		else
		{
			$this->EE->view->cp_page_title = lang( 'exporter_configurations');
		}

		//load the model
		$this->EE->load->model( 'exporter_config_model' );

		//render
		return $this->EE->exporter_config_model->view_config_entries();
	}

	/**
	 * Add/edit an entry.
	 *
	 * @return string
	 */
	public function config_entry()
	{
		//set the breadcrumbs
		$this->EE->cp->set_breadcrumb( $this->base_url . AMP . 'method=index', lang('exporter_module_name') );
		$this->EE->cp->set_breadcrumb( $this->base_url . AMP . 'method=configs', lang('exporter_configurations') );

		//set the page title
		if ( $this->is_legacy )
		{
			$this->EE->cp->set_variable( 'cp_page_title', lang( 'exporter_config_entry') );
		}
		else
		{
			$this->EE->view->cp_page_title = lang( 'exporter_config_entry');
		}

		//load the model
		$this->EE->load->model( 'exporter_config_model' );

		//render
		return $this->EE->exporter_config_model->edit_config_entry();
	}

	/**
	 * Delete an entry.
	 *
	 * @return string
	 */
	public function config_delete_entry()
	{
		//set the breadcrumbs
		$this->EE->cp->set_breadcrumb( $this->base_url . AMP . 'method=index', lang('exporter_module_name') );
		$this->EE->cp->set_breadcrumb( $this->base_url . AMP . 'method=configs', lang('exporter_configurations') );

		//set the page title
		if ( version_compare( APP_VER, '2.6.0', '<' ) )
		{
			$this->EE->cp->set_variable( 'cp_page_title', lang('exporter_config_delete_entry') );
		}
		else
		{
			$this->EE->view->cp_page_title = lang('exporter_config_delete_entry');
		}

		//load the model
		$this->EE->load->model( 'exporter_config_model' );

		//render
		return $this->EE->exporter_config_model->delete_config_entry();
	}

	/**
	 * Provides the action url to trigger entry exports.
	 */
	public function action_url()
	{
		//set the breadcrumbs
		$this->EE->cp->set_breadcrumb( $this->base_url . AMP . 'method=index', lang('exporter_module_name') );

		//set the page title
		if ( version_compare( APP_VER, '2.6.0', '<' ) )
		{
			$this->EE->cp->set_variable( 'cp_page_title', lang('exporter_action_url') );
		}
		else
		{
			$this->EE->view->cp_page_title = lang('exporter_action_url');
		}

		return $this->EE->functions->create_url( QUERY_MARKER . 'ACT=' . $this->EE->cp->fetch_action_id( 'Exporter', 'export' ) );;
	}

	/**
	 * Filters the raw votes table results.
	 *
	 * @param array $state
	 * @param array $params
	 * @return array
	 */
	public function _entries_filter( $state, $params )
	{
		//load the model
		$this->EE->load->model( 'exporter_entries_model' );

		//load the form helper
		$this->EE->load->helper( 'form' );

		//date string
		$date_format = ($this->EE->session->userdata('time_format') != '') ? $this->EE->session->userdata('time_format') : $this->EE->config->item('time_format');
		$date_string = '%m/%d/%y %h:%i %a';
		if ( $date_format != 'us' )
		{
			$date_string = '%Y-%m-%d %H:%i';
		}

		//entries
		$rows = $this->EE->exporter_entries_model->get_entries( $params['perpage'], $state['offset'], $state['sort'] );
		foreach ( $rows as $index => $row )
		{
			$rows[$index]['_check'] = form_checkbox('toggle[]', $row['entry_id'], '', ' class="toggle" id="export_check_'.$row['entry_id'].'"');
			$rows[$index]['exported_date'] = empty( $row['exported_date'] ) ? '---' : $this->EE->localize->decode_date( $date_string, $row['exported_date'] );
			$rows[$index]['entry_date'] = $this->EE->localize->decode_date( $date_string, $row['entry_date'], TRUE);
		}

		//return array
		return array(
			'rows' => $rows,
			'no_results' => '<p>'.lang('exporter_no_results').'</p>',
			'pagination' => array(
				'per_page' => $params['perpage'],
				'total_rows' => $this->EE->db->count_all('exp_channel_titles')
			)
		);
	}

	/**
	 * Get the third_party theme folder URL.
	 *
	 * @return string
	 */
	private function get_theme_url()
	{
		$theme_folder_url = defined('URL_THIRD_THEMES') ? URL_THIRD_THEMES : $this->EE->config->slash_item('theme_folder_url').'third_party/';
		return $theme_folder_url . 'exporter/';
	}
}