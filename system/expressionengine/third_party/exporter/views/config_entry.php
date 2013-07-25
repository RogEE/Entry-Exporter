<?php
if ( $show_form ): //we want to show the form

	//open form
	echo form_open( $action_url, '' );

	//id
	echo form_hidden( 'id', set_value('id', $defaults['id']) );
	echo form_error('id');

	//name
	echo '<p>';
	echo form_label( lang( 'exporter_config_name' ), 'name' );
	echo '<br>';
	echo '<span class="instructions">' . lang( 'exporter_config_name_instructions' ) . '</span>';
	echo '<br>';
	echo form_input(
		array(
			'name'        => 'name',
			'id'          => 'name',
			'value'       => set_value('name', $defaults['name']),
			'maxlength'   => '250',
			'size'        => '250'
		)
	);
	echo '</p>';
	echo form_error('name');

	//filename
	echo '<p>';
	echo form_label( lang( 'exporter_config_filename' ), 'filename' );
	echo '<br>';
	echo '<span class="instructions">' . lang( 'exporter_config_filename_instructions' ) . '</span>';
	echo '<br>';
	echo form_input(
		array(
			'name'        => 'filename',
			'id'          => 'filename',
			'value'       => set_value('filename', $defaults['filename']),
			'maxlength'   => '250',
			'size'        => '250'
		)
	);
	echo '</p>';
	echo form_error('filename');

	//package
	echo '<p>';
	echo form_label( lang( 'exporter_config_package' ), 'package' );
	echo '<br>';
	echo '<span class="instructions">' . lang( 'exporter_config_package_instructions' ) . '</span>';
	echo '<br>';
	echo form_input(
		array(
			'name'        => 'package',
			'id'          => 'package',
			'value'       => set_value('package', $defaults['package']),
			'maxlength'   => '250',
			'size'        => '250'
		)
	);
	echo '</p>';
	echo form_error('package');

	//relative path
	echo '<p>';
	echo form_label( lang( 'exporter_config_relative_path' ), 'relative_path' );
	echo '<br>';
	echo form_input(
		array(
			'name'        => 'relative_path',
			'id'          => 'relative_path',
			'value'       => set_value('relative_path', $defaults['relative_path']),
			'maxlength'   => '250',
			'size'        => '250'
		)
	);
	echo '</p>';
	echo form_error('relative_path');

	//template_id
	echo '<p>';
	echo form_label( lang( 'exporter_config_template' ), 'template_id' );
	echo '<br>';
	echo form_dropdown('template_id', $templates, set_value('template_id', $defaults['template_id']));
	echo '</p>';
	echo form_error('template_id');

	//zip
	echo '<p>';
	echo form_label( lang( 'exporter_config_zip' ), 'zip' );
	echo '<br>';
	$zip = set_value('zip', $defaults['zip']);
	echo form_checkbox('zip', 'y', ($zip == 'y'));
	echo '</p>';
	echo form_error('zip');

	//submit
	echo form_submit( array( 'name' => 'config_entry_submit', 'value' => lang( 'exporter_config_entry_submit' ), 'class' => 'submit' ) );

	//close form
	echo form_close();
else:
	echo '<p>' . lang( 'exporter_config_' . $method . '_success' ) . ' <br><br> <a class="submit" href="' . BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=exporter'.AMP.'method=configs'.AMP. '">' . lang('exporter_config_return_to_config_index') . '</a></p>';
endif;
?>