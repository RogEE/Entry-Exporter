<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Exporter Templates Model
 *
 * @author		Aaron Waldon <http://www.causingeffect.com> for Michael Rog
 * @copyright	Copyright (c) 2013
 * @license		All rights reserved
 */

class Exporter_templates_model
{
	public function __construct()
	{
		$this->EE = get_instance();
	}

	/**
	 * Gets the template ids => group/template array to be used in a dropdown.
	 *
	 * @return array
	 */
	public function get_templates_dropdown_data()
	{
		$templates = array();

		$query = $this->EE->db->query("SELECT exp_template_groups.group_name, exp_templates.template_name, exp_templates.template_id
FROM exp_template_groups, exp_templates
WHERE exp_template_groups.group_id = exp_templates.group_id
AND exp_templates.site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'");

		foreach ($query->result_array() as $row)
		{
			$templates[$row['template_id']] = $row['group_name'].'/'.$row['template_name'];
		}

		asort( $templates );

		return $templates;
	}

	/**
	 * Retrieve template content.
	 *
	 * @param $id
	 * @return string|bool The template content on success and false on failure.
	 */
	public function get_template_content( $id )
	{
		$template_content = false;

		$results = $this->EE->db->query( 'SELECT
		et.template_data,
		et.save_template_file,
		etg.group_name,
		et.template_name,
		et.template_type
		FROM exp_templates et
		LEFT JOIN exp_template_groups etg
		ON et.group_id = etg.group_id
		WHERE et.template_id = ?', array( $id ) );

		if ( $results->num_rows() > 0 )
		{
			$template_content = $results->row('template_data');

			if ($this->EE->config->item('save_tmpl_files') === 'y' && $this->EE->config->item('tmpl_file_basepath')  && $results->row('save_template_file') === 'y')
			{
				//load the api
				$this->EE->load->library('api');
				$this->EE->api->instantiate('template_structure');

				$basepath = rtrim( $this->EE->config->item('tmpl_file_basepath'), '/') . '/';
				$basepath .= $this->EE->config->item('site_short_name').'/'.$results->row('group_name').'.group/'.$results->row('template_name').$this->EE->api_template_structure->file_extensions($results->row('template_type'));

				if ( file_exists( $basepath ) )
				{
					$template_content = file_get_contents($basepath);
				}
			}
		}

		return $template_content;
	}
}