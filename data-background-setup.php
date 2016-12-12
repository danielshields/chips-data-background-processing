<?php
global $BuildSteps;
$BuildSteps = array();
/*				PARAMETERS:
					filename, - must be the same as filename in types directory
						static/data, static = html file, data = js file
							local class data object, must be unique
								remote class name - defined in php file referenced with filename	
									list of post types so that when a single post of that type is saved, run this action*/
$BuildSteps[] = new chipsBGProcess('post','static','classPD','buildPostData', array('post'));
$BuildSteps[] = new chipsBGProcess('posts','data','classJSData','buildPostJS', array('post'));
$BuildSteps[] = new chipsBGProcess('artists','data','classArtistJSData','buildArtistJS', array('artists'));
$BuildSteps[] = new chipsBGProcess('placements','data','classPlacementJSData','buildPlacementJS', array('placements', 'artists'));
$BuildSteps[] = new chipsBGProcess('albums','data','classAlbumJSData','buildAlbumJS', array('albums', 'artists'));

$BuildSteps[] = new chipsBGProcess('static_page','static_page','classPageStatic','buildPageStatic', array('page')); 

class chipsBGProcess {
	public $buildName = null; // Used for finding the class
	public $buildType = "data"; // static, data
	public $definedVar = null;
	public $definedCB = null;
	public $savePostTypes = array();
	public $allowAjaxBuild = true; // Generates an ajax callback based on $buildName
	public $process_post;
	public $parThis;

	public function __construct($oName = null,$oType = null,$oVar=null, $oFunc=null, $oSave = null, $oCB = null) {
		if($oName !== null){ $this->buildName = $oName; } else { return; }
		if($oType !== null){ $this->buildType = $oType; }
		if($oFunc !== null){ $this->definedCB = $oFunc; }
		if($oVar !== null){ $this->definedVar = $oVar; }
		if($oSave !== null){ $this->savePostTypes = $oSave; }
	}

	public function splitBuildProcess($postData = null,$parentClass){
		global $BuildSteps;
		if(is_array($postData) && !empty($postData)){
			$this->parThis = $parentClass;
			// error_log(print_r($postData,true));

			$act = $_POST['action'];
			if($act === "build_posts"){
				self::local_process_js();
			} else if($act === "build_artists"){
				self::local_process_artists();
			} else if($act === "build_placements"){
				self::local_process_placements();
			} else if($act === "build_albums"){
				self::local_process_albums();
			} else if($act === "editpost"){
				if(!empty($BuildSteps)){
					foreach($BuildSteps as $key => $BuildStep){
						// error_log(print_r($BuildStep, true));
						if (is_array($BuildStep->savePostTypes)) {
							if (in_array($postData['post_type'], $BuildStep->savePostTypes)) {
								$_POST['action'] = "build_" . $BuildStep->buildName;
								self::splitBuildProcess($_POST, $parentClass);
							}
							// error_log(print_r($BuildStep->savePostTypes, true));
						}
					}
				}


				
			} else {
				if (isset($postData['post_ID'])) {
					if(get_post_type($postData['post_ID']) === "artists"){
						$this->process_artist($postData['post_ID']);
					} else {
						self::local_process_posts($postData['post_ID']);
					}
					self::local_process_js();
				}
			}
		}
	}

	public function local_process_posts($pID = null) {
		if($pID !== null){
			$args = array(
				'post_status' => 'publish',
				'posts_per_page' => -1,
				'post_type' => 'post',
				'p' => $pID
			);
		} else {
			$args = array(
				'post_status' => 'publish',
				'post_type' => 'post',
				'posts_per_page' => -1
			);
		}
		query_posts( $args );

		while ( have_posts() ) : the_post();
			$singlePost = get_the_id();
			$this->parThis->classPD->push_to_queue( $singlePost );
		endwhile;
		$this->parThis->classPD->save()->dispatch();
		wp_reset_query();
	}

	public function build_page($id){
		$this->parThis->classPageStatic->push_to_queue($id);
		$this->parThis->classPageStatic->save()->dispatch();
	}
	
	public function local_process_js(){
		$this->parThis->classJSData->push_to_queue(true);
		$this->parThis->classJSData->save()->dispatch();
	}

	public function local_process_artists(){
		$this->parThis->classArtistJSData->push_to_queue(true);
		$this->parThis->classArtistJSData->save()->dispatch();
	}

	public function local_process_albums(){
		$this->parThis->classAlbumJSData->push_to_queue(true);
		$this->parThis->classAlbumJSData->save()->dispatch();
	}

	public function local_process_placements(){
		$this->parThis->classPlacementJSData->push_to_queue(true);
		$this->parThis->classPlacementJSData->save()->dispatch();
	}
}
