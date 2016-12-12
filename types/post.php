<?php
//Loop through posts, saving out each post
class buildPostData extends WP_Background_Process {
	protected $action = 'build_post';
	
	protected function task( $item ) {
		if (get_option( 'chips_data_option_name' )) {
			$opts = get_option( 'chips_data_option_name' );
			if (isset($opts['data_root']) === TRUE) {
				$data_root = $opts['data_root'];	
			}
			if (isset($opts['data_root']) === TRUE) {
				$data_html = $opts['data_html'];
			}
		}

		$finalMarkup = '';
		$singlePost = $item;

		$post = get_post($singlePost);

		$slug = $post->post_name;
		$title = $post->post_title;
		$content = $post->post_content;

		$featuredImg = get_the_post_thumbnail($singlePost, 'large');

		error_log("Featured image");
		error_log(print_r($featuredImg, true));
		error_log("End featured");

		$date = get_the_date('M d, Y', $singlePost);

		$finalMarkup .= "<h1>" . $title . "</h1>";
		$finalMarkup .= "<p class=\"post-date\">" . $date . "</p>";
		$finalMarkup .= "<p>" . $content . "</p>";
		$finalMarkup .= $featuredImg;

		$upload_dir = wp_upload_dir();
		if (!is_dir($upload_dir['basedir']. $data_root . $data_html)) { mkdir($upload_dir['basedir']. $data_root . $data_html);}
		if (!is_dir($upload_dir['basedir']. $data_root . $data_html)) { mkdir($upload_dir['basedir'] . $data_root . $data_html);}
		$dataFileName = $upload_dir['basedir']. $data_root . '/post/' . $slug .'.html';
	
		file_put_contents($dataFileName, $finalMarkup);
		return false;
	}

	protected function complete() {
		// error_log('build_post process complete');
		parent::complete();
	}

}