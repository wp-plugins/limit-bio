<?php
/*
Plugin Name: Limit Bio
Plugin URI: http://www.rivercitygraphix.com
Description: A plugin that allows you to limit the biographical information field on the edit profile page.
Author: Kevin Olson
Version: 1.0
Author URI: http://www.rivercitygraphix.com
*/
function remove_plain_bio($buffer) {
	$titles = array('#<h3>About Yourself</h3>#','#<h3>About the user</h3>#');
	$buffer=preg_replace($titles,'<h3>Password</h3>',$buffer,1);
	$biotable='#<h3>Password</h3>.+?<table.+?/tr>#s';
	$buffer=preg_replace($biotable,'<h3>Password</h3> <table class="form-table">',$buffer,1);
	return $buffer;
}

function profile_admin_buffer_start() { ob_start("remove_plain_bio"); }

function profile_admin_buffer_end() { ob_end_flush(); }

add_action('admin_head', 'profile_admin_buffer_start');
add_action('admin_footer', 'profile_admin_buffer_end');
define( 'AS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
require_once( AS_PLUGIN_PATH . '/options.php' );

class LimitBio {
	var $add_my_script;
	var $theme_options = array(
			array('type' => 'open'),
			array(
					'id' => 'character_count',
					'default' => '175',
					'label' => 'Bio Character Limit',
					'type' => 'text'
			),
			array('type' => 'close')
	);
	var $options;
	function __construct(){
		add_action( 'show_user_profile', array(&$this,'add_custom_user_profile_fields' ));
		add_action( 'edit_user_profile', array(&$this,'add_custom_user_profile_fields' ));
		add_action( 'personal_options_update', array(&$this,'save_custom_user_profile_fields' ),100,1);
		add_action( 'edit_user_profile_update', array(&$this,'save_custom_user_profile_fields' ),100,1);
		add_action( 'admin_print_scripts-profile.php', array(&$this,'profile_scripts'));
		add_action('admin_init', array(&$this, 'admin_init'));
		$this->options = new Limit_Options('limit_options');
		add_action('admin_menu', array(&$this, 'theme_options_menu'));
		add_filter ('pre_user_description', array(&$this,'pre_user_description'), 1, 1);
	}
	function profile_scripts(){
		wp_register_script('char', plugins_url($path='/limit-bio') . '/js/charCount.js',array('jquery') );
		wp_print_scripts('char');
		wp_register_script('bio', plugins_url($path='/limit-bio') . '/bio.php?char=' .$this->options->character_count);
		wp_print_scripts('bio');
		wp_register_style('limit-style',plugins_url($path='/limit-bio') . '/css/style.css');
		wp_print_styles('limit-style');
	}
	function admin_init(){
		foreach($this->theme_options as $value){
	    	if(!isset($this->options->{$value['id']})){
	    	$this->options->{$value['id']} = $value['default'];
	    	}
	    }
	    $this->options->save();
	}
	function add_custom_user_profile_fields( $user ) {
		?>
		<h3><?php _e('Biography', 'limit-bio'); ?></h3>
	    <table class="form-table">
		   	<tr>
			    <th>
			    <label for="description"><?php _e('User Biography', 'limit-bio'); ?></label>
			    </th>
			    <td>
			    	<div id="bio_container">
	        		<textarea id="description" class="limit_bio_description" name="description"><?php echo esc_attr( get_the_author_meta( 'description', $user->ID ) ); ?></textarea>
			    	</div>
			    </td>
		    </tr>	    
		</table>
    <?php }
    function save_custom_user_profile_fields( $user_id ) {
    if ( !current_user_can( 'edit_user', $user_id ) )
    return FALSE;
    }
    function pre_user_description($description){
    	$character_count = $this->options->character_count;
    	$description = substr($description, 0, $character_count);
    	return $description;
    }
    function theme_options_menu() {
    	if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'save'){
    		foreach($this->theme_options as $value){
    			if(isset($_REQUEST[$value['id']])){
    				$this->options->{$value['id']} = stripslashes($_REQUEST[$value['id']]);
    			}
    		}
    		$this->options->save();
    		if(stristr($_SERVER['REQUEST_URI'], '&saved=true')){
    			$location = $_SERVER['REQUEST_URI'];
    		}else{
    			$location = $_SERVER['REQUEST_URI'] . '&saved=true';
    		}
    		header("Location: $location");
    	}
    	add_options_page('Limit Bio Options', 'Limit Bio', 'manage_options', __FILE__, array(&$this, 'limit_options'));
    }
    function limit_options() {
    	if ( !current_user_can( 'manage_options' ) )  {
    		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    	}
    	?>
    				<div class="wrap">
    				<?php screen_icon(); ?>
    				<h2 class="alignleft"><?php _e('Limit Bio Settings'); ?></h2>
    				<br clear="all" />
    				<?php if(isset($_REQUEST['saved']) && $_REQUEST['saved']) {?>
    				<div id="message" class="updated fade"><p><strong><?php _e('Settings Saved!') ?></strong></p></div>
    				<?php } ?>
    				<form method="post" id="my_form" enctype="multipart/form-data">
    					<div id="poststuff" class="metabox-holder">
    						<div class="stuffbox">
    							<h3><label><?php _e('Size Settings') ?></label></h3>
    							<div class="inside">
    								<table class="form-table" style="width:auto;">
    									<?php 
    										foreach($this->theme_options as $value){
    											if(!isset($value['id'])){
    												continue;
    											}
    											switch( $value['id']){
    												case 'character_count':
    								    ?>		
    								<tr>
    									<th scope="row">
    										<strong><?php echo $value['label']; ?></strong>
    									</th>
    									<td>
    										<input type="text" name="<?php echo $value['id']; ?>" id="<?php echo $value['id']; ?>" value="<?php echo $this->options->{$value['id']}; ?>"  />
    									</td>
    								</tr>		
    												<?php
    												break;										
    											}
    										}
    									?>
    								</table>
    							</div>
    						</div>
    					</div>
    				<input type="submit" name ="save" class="button-primary" value="<?php _e('Save Changes'); ?>" />
    				<input type="hidden" name="action" value="save" />	
    			</form>
    		</div>
    		<?php
    }
}

$limit_bio = new LimitBio();
?>