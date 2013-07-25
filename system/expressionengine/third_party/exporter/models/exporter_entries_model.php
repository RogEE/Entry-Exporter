<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Exporter Entries Model
 *
 * @author		Aaron Waldon <http://www.causingeffect.com> for Michael Rog
 * @copyright	Copyright (c) 2013
 * @license		All rights reserved
 */

class Exporter_entries_model
{
	public function __construct()
	{
		$this->EE =& get_instance();
	}

	/**
	 * Retrieves the channel entries and their last export date.
	 *
	 * @param int $limit
	 * @param int $offset
	 * @param array $order
	 * @return array
	 */
	public function get_entries( $limit = 50, $offset = 0, $order = array() )
	{
		$sql = 'SELECT ct.entry_id, ct.title, ct.entry_date, ec.channel_title, ex.exported_date
		FROM exp_channel_titles as ct
		LEFT JOIN exp_exporter_exported as ex
		ON ct.entry_id = ex.entry_id
		LEFT JOIN exp_channels ec
		ON ct.channel_id = ec.channel_id
		';

		if ( is_array($order) && count($order) > 0 )
		{
			foreach ( $order as $key => $val )
			{
				$sql .= ' ORDER BY ' . $this->EE->db->escape_str( $key ) . ' ' . $this->EE->db->escape_str($val);
			}
		}
		else
		{
			$this->EE->db->order_by('entry_date', 'desc');
		}

		if ( ! is_numeric( $limit ) )
		{
			$limit = 50;
		}

		if ( ! is_numeric( $offset ) )
		{
			$offset = 0;
		}

		$sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;

		$results = $this->EE->db->query( $sql );

		if ( $results->num_rows() > 0 )
		{
			return $results->result_array();
		}

		return array();
	}

	public function view_entries()
	{

	}
}