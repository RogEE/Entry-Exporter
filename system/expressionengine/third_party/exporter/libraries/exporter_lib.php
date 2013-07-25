<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Exporter Library
 *
 * @author		Aaron Waldon <http://www.causingeffect.com> for Michael Rog
 * @copyright	Copyright (c) 2013
 * @license		All rights reserved
 */

class Exporter_lib
{
	public function __construct()
	{
		$this->EE = get_instance();
	}

	/**
	 * Runs the export using the entry_ids and the config_id.
	 *
	 * @param array $entry_ids
	 * @param int $config_id
	 * @throws Exception
	 * @return string|bool The error message or true.
	 */
	public function export_from_config( $entry_ids, $config_id )
	{
		//validate the config id
		if ( empty( $config_id ) )
		{
			throw new Exception( lang('exporter_exception_config_required') );
		}
		if ( ! is_numeric( $config_id ) )
		{
			throw new Exception( lang('exporter_exception_config_invalid' ) );
		}

		//load the model
		$this->EE->load->model( 'exporter_config_model' );

		//get the config
		if ( ! empty( $config_id ) && is_numeric( $config_id ) && $config_id > 0 )
		{
			$result = $this->EE->exporter_config_model->get_config( $config_id );

			if ( empty( $result ) )
			{
				throw new Exception( lang( 'exporter_exception_config_not_found' ) );
			}
		}

		//ensure the needed information is present
		if ( ! isset( $result['filename'] )
			|| ! isset( $result['package'] )
			|| ! isset( $result['template_id'] )
			|| ! isset( $result['relative_path'] )
			|| ! isset( $result['zip'] ) )
		{
			throw new Exception( lang('exporter_exception_config_invalid_info') );
		}

		return $this->export( $entry_ids, $result['filename'], $result['package'], $result['template_id'], $result['relative_path'], $result['zip'] );
	}

	/**
	 * Exports one or more entries through a template.
	 *
	 * @param array $entry_ids
	 * @param string $filename
	 * @param string $package_name
	 * @param int $template_id
	 * @param string $relative_path
	 * @param string $zip
	 * @throws Exception
	 * @return string|bool The error message or true.
	 */
	public function export( $entry_ids, $filename, $package_name, $template_id, $relative_path = '', $zip = 'y' )
	{
		//load the string helper
		$this->EE->load->helper('string');

		//validate entry_ids
		if ( empty( $entry_ids ) || ! is_array( $entry_ids ) )
		{
			throw new Exception( lang('exporter_exception_no_entry') );
		}
		foreach( $entry_ids as $entry_id )
		{
			if ( ! is_numeric( $entry_id ) )
			{
				throw new Exception( lang('exporter_exception_invalid_entry_id') );
			}
		}

		//validate zip preference
		$zip = ( $zip == 'y' || $zip === TRUE || $zip == 'yes' || $zip === 1 ) ? true : false;

		//make sure the provided template exists
		$this->EE->load->model( 'exporter_templates_model' );
		if ( ! $template = $this->EE->exporter_templates_model->get_template_content( $template_id ) )
		{
			throw new Exception( lang('exporter_exception_template_not_found'));
		}

		//base path
		$base = $this->EE->config->item( 'exporter_base_path' );
		if ( $base === false )
		{
			//default to document root
			$base = isset( $_SERVER['DOCUMENT_ROOT'] ) ? $_SERVER['DOCUMENT_ROOT'] : FCPATH;
		}
		$base = str_replace('\\', '/', $this->EE->security->xss_clean( $base ) );
		$base = reduce_double_slashes( $base . '/' );

		//load the template library
		$this->EE->load->library('template', null, 'TMPL');

		//get the entry information for each entry
		$entry_info = $this->get_entry_info( $entry_ids );
		if ( empty( $entry_info ) )
		{
			throw new Exception( lang('exporter_exception_entry_not_found') );
		}

		//process each template
		foreach ( $entry_info as $entry_id => $url_title )
		{
			//create the dynamic filename
			$filename_temp = str_replace(
				array( '{entry_id}', '{url_title}' ),
				array( $entry_id, $url_title ),
				$filename
			);

			//create the dynamic package name
			$package_name_temp = str_replace(
				array( '{entry_id}', '{url_title}' ),
				array( $entry_id, $url_title ),
				$package_name
			);

			//replace the entry id
			$entry_template = str_replace( '{exporter:entry_id}', $entry_id, $template );

			//package path
			$package_path = reduce_double_slashes( $base . '/' . $relative_path . '/' . $package_name_temp );
			$package_path = str_replace('\\', '/', $this->EE->security->xss_clean( $package_path ) );

			//filename path
			$file_path = reduce_double_slashes( $package_path . '/' . $filename_temp );
			$file_path = str_replace('\\', '/', $this->EE->security->xss_clean( $file_path ) );

			//check the path and create the folders if needed
			if ( ! $this->create_directories_for_path( $base, $package_path ) )
			{
				throw new Exception( sprintf( lang( 'exporter_exception_cache_path_problem' ), $package_path ) );
			}

			//process the template
			$this->EE->TMPL = new EE_Template();
			$this->EE->TMPL->parse( $entry_template );
			$entry_template = $this->EE->TMPL->final_template;

			//process the assets
			$entry_template = $this->capture_files( $entry_template, $package_path );

			//save the export
			if ( ! $this->save_text_to_file( $entry_template, $file_path ) )
			{
				throw new Exception( sprintf( lang( 'exporter_exception_save_problem' ), $file_path ) );
			}

			//create zip if applicable
			if ( $zip )
			{
				if ( ! $this->zip( $package_path, $package_path . '.zip' ) )
				{
					throw new Exception( lang('exporter_exception_zip_problem') );
				}
			}

			//log the save
			$this->log_export( $entry_id );
		}

		return true;
	}

