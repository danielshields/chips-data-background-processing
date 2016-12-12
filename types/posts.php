<?php
class buildPostJS extends WP_Background_Process {
	protected $action = 'build_post_js';
	protected $data = null;
	
	protected function task( $item ) {
		$args = array(
			'post_type'=> 'post',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'orderby' => 'menu_order'
		);
		query_posts( $args );
		$fullData = array();
		while ( have_posts() ) : the_post();
			$singlePostData = array();
			$singlePost = get_the_id();

			$singlePostData["id"] = $singlePost;
			$singlePostData["title"] = get_the_title($singlePost);
			$singlePostData["permalink"] = get_the_permalink($singlePost);
			$singlePostData["pdate"] = get_the_date('Y-m-d', $singlePost);
			$singlePostData["content"] = get_the_content( $singlePost );
			$singlePostData["featuredImg"]  = get_the_post_thumbnail($singlePost, 'large'); 

			$status = get_post_status($singlePost);
			if($status === 'publish'){
				$fullData[] = $singlePostData;
			}
		endwhile;
		wp_reset_query();
		$this->data = $fullData;
		return false;
	}

	public function getData(){return $this->data;}

	protected function complete() {
		if (get_option( 'chips_data_option_name' )) {
			$opts = get_option( 'chips_data_option_name' );
			if (isset($opts['data_root']) === TRUE) {
				$data_root = $opts['data_root'];	
			}
			if (isset($opts['data_root']) === TRUE) {
				$data_html = $opts['data_html'];
			}
		}
		// error_log('build_post_js process complete');

		$upload_dir = wp_upload_dir();
		if (!is_dir($upload_dir['basedir'].'/data')) { mkdir($upload_dir['basedir']. $data_root);}
		$dataFileName = $upload_dir['basedir']. $data_root . '/posts.js';
		file_put_contents($dataFileName,json_encode((object) array('postData' => $this->getData())));
		parent::complete();
	}

}