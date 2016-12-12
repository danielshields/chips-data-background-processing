<?php
add_action( 'wp_dashboard_setup', 'chips_add_regen_dashboard_widgets' );
if( !is_admin() ){
	add_action( 'wp_head', 'data_regen_scripts_frontend');
}
//Add link to options page on dashboard
function chips_add_regen_dashboard_widgets() {

wp_add_dashboard_widget(
		'regen_data_link',
		'Regenerable Data Files Checklist',
		'regen_data_checklist_function'
	);

}

function regen_data_checklist_function() {
	echo '<a href="/wp-admin/options-general.php?page=chips-data-admin">Options Page</a>';
}

function data_regen_scripts_frontend(){
	global $chipsOptions;
	$randValOption = get_option('chips_data_option_name');
	if(is_array($randValOption) && !empty($randValOption)){
		$chipsOptions["data_root"] = $randValOption['data_root'];
		$chipsOptions["data_html"] = $randValOption['data_html'];
	}
}

class CHIPSDataSettingsPage
{
	private $options;

	public function __construct()
	{
		
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
		add_action( 'admin_footer', array( $this, 'options_regen_js') );
	}

	public function add_plugin_page()
	{
		// This page will be under "Settings"
		add_options_page(
			'Settings Admin', 
			'CHIPS Data Generator Settings', 
			'manage_options', 
			'chips-data-admin', 
			array( $this, 'create_admin_page' )
		);
	}

	 // Write our JS below here
	public function options_regen_js() { 
		?>
		<script type="text/javascript" >
		jQuery(document).ready(function($) {

			$('#build-by-id').on("click", function(e) {
				e.preventDefault();
				buildPostData('build_static_page', $('#id-input').val(), null);
			});

			$('.regen-section .regen-single').on("click", function(e) {
				e.preventDefault();
				buildPostData($(this).data('action'), $(this).data('id'), $(this).data('slug'), $(this));
				$(this).parent().find('.date').css('color', '#444');
				$(this).parent().find('.date').html('Rebuilding...');
			});
			$('.regen-section.static .regen-all').on("click", function(e) {
				e.preventDefault();
				buildAllStatic();
				$('.regen-section.static .date').css('color', '#444');
				$('.regen-section.static .date').html('Rebuilding...');
			});

			$('.regen-section.data .regen-all').on("click", function(e) {
				e.preventDefault();
				buildAllData();
				$('.regen-section.data .date').css('color', '#444');
				$('.regen-section.data .date').html('Rebuilding...');
			});

			var buildPostData = function(action, id, slug, el){
				var data = {
					'action': action,
					'post_ID': id,
					'slug': slug
				};
				// console.log("Sending ajax request");
				// console.log(data);
				$.post(ajaxurl, data, function(response) {
					if (el) {
						el.parent().find('.date').html(response.substring(0, response.length - 1));
					} else {
						$('.' + action + ' .date').html(response.substring(0, response.length - 1));
					}
				});
			};

			var buildAllStatic = function(){
				<?php
					global $BuildSteps;
					if(!empty($BuildSteps)){
						foreach($BuildSteps as $key => $BuildStep){
							if($BuildStep->buildType == "static"){
								echo '
									var data'.$key.' = {
										\'action\': \'build_' . $BuildStep->buildName .'\',
										\'post_ID\':null
									};
									console.log("Sending ajax request");
									// console.log(data);
									$.post(ajaxurl, data'.$key.', function(response) {
										console.log(response);
									});
								';
							}
						}
					}
				?>
			};
			var buildAllData = function(){
				<?php
					global $BuildSteps;
					if(!empty($BuildSteps)){
						foreach($BuildSteps as $key => $BuildStep){
							if($BuildStep->buildType == "data"){
								echo '
									var data'.$key.' = {
										\'action\': \'build_' . $BuildStep->buildName .'\',
										\'post_ID\':null
									};
									console.log("Sending ajax request");
									// console.log(data);
									$.post(ajaxurl, data'.$key.', function(response) {
										console.log(response);
									});
								';
							}
						}
					}
				?>
			};
		});
		</script> 
		<?php
	}
	public function create_admin_page(){
		$this->options = get_option( 'chips_data_option_name' );
		if (!isset($this->options['data_root']) || $this->options['data_root'] == '') {
			$this->options['data_root'] = '/data';
			update_option('chips_data_option_name',  $this->options);
		}

		if (!isset($this->options['data_html']) || $this->options['data_html'] == '') {
			$this->options['data_html'] = '/posts';
			update_option('chips_data_option_name',  $this->options);
		}


		?>
		<div class="wrap">
			<h2>CHIPS Data Generator Settings</h2>           
			<form method="post" action="options.php">
			<?php
				// This prints out all hidden setting fields
				settings_fields( 'chips_data_option_group' );   
				do_settings_sections( 'chips-data-admin' );
				do_settings_sections( 'chips-data-admin-data-summary' );
				submit_button(); 
			?>
			</form>
		</div>

		<div class="current-queue" style="position:fixed;top:32px;right:0;background-color:white;padding:10px;border:1px solid #e5e5e5;">
			<div class="ajaxResponse"><span style="border:1px solid #e5e5e5;border-radius:50%;background-color:green;width:18px;height:18px;position:relative;display:inline-block;margin-right:5px;vertical-align:middle;"></span><strong style="display:inline-block;vertical-align:middle;">Current status:</strong> <span style="display:inline-block;vertical-align:middle;">Ready to build.</span></div>
			<?php // FLAG to add the active queue
				// global $process_all;
				// if (($process_all->get_batch_pub() && $process_all->get_batch_pub()->option_value) || ($process_all->get_batch_pub() && $process_all->get_batch_pub()->option_value)) {
				// 	$dType = null;
				// 	// error_log($process_all->get_batch_pub()->option_name);
				// 	if($process_all->get_batch_pub()) {
				// 		print_r($process_all->get_batch_pub());
				// 	} else {
				// 		echo "No queue here";
				// 	}
				// }
			?>
		</div>
		<?php
	}

