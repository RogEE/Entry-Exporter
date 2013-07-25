<?php

//output the error message, if any
if ( ! empty( $error_message ) )
{
	echo '<p class="exporter-error">' . $error_message . '</p>';
}

//do we have any config items?
$has_configs = count( $configs ) > 0;

if ( $has_configs )
{
	echo form_open( $action_url, array( 'id' => 'exporter-form' ) );
}

//the table
echo $table_html;

//the pagination
echo $pagination_html;

if ( $has_configs )
{
	echo '<div class="tableSubmit">';
		//add instructions
		$configs = array( '' => lang('exporter_please_select_config') ) + $configs;

		//the configuration
		echo form_dropdown('config_id', $configs, set_value('config_id', ''), 'id="exporter-config-select"' );
		echo form_error('config_id');
		echo ' ';

		//submit
		echo form_submit( array( 'name' => 'export_submit', 'id' => 'export_submit', 'value' => lang( 'exporter_export_selected' ), 'class' => 'submit' ) );
	echo '</div>';

	//close form
	echo form_close();
}
?>