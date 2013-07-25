<?php
if ( count( $configs ) > 0 )
{

	//the control panel table template
	$this->table->set_template( $cp_table_template );

	//set the headings
	$this->table->set_heading(
		lang( 'exporter_config_id' ),
		lang( 'exporter_config_name' ),
		lang( 'exporter_config_filename' ),
		lang( 'exporter_config_package' ),
		lang( 'exporter_config_relative_path' ),
		lang( 'exporter_config_template' ),
		lang( 'exporter_config_zip' ),
		'&nbsp;',
		'&nbsp;'
	);

	//set the rows
	foreach( $configs as $config )
	{
		$this->table->add_row(
			$config['id'],
			$config['name'],
			$config['filename'],
			$config['package'],
			$config['relative_path'],
			$templates[ $config['template_id'] ],
			( $config['zip'] == 'y' ) ? lang('exporter_config_yes') : lang('exporter_config_no'),
			'<a href="'. BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=exporter'.AMP.'method=config_entry'.AMP.'id='. $config['id']  . '">'. lang('exporter_edit_config') . '</a>',
			'<a href="'. BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=exporter'.AMP.'method=config_delete_entry'.AMP.'id='. $config['id'] . '">'. lang('exporter_delete_config') . '</a>'
		);
	}

	//generate the table
	echo $this->table->generate();
}
else
{
	echo lang('exporter_no_configs');
}

echo '<a href="'. BASE.AMP.'C=addons_modules' . AMP . 'M=show_module_cp' . AMP . "module=exporter" . AMP . 'method=config_entry" class="submit">'. lang('exporter_new_config_button') . '</a>';
?>