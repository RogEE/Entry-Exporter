<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Exporter Config Model
 *
 * @author		Aaron Waldon <http://www.causingeffect.com> for Michael Rog
 * @copyright	Copyright (c) 2013
 * @license		All rights reserved
 */

class Exporter_config_model
{
	public function __construct()
	{
		$this->EE =& get_instance();
	}

	/**
	 * Get all of the config rows.
	 *
	 * @return array
	 */
	public function get_configs()
	{
		$sql = 'SELECT * FROM exp_exporter_configs';

		$results = $this->EE->db->query( $sql );

		if ( $results->num_rows() > 0 )
		{
			return $results->result_array();
		}

		return array();
	}

	/**
	 * Get a specific config row.
	 *
	 * @param $id
	 * @return array
	 */
	public function get_config( $id )
	{
		$results = $this->EE->db->query( 'SELECT * FROM exp_exporter_configs WHERE id = ?', array( $id ) );

		if ( $results->num_rows() > 0 )
		{
			return $results->row_array();
		}

		return array();
	}

	/**
	 * Delete a config row.
	 *
	 * @param $id
	 * @return void
	 */
	public function delete_config( $id )
	{
		$sql = 'DELETE FROM exp_exporter_configs WHERE id = ?';
		$this->EE->db->query( $sql, array( $id ) );

		return;
	}

	/**
	 * Inserts a new config row.
	 *
	 * @param array $data
	 * @return int The row id.
	 */
	public function insert_config( $data )
	{
		$this->EE->db->insert( 'exp_exporter_configs', $data );

		return $this->EE->db->insert_id();
	}

	/**
	 * Updates a config row.
	 *
	 * @param int $id The config row id.
	 * @param array $data
	 * @return bool
	 */
	public function update_config( $id, $data )
	{
		//make sure the result exists
		$results = $this->EE->db->query( 'SELECT * FROM exp_exporter_configs WHERE id = ?', array( $id ) );
		if ( $results->num_rows() != 1 ) //the result does not exist
		{
			return false;
		}

		//update the result
		$this->EE->db->where( 'id', $id );
		$this->EE->db->update( 'exp_exporter_configs', $data );

		return true;
	}

	/**
	 * Gets the id => name array of the config rows to be used in a dropdown.
	 *
	 * @return array
	 */
	public function get_config_dropdown_data()
	{
		$templates = array();

		$query = $this->EE->db->query( 'SELECT id, name FROM exp_exporter_configs' );

		foreach ($query->result_array() as $row)
		{
			$templates[$row['id']] = $row['name'];
		}

		return $templates;
	}

	/**
	 * Lists all of the config entries.
	 *
	 * @return mixed
	 */
	public function view_config_entries()
	{
		//load the models
		$this->EE->load->model( 'exporter_templates_model' );

		//load needed classes
		$this->EE->load->library('table');
		$this->EE->load->helper('form');

		$vars = array();

		//get the templates
		$vars['templates'] = $this->EE->exporter_templates_model->get_templates_dropdown_data();

		//get the configs
		$vars['configs'] = $this->get_configs();

		//new config URL
		$vars['new_config_url'] = 'C=addons_modules' . AMP . 'M=show_module_cp' . AMP . "module=exporter" . AMP . 'method=config_entry';

		//return the view
		return $this->EE->load->view( 'config_entries', $vars, true );
	}