	/**
	 *
	 * @param array $ids
	 * @return array
	 */
	private function get_entry_info( $ids )
	{
		$final = array();

		$this->EE->db->select('entry_id, url_title');
		$this->EE->db->where_in('entry_id', $ids);
		$results = $this->EE->db->get('exp_channel_titles');

		if ( $results->num_rows() > 0 )
		{
			foreach ($results->result_array() as $row)
			{
				$final[ $row['entry_id'] ] = $row['url_title'];
			}
		}

		return $final;
	}

	/**
	 * Save the text to a file.
	 *
	 * @param string $text The text to save.
	 * @param string $file_path The full server path.
	 * @return bool True on success, false on failure.
	 */
	private function save_text_to_file( $text, $file_path )
	{
		//write the file
		if ( write_file( $file_path, $text ) )
		{
			//set the file to full permissions
			@chmod( $file_path, 0777 );
			unset( $file_path, $data );
			return true;
		}

		unset( $file, $data );

		return false;
	}

	/**
	 * Download all the files between the {exporter:capture_file}...{/exporter:capture_file} tags and make the URLs relative to the directory path.
	 *
	 * @param string $template_string
	 * @param string $package_path
	 * @return string
	 */
	private function capture_files( $template_string, $package_path )
	{
		//match all capture file tags
		if ( preg_match_all( '@\{exporter\:capture_file\}(.*)\{\/exporter\:capture_file\}@Us', $template_string, $matches, PREG_SET_ORDER ) )
		{
			//load the string helper
			$this->EE->load->helper('string');

			//get the site_url
			$site_url = $this->EE->config->item( 'site_url' );

			foreach ( $matches as $match )
			{
				$file = $relative = $match[1];

				//relative to the export folder
				$relative = basename( $file );

				//full server path to downloaded asset
				$path = reduce_double_slashes( $package_path . '/' . $relative  );

				//download the asset
				if ( substr( $file, 0, 7 ) === 'http://' || substr( $file, 0, 8 ) === 'https://' || substr( $file, 0, 2 ) === '//' ) //remote URL
				{
					//
				}
				else //local URL
				{
					$file = reduce_double_slashes( $site_url . '/' . $file );
				}

				//download the asset to the path
				file_put_contents( $path, file_get_contents( $file ) );

				//swap out the new path
				$template_string = str_replace( $match[0], $relative, $template_string );
			}
		}

		return $template_string;
	}

	/**
	 * Create the folder structure for the export path.
	 *
	 * @param string $base The base path.
	 * @param string $package_path The file path.
	 * @return bool
	 */
	private function create_directories_for_path( $base, $package_path )
	{
		//load the file helper
		$this->EE->load->helper( 'file' );

		//get the directories
		$directories = rtrim( $package_path, '/' );

		//create the directories with the correct permissions as needed
		if ( ! @is_dir( $directories ) )
		{
			//turn the directory path into an array of directories
			$directories = explode( '/', substr( $directories, strlen( $base ) ) );

			//assign the current variable
			$current = $base;

			//start with base, and add each directory and make sure it exists with the proper permissions
			foreach ( $directories as $directory )
			{
				$current .= '/' . $directory;

				//check if the directory exists
				if ( ! @is_dir( $current ) )
				{
					//try to make the directory with full permissions
					@mkdir( $current . '/', 0777, true );
				}
			}

			//ensure the directory is writable
			if ( ! is_really_writable( $current ) )
			{
				return false;
			}
		}

		unset( $directories );
		return true;
	}

	/**
	 * Zip a directory and all of its files.
	 *
	 * Code based on StackOverflow answer: http://stackoverflow.com/a/1334949/1136822
	 *
	 * @param $source
	 * @param $destination
	 * @return bool
	 */
	private function zip( $source, $destination )
	{
		if ( ! extension_loaded('zip') || ! file_exists( $source ) )
		{
			return false;
		}

		$zip = new ZipArchive();
		if ( ! $zip->open( $destination, ZIPARCHIVE::CREATE ) ) {
			return false;
		}

		$source = str_replace('\\', '/', realpath($source));

		if (is_dir($source) === true)
		{
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

			foreach ($files as $file)
			{
				$file = str_replace( '\\', '/', $file );

				//ignore dots
				if( in_array( substr( $file, strrpos( $file, '/' ) + 1 ), array( '.', '..' ) ) )
				{
					continue;
				}

				$file = str_replace('\\', '/', realpath($file));

				if ( is_dir($file) === true )
				{
					$zip->addEmptyDir( str_replace($source . '/', '', $file . '/') );
				}
				else if ( is_file($file) === true )
				{
					$zip->addFromString( str_replace($source . '/', '', $file), file_get_contents($file) );
				}
			}
		}
		else if ( is_file( $source ) === true )
		{
			$zip->addFromString( basename($source), file_get_contents($source) );
		}

		return $zip->close();
	}

	private function log_export( $entry_id )
	{
		$sql = 'REPLACE INTO exp_exporter_exported SET entry_id = ?, exported_date = ?';
		$this->EE->db->query( $sql, array( $entry_id, time() ) );
	}
}