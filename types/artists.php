<?php
class buildArtistJS extends WP_Background_Process {
	protected $action = 'build_artist_js';
	protected $data = null;

	protected function task($item) {
		$args = array(
			'post_type'=> 'artists',
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
			$singlePostData["artist"] = get_the_title($singlePost);

			$singlePostData["website"] = get_field('website', $singlePost);
			$singlePostData["website_text"] = get_field('website_text', $singlePost);
			
			$singlePostData["links"] = "";
			$linkChecks = get_field('links', $singlePost);
			if(is_array($linkChecks) && !empty($linkChecks)){
				if(in_array('facebook', $linkChecks)){
					$singlePostData["links"] .= '<li><a href="' . the_field('facebook_link') . '" class="social-link facebook-link" title="facebook" target="_blank"></a></li>'; 
				}
				if(in_array('instagram', $linkChecks)){
					$singlePostData["links"] .= '<li><a href="' . the_field('instagram_link') . '" class="social-link twitter-link" title="twitter" target="_blank"></a></li>'; 
				}
				if(in_array('twitter', $linkChecks)){
					$singlePostData["links"] .= '<li><a href="' . the_field('twitter_link') . '" class="social-link instagram-link" title="instagram" target="_blank"></a></li>'; 
				}
				if(in_array('soundcloud', $linkChecks)){
					$singlePostData["links"] .= '<li><a href="' . the_field('soundcloud_link') . '" class="social-link soundcloud-link" title="soundcloud" target="_blank"></a></li>'; 
				}
			}

			$status = get_post_status($singlePost);
			$ribbonPub = get_field('ribbon_publishing',$singlePost);
			if($status === 'publish' && $ribbonPub === true){
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

		$upload_dir = wp_upload_dir();
		if (!is_dir($upload_dir['basedir'].'/data')) { mkdir($upload_dir['basedir']. $data_root);}
		$dataFileName = $upload_dir['basedir']. $data_root . '/artists.js';
		file_put_contents($dataFileName, json_encode((object) array('artistData' => $this->getData())));
		parent::complete();
	}

}