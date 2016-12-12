<?php
/*
Plugin Name: CHIPS Data Background Processing
Description: Background processing in WordPress.
Author: CHIPS
Version: 0.1
Author URI: http://chips.nyc
*/

require_once plugin_dir_path( __FILE__ ) . 'data-background-setup.php';



require_once plugin_dir_path( __FILE__ ) . 'options.php';
class Data_Background_Processing {
	// protected $process_all;
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 100 );
		add_action( 'save_post', array( $this, 'process_handler' ) );

		//Example: add bulk build action to edit page and resultant notice
		add_action('admin_footer-edit.php', array(&$this, 'custom_bulk_admin_footer'));
		add_action('load-edit.php',         array(&$this, 'custom_bulk_action'));
		add_action('admin_notices',         array(&$this, 'custom_bulk_admin_notices'));

		// Custom Rules
		global $BuildSteps;
		if(!empty($BuildSteps)){
			foreach($BuildSteps as $BuildStep){
				if($BuildStep->allowAjaxBuild == 1){
					add_action( 'wp_ajax_build_' . $BuildStep->buildName, array( $this, 'process_handler') );
				}
			}
		}
		add_action( 'wp_ajax_build_static_page', array( $this, 'process_id') );
	}
		
	public function init() {
		if(class_exists('WP_Background_Process')){
			
			global $BuildSteps;
			if(!empty($BuildSteps)){
				foreach($BuildSteps as $BuildStep){
					require_once plugin_dir_path( __FILE__ ) . 'types/'.$BuildStep->buildName.'.php';
					$tempName = $BuildStep->definedCB;
					$tempKey = $BuildStep->definedVar;
					if($tempName !== "" && $tempName !== null){
						$this->$tempKey = new $tempName();
					}
				}
			}

		}
	}

	//Function that adds admin bar button
	public function admin_bar( $wp_admin_bar ) { 
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$wp_admin_bar->add_menu( array(
			'id'    => 'chips-bg-plugin',
			'title' => __( 'Rebuild Cache and Data Files', 'chips-bg-plugin' ),
			'href'  => '/wp-admin/options-general.php?page=chips-data-admin',
		) );
	}

	// public function 

	//Handler function for process routing
	public function process_handler() {
		if (get_option( 'chips_data_option_name' )) {
			$opts = get_option( 'chips_data_option_name' );
			if (isset($opts['data_root']) === TRUE) {
				$data_root = $opts['data_root'];	
			}
			if (isset($opts['data_root']) === TRUE) {
				$data_html = $opts['data_html'];
			}
		}
		$upload_dir = wp_upload_dir();
		chipsBGProcess::splitBuildProcess($_POST,$this);
	}

	public function process_id() {
		if (isset($_POST['post_ID'])) {
			wp_update_post(array('ID' => $_POST['post_ID']));
		}
	}

	//Add bulk action to dropdown
	public function custom_bulk_admin_footer() {
		global $post_type;
		
		if($post_type == 'post') {
			?>
				<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery('<option>').val('build').text('<?php _e('Build flat file')?>').appendTo("select[name='action']");
						jQuery('<option>').val('build').text('<?php _e('Build flat file')?>').appendTo("select[name='action2']");
						jQuery('#doaction').on('click', function(e) {
							// e.preventDefault();
							if(jQuery('#bulk-action-selector-top')[0].value == 'build') {
								if (jQuery('.updated')[0]) {
									jQuery('.updated').html('<p style="display:inline-block;">Currently building...<span style="margin-top:0;" class="spinner is-active"></span></p>');
								} else {
									jQuery('.wrap h1').after('<div class="updated"><p style="display:inline-block;">Currently building...<span style="margin-top:0;" class="spinner is-active"></span></p></div>');
								}
							}
						});
					});
				</script>
			<?php
    	}
	}

	//Bulk build handler
	public function custom_bulk_action() {
		global $typenow;
		$post_type = $typenow;
		if($post_type == 'post') {
			// get the action
			$wp_list_table = _get_list_table('WP_Posts_List_Table');
			$action = $wp_list_table->current_action();
			
			$allowed_actions = array("build");
			if(!in_array($action, $allowed_actions)) return;
			
			// security check
			check_admin_referer('bulk-posts');
			
			// make sure ids are submitted
			if(isset($_REQUEST['post'])) {
				$post_ids = array_map('intval', $_REQUEST['post']);
			}
			
			if(empty($post_ids)) return;
			
			// this is based on wp-admin/edit.php
			$sendback = remove_query_arg( array('built', 'untrashed', 'deleted', 'ids'), wp_get_referer() );
			if ( ! $sendback )
				$sendback = admin_url( "edit.php?post_type=$post_type" );
			
			$pagenum = $wp_list_table->get_pagenum();
			$sendback = add_query_arg( 'paged', $pagenum, $sendback );
			
			switch($action) {
				case 'build':
					$built = 0;
					foreach( $post_ids as $post_id ) {
						$this->process_post->push_to_queue( $post_id );
						$built++;
					}
					$this->process_post->save()->dispatch();
					$sendback = add_query_arg( array('built' => $built, 'ids' => join(',', $post_ids) ), $sendback );
				break;
				
				default: return;
			}
			
			$sendback = remove_query_arg( array('action', 'action2', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status',  'post', 'bulk_edit', 'post_view'), $sendback );
			wp_redirect($sendback);
			exit();
		}
	}

	//Display an admin notice on the posts page after a bulk build
	public function custom_bulk_admin_notices() {
		global $post_type, $pagenow;
		if($pagenow == 'edit.php' && $post_type == 'post' && isset($_REQUEST['built']) && (int) $_REQUEST['built']) {
			$message = sprintf( _n( 'Post flat file built.', '%s post flat files built.', $_REQUEST['built'] ), number_format_i18n( $_REQUEST['built'] ) );
			echo "<div class=\"updated\"><p>{$message}</p></div>";
		}
	}
}

register_activation_hook( __FILE__, 'child_plugin_activate' );
function child_plugin_activate(){
	// Require parent plugin
	if ( ! is_plugin_active( 'wp-background-processing/wp-background-processing.php' ) and current_user_can( 'activate_plugins' ) ) {
		// Stop activation redirect and show error
		wp_die('Sorry, but this plugin requires the WP Background Processing plugin to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
	}
}

new Data_Background_Processing();