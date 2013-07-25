<?php
if ( $show_form ): //show the form

	//open form
	echo form_open( $action_url, '' );

	//item id
	echo form_hidden( 'id', $id );

	echo '<p>' . lang( 'exporter_config_delete_confirm' ) . '</p>';

	//submit
	echo form_submit( array( 'name' => 'submit', 'value' => lang( 'exporter_config_delete_confirmed' ), 'class' => 'submit' ) );

	//close form
	echo form_close();

else: //show the success message

	echo '<p>' . lang( 'exporter_config_delete_success' ) . '</p>';

endif;
?>