	/**
	 * Handles adding and editing a single config entry.
	 *
	 * @return string
	 */
	public function edit_config_entry()
	{
		//load classes/helpers
		$this->EE->load->helper( array('form', 'url') );

		//load the form validation library like normal
		$this->EE->load->library('form_validation');

		//include the Ce_validation class
		if ( ! class_exists( 'Ce_validation' ) )
		{
			require PATH_THIRD . 'ce_validation/Ce_validation.php';
		}

		//load the models
		$this->EE->load->model( 'exporter_templates_model' );

		//defaults
		$defaults = array(
			'id' => '',
			'name' => '',
			'filename' => '{url_title}.html',
			'package' => '{entry_id}',
			'relative_path' => '',
			'template_id' => '',
			'zip' => 'y'
		);
		$method = 'add';

		//grab the id
		$id = $this->EE->input->get_post( 'id', TRUE );

		if ( ! empty( $id ) && is_numeric( $id ) )
		{
			$result = $this->get_config( $id );

			if ( ! empty( $result ) )
			{
				$defaults = $result;
				$method = 'edit';
			}
		}

		//override the original
		$this->EE->form_validation = new Ce_validation();

		//error delimiter
		$this->EE->form_validation->set_error_delimiters( '<p class="exporter-error">', '</p>');

		//rules
		$this->EE->form_validation->set_rules( 'id', '&ldquo;' . lang( 'exporter_config_id_text' ) . '&rdquo;', 'trim|is_natural' );
		$this->EE->form_validation->set_rules( 'name', '&ldquo;' . lang( 'exporter_config_name' ) . '&rdquo;', 'trim|required|min_length[3]|max_length[250]' );
		$this->EE->form_validation->set_rules( 'filename', '&ldquo;' . lang( 'exporter_config_filename' ) . '&rdquo;', 'trim|required|min_length[3]|max_length[250]' );
		$this->EE->form_validation->set_rules( 'package', '&ldquo;' . lang( 'exporter_config_package' ) . '&rdquo;', 'trim|required|min_length[3]|max_length[250]' );
		$this->EE->form_validation->set_rules( 'relative_path', '&ldquo;' . lang( 'exporter_config_relative_path' ) . '&rdquo;', 'trim|max_length[250]' );
		$this->EE->form_validation->set_rules( 'template_id', '&ldquo;' . lang( 'exporter_config_template' ) . '&rdquo;', 'trim|is_natural_no_zero' );
		$this->EE->form_validation->set_rules( 'zip', '&ldquo;' . lang( 'exporter_config_zip' ) . '&rdquo;', 'trim|callback_valid_checkbox' );

		//custom messages
		$this->EE->form_validation->set_message('valid_checkbox', lang('exporter_config_zip_error_message'));

		//custom callbacks
		function valid_checkbox( $str )
		{
			return ( $str == '' || $str == 'y' );
		}

		//flag to show the form (TRUE) or the confirmation page (FALSE)
		$show_form = true;

		if ( $this->EE->input->post( 'config_entry_submit', true ) !== false ) //the form was submitted
		{
			//run validation
			if ( $this->EE->form_validation->run() ) //success
			{
				//exit('here');

				//at this point, we should have all of the data we need
				$data = array(
					'id' => set_value( 'id' ),
					'name' => set_value( 'name' ),
					'filename' => set_value( 'filename' ),
					'package' => set_value( 'package' ),
					'relative_path' => set_value( 'relative_path' ),
					'template_id' => set_value( 'template_id' ),
					'zip' => set_value( 'zip' )
				);

				$base_url = BASE . AMP . 'C=addons_modules' . AMP . 'M=show_module_cp' . AMP . 'module=exporter';

				if ( $method == 'add' )
				{
					$result = $this->insert_config( $data );

					if ( ! empty( $result ) ) //the result should be fine
					{
						$this->EE->session->set_flashdata('message_success', lang('exporter_config_add_success') );
						$this->EE->functions->redirect( $base_url. AMP . 'method=configs' );
					}
				}
				else if ( $method == 'edit' )
				{
					$result = $this->update_config( set_value( 'id' ), $data );

					if ( ! empty( $result ) ) //the result should be fine
					{
						$this->EE->session->set_flashdata('message_success', lang('exporter_config_edit_success') );
						$this->EE->functions->redirect( $base_url. AMP . 'method=configs' );
					}
				}
			}
		}

		//form action
		$vars[ 'action_url' ] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=exporter'.AMP.'method=config_entry';
		$vars[ 'show_form' ] = $show_form;
		$vars[ 'defaults' ] = $defaults;
		$vars[ 'method' ] = $method;
		$vars[ 'templates' ] = $this->EE->exporter_templates_model->get_templates_dropdown_data();

		$theme_url = $this->get_theme_url();

		//add the css
		$this->EE->cp->add_to_head( '<link rel="stylesheet" href="' . $theme_url . 'css/exporter.css">' );

		//return the view
		return $this->EE->load->view( 'config_entry', $vars, TRUE );
	}

	/**
	 * Handles the deletion of a config entry.
	 *
	 * @return string
	 */
	public function delete_config_entry()
	{
		//load classes/helpers
		$this->EE->load->helper( array('form', 'url') );
		$this->EE->load->library( 'form_validation' );

		//grab the driver from the get/post data
		$id = $this->EE->input->get_post( 'id', TRUE );

		if ( empty( $id ) || ! is_numeric( $id ) )
		{
			return '<p>' . lang( 'exporter_config_delete_no_entry' ) . '</p>';
		}

		$show_form = TRUE;

		if ( $this->EE->input->post( 'submit', TRUE ) !== FALSE ) //the form was submitted
		{
			//delete the item
			$this->delete_config( $id );

			//item was deleted successfully
			$show_form = FALSE;

			$base_url = BASE . AMP . 'C=addons_modules' . AMP . 'M=show_module_cp' . AMP . 'module=exporter';
			$this->EE->session->set_flashdata('message_success', lang('exporter_config_delete_success') );
			$this->EE->functions->redirect( $base_url. AMP . 'method=configs' );
		}

		//view data
		$data = array(
			'id' => $id,
			'action_url' => 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=exporter'.AMP.'method=config_delete_entry',
			'show_form' => $show_form
		);

		//return the view
		return $this->EE->load->view( 'config_delete', $data, TRUE );
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