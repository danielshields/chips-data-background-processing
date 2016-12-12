<?php
class buildPageStatic extends WP_Background_Process {
	protected $action = 'build_page';
	
	protected function task( $item ) {
		// if ($item == 93) {
		// }
		return false;
	} 

	protected function complete() {
		// error_log('build_post process complete');
		parent::complete();
	}

}