	/**
	 * Register and add settings
	 */
	public function page_init()
	{        
		register_setting(
			'chips_data_option_group', // Option group
			'chips_data_option_name', // Option name
			array( $this, 'sanitize' ) // Sanitize
		);


		 add_settings_section(
			'data_summary', // ID
			'Data Summary', // Title
			array( $this, 'print_section_info' ), // Callback
			'chips-data-admin-data-summary' // Page
		);  
		 add_settings_field(
			'data_path', // ID
			'Data path', // Title 
			array( $this, 'data_path_callback' ), // Callback
			'chips-data-admin-data-summary', // Page
			'data_summary' // Section           
		);
		 add_settings_field(
			'data_build_id', // ID
			'Build Post By ID', // Title 
			array( $this, 'data_id_callback' ), // Callback
			'chips-data-admin-data-summary', // Page
			'data_summary' // Section           
		);
		add_settings_field(
			'data_summary_cb_3', // ID
			'Javascript Files', // Title 
			array( $this, 'data_callback' ), // Callback
			'chips-data-admin-data-summary', // Page
			'data_summary' // Section           
		);
		global $BuildSteps;
		if(!empty($BuildSteps)){
			foreach($BuildSteps as $key => $BuildStep){
				if($BuildStep->buildType == "static"){
					add_settings_field(
						'data_summary_cb_' . $BuildStep->buildName, // ID
						'Flat Files (' . $BuildStep->buildName . ')', // Title 
						array( $this, 'data_static_callback' ), // Callback
						'chips-data-admin-data-summary', // Page
						'data_summary', // Section
						array('bType' => $BuildStep->buildName)
					);
				}
			}
		}
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 */
	public function sanitize( $input )
	{
		$new_input = array();
		if( isset( $input['data_root'] ) )
			$new_input['data_root'] = sanitize_text_field( $input['data_root'] );

		if( isset( $input['data_html'] ) )
			$new_input['data_html'] = sanitize_text_field( $input['data_html'] );

		if( isset( $input['title'] ) )
			$new_input['title'] = sanitize_text_field( $input['title'] );

		return $new_input;
	}

	public function print_section_info(){
		// print 'Enter your settings below:';
	}

	/** 
	 * Get the settings option array and print one of its values
	 */
	public function data_id_callback(){
		print '<input id="id-input" type="text">';
		print '<button id="build-by-id">Build</button>';
	}
	
	public function data_static_callback($args){
		global $post;
		global $BuildSteps;
		if(is_array($args) && !empty($args) && isset($args['bType'])){
			$buildType = $args['bType'];
			error_log($buildType);
			$filesStr = '';
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
			if (!is_dir($upload_dir['basedir'] . $data_root)) { mkdir($upload_dir['basedir'] . $data_root);}
			if (!is_dir($upload_dir['basedir'] . $data_root . '/' . $buildType)) { mkdir($upload_dir['basedir'] . $data_root . '/' . $buildType);}
			if (is_dir($upload_dir['basedir'] . $data_root) && is_dir($upload_dir['basedir'] . $data_root . '/' . $buildType)) { 
				$args = array(
					'post_type'=> $buildType,
					'post_status' => 'publish',
					'posts_per_page' => -1
				);

				query_posts( $args );
				while ( have_posts() ) : the_post();
					$draft = '';
					if ($post->post_status === 'draft') {
						$draft = 'Draft — ';
					}
					// error_log($upload_dir['basedir']. $data_root . '/' . $buildType . '/'.$post->post_name.'.html');
					if (file_exists($upload_dir['basedir']. $data_root . '/' . $buildType . '/'.$post->post_name.'.html')) {
						$filesStr .= '<p style="margin-bottom:12px;"><span>'. $draft . $post->post_title. '</span><a class="regen-single" href="#" data-action="build_'.$buildType.'" data-slug='.$post->post_name.' data-id='.get_the_id().' style="color:green;margin-left:24px;text-decoration:none;float:right;display:block;">&#10227;</a><span class="date" style="float:right;">'. date ("F d Y g:i:s A", filemtime($upload_dir['basedir']. $data_root . '/' . $buildType . '/' . $post->post_name.'.html')).'</span></p>';
					} else {
						$filesStr .= '<p style="margin-bottom:12px;"><span>'. $draft . $post->post_title. '</span><a class="regen-single" href="#" data-action="build_'.$buildType.'" data-slug='.$post->post_name.' data-id='.get_the_id().' style="color:green;margin-left:24px;text-decoration:none;float:right;display:block;">&#10227;</a><span class="date" style="float:right;color:red;">File Missing!</span></p>';
					}
				endwhile;
				wp_reset_query();
				printf(
					'<div class="regen-section custom-static" data-action="build_post">
					<div class="header" style="border:1px solid #e5e5e5;background-color:#fff;border-bottom: 1px solid #eee;"><h2 style="font-size: 14px;padding: 8px 12px;margin: 0;line-height: 1.4;"><span>Article Title</span><a class="regen-all-custom-static" href="#" style="float:right;">All</a><span style="float:right;margin-right: 22px;">Date Modified</span></h2></div>
					<div class="data-summary static" style="position:relative;height:300px;overflow:scroll;background-color:#fff;border: 1px solid #e5e5e5;border-top:none;box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					
						<div class="inner" style="padding:20px 12px;">'.$filesStr.

					'</div></div></div>'
				);
			} else {
				print '<p>Save a post!</p>';
			}
		}
	}

	public function data_callback(){
		global $post;
		$filesStr = '';
		$upload_dir = wp_upload_dir();
		if (get_option( 'chips_data_option_name' )) {
			$opts = get_option( 'chips_data_option_name' );
			if (isset($opts['data_root']) === TRUE) {
				$data_root = $opts['data_root'];    
			}
			if (isset($opts['data_root']) === TRUE) {
				$data_html = $opts['data_html'];
			}
		}

		if (!is_dir($upload_dir['basedir']. $data_root)) { mkdir($upload_dir['basedir']. $data_root);}
		if (is_dir($upload_dir['basedir']. $data_root)) { 
			global $BuildSteps;
			if(!empty($BuildSteps)){
				foreach($BuildSteps as $BuildStep){
					if($BuildStep->buildType == "data"){
						if (file_exists($upload_dir['basedir']. $data_root . '/'.$BuildStep->buildName.'.js')) {
							$filesStr .= '<p style="margin-bottom:12px;"><span>'.$BuildStep->buildName.'</span><a class="regen-single" href="#" data-action="build_'.$BuildStep->buildName.'" data-slug='.$BuildStep->buildName.' style="color:green;margin-left:24px;text-decoration:none;float:right;display:block;">&#10227;</a><span class="date" style="float:right;">'. date ("F d Y g:i:s A", filemtime($upload_dir['basedir']. $data_root . '/'.$BuildStep->buildName.'.js')).'</span></p>';
						} else {
							$filesStr .= '<p style="margin-bottom:12px;"><span>'.$BuildStep->buildName.'</span><a class="regen-single" href="#" data-action="build_'.$BuildStep->buildName.'" data-slug='.$BuildStep->buildName.' style="color:green;margin-left:24px;text-decoration:none;float:right;display:block;">&#10227;</a><span class="date" style="float:right;color:red;">File Missing!</span></p>';
						}
					}
				}
			}

			printf(
				'<div class="regen-section data" data-action="build_js">
				<div class="header" style="border:1px solid #e5e5e5;background-color:#fff;border-bottom: 1px solid #eee;"><h2 style="font-size: 14px;padding: 8px 12px;margin: 0;line-height: 1.4;"><span>Filename</span><a class="regen-all" href="#" style="float:right;">All</a><span style="float:right;margin-right: 22px;">Date Modified</span></h2></div>
				<div class="data-summary static" style="position:relative;height:300px;overflow:scroll;background-color:#fff;border: 1px solid #e5e5e5;border-top:none;box-shadow: 0 1px 1px rgba(0,0,0,.04);">
				
					<div class="inner" style="padding:20px 12px;">'.$filesStr.

				'</div></div></div>'
			);
		}
	}

	public function data_path_callback()
	{
		// error_log('here');
		// error_log(print_r( get_option( 'chips_data_option_name' ), true));

		print '<p><b>Basic directory structure:</b></p><br>';
		print '<p>All JS data files are saved in the data root directory <span><i>(e.g. \'/data/posts.js\')</i></span></p>';
		print '<p>Flat files save to /[data root directory]/[flat files directory] <span><i>(e.g. \'/data/posts/newpost.html\')</i></span></p><br>';


		printf(
			'<div><label for="data_root">Data root directory:</label><input type="text" id="data_root" placeholder="e.g. \'/data\'" name="chips_data_option_name[data_root]" value="%s" /><span>(defaults to \'/data\')</span></div>',
			isset( $this->options['data_root'] ) ? esc_attr( $this->options['data_root']) : ''
		);
		 printf(
			'<div><label for="data_html">Flat files directory:</label><input type="text" id="data_html" placeholder="e.g. \'/posts\'" name="chips_data_option_name[data_html]" value="%s" /><span>(defaults to \'/posts\')</span></div>',
			isset( $this->options['data_html'] ) ? esc_attr( $this->options['data_html']) : ''
		);
	}
}

if( is_admin() )
	$chips_data_settings_page = new CHIPSDataSettingsPage();


add_action( 'wp_ajax_chipsdbbg_action', 'chipsdbbg_action_callback' );
function chipsbackgroundchecker_action_javascript() { ?>
	<script type="text/javascript" >
	jQuery(document).ready(function($) {

		var data = {
			'action': 'chipsdbbg_action'
		};

		setInterval(function(){
			jQuery.post(ajaxurl, data, function(response) {
				console.log(response);
				$(".current-queue").html(response);
			});
		},3000);
	});
	</script> <?php
}


add_action( 'admin_enqueue_scripts', 'chipsbuildprocess_enqueue' );
function chipsbuildprocess_enqueue($hook) {
	if( 'settings_page_chips-data-admin' != $hook ) {
		return; // Move along if not in the CHIPS publishing plugin
	} else {
		add_action( 'admin_footer', 'chipsbackgroundchecker_action_javascript' );
	}
}

function chipsdbbg_action_callback() {
	global $wpdb;
	$queueAmt = $wpdb->get_var( "SELECT COUNT(*) FROM wp_options WHERE option_name LIKE '%wp_build_%'" );
	if($queueAmt > 0){
		$currStatus = "Building... {$queueAmt} items left.";
		$color = 'yellow';
	} else {
		$currStatus = "Ready to build.";
		$color = 'green';
	}

	echo '<div class="ajaxResponse"><span style="border:1px solid #e5e5e5; border-radius:50%;background-color:' . $color . ';width:18px;height:18px;position:relative;display:inline-block;margin-right:5px;vertical-align:middle;"></span><strong style="display:inline-block;vertical-align:middle;">Current status:</strong> <span style="display:inline-block;vertical-align:middle;">'.$currStatus.'</span></div>';
	die();
}