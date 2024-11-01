<?php
/*
Plugin Name: All Authors Page
Plugin URI: http://www.aktdevelopments.com.au/products/all-authors-plugin/
Description: NO LONGER SUPPORTED PLEASE DO NOT USE OR INSTALL! A effective, simple and easy way to show all your staff, content writers, experts, contributors etc. in one easy place for your customers. Link a name to a face as this plugin provides a gravatar image for registered users with an email linked to garvatar. This plugin adds a page called 'All Authors Page' to your site and automagically adds every one with the role 'Author' to the page. If the page is not displaying correctly try changing the page template under attributes to help your theme handle the page.
Version: 1.8.7
Author: AKT Developments
Author URI: http://www.aktdevelopments.com.au/
License: GPLv2 or later
*/

	
if (!class_exists('authorlistpage_Plugin'))
{
  class authorlistpage_Plugin
  {
    
	
    public $_name;
    public $page_title;
    public $page_name;
    public $page_id;
	
    public function __construct()
    {
		
      $this->_name      = "all_author_plug";
      $this->page_title = 'All Authors Page';
      $this->page_name  = $this->_name;
      $this->page_id    = '0';
	  $this->admin_option    = __FILE__;
	  $this->admin_action    =
		  trailingslashit(get_bloginfo('wpurl')) . 'wp-admin/'
		. ($this->wp27 ? 'options-general.php' : 'admin.php')
		. '?page=' . $this->admin_option;

      register_activation_hook(__FILE__, array($this, 'activate'));
      register_deactivation_hook(__FILE__, array($this, 'deactivate'));
      register_uninstall_hook(__FILE__, array($this, 'uninstall'));
     
      add_filter('parse_query', array($this, 'query_parser'));
      add_filter('the_posts', array($this, 'page_filter'));
	//add_filter('plugin_action_links', array($this, 'plugin_options_link'), 10, 2);
	add_action('admin_menu', array($this,'authorlistpage_create_menu'));
	add_action('user_contactmethods', array($this, 'change_contactmethod'));
	//add_action('personal_options_update', array($this, 'aap_save_user_setting'));
    }
    
    // Add a custom field to the form in "Profile"
function aap_user_setting($user) {
	$meta = get_userdata($user->ID);
	echo "<!--
	";
	foreach($user as $k => $v){
		echo $k . ' -> ' . $v . "
		";
	}
	echo "
	-->";
	if($meta->google_profile){
		$google = $meta->google_profile;
	}else{
		$google = '';
	}
	
	if($meta->linkedin_profile){
		$linkedin = $meta->linkedin_profile;
	}else{
		$linkedin = "";
	}
	
	if($meta->twitter_profile){
		$twitter = $meta->twitter_profile;
	}else{
		$twitter = "";
	}
?>

    <tr>
	<th><label for="google_profile">Google Profile URL</label></th>
	<td><input type="text" name="google_profile" id="google_profile" value="<?php echo $google; ?>" class="regular-text" /></td>
    </tr>
    <tr>
	<th><label for="linkedin_profile">LinkedIn Profile URL</label></th>
	<td><input type="text" name="linkedin_profile" id="linkedin_profile" value="<?php echo $linkedin; ?>" class="regular-text" /></td>
    </tr>
    <tr>
	<th><label for="twitter_profile">Twitter Profile URL</label></th>
	<td><input type="text" name="twitter_profile" id="twitter_profile" value="<?php echo $twitter; ?>" class="regular-text" /></td>
    </tr>
    <tr>
	<th><label for="facebook_profile">Facebook Profile URL</label></th>
	<td><input type="text" name="facebook_profile" id="facebook_profile" value="<?php echo $facebook; ?>" class="regular-text" /></td>
    </tr>
<?php
}

function change_contactmethod( $contactmethods ) {
//Add some fields
$contactmethods['google_profle'] = 'Google+ Profile URL';
$contactmethods['linkedin_profile'] = 'LinkedIn Profile URL';
$contactmethods['twitter_profile'] = 'Twitter Profile URL';
$contactmethods['facebook_profile'] = 'Facebook';

 
//make it go!
return $contactmethods;
 
}
 

 
// Handle data that's posted and sanitize before saving it
function aap_save_user_setting( $user_id ) {
    if( !empty($_POST['google_profile'])){
    	$googlePlus = $_POST['google_profile'];
    	update_usermeta( $user_id, 'google_profile', $googlePlus );
    }else if(!empty($_POST['linkedin_profile'])){
    	$linkedIn = $_POST['linkedin_profile'];
    	update_usermeta( $user_id, 'linkedin_profile', $linkedIn );
    }else if(!empty($_POST['twitter_profile'])){
    	$twitter = $_POST['twitter_profile'];
    	update_usermeta( $user_id, 'twitter_profile', $twitter );
    }
}
 

 



 
	
	public function plugin_options_link($links,$file){
	
		if (method_exists($this, 'addPluginSettingLinks')) {
			$links = $this->addPluginSettingLinks($links, $file);
		} else {
			$this_plugin = plugin_basename(__FILE__);
			if ($file == $this_plugin) {
				$settings_link = '<a href="' . $this->admin_action . '">' . __('Settings') . '</a>';
				array_unshift($links, $settings_link); // before other links
			}
		}
		return $links;
	}
	
    public function activate()
    {
      global $wpdb;      

      delete_option($this->_name.'_page_id');
      add_option($this->_name.'_page_id', $this->page_id, '', 'yes');
	  
	  delete_option($this->_name.'_page_title');
      add_option($this->_name.'_page_title', $this->page_title, '', 'yes');

      delete_option($this->_name.'_page_name');
      add_option($this->_name.'_page_name', $this->page_name, '', 'yes');

	  delete_option($this->_name.'_role_check');
      add_option($this->_name.'_role_check', 'author');
	  
	  delete_option($this->_name.'_show_email');
	  add_option($this->_name.'_show_email','true');
      

      $the_page = get_page_by_title($this->page_title);

      if (!$the_page)
      {

        $_p = array();
        $_p['post_title']     = $this->page_title;
        $_p['post_content']   = $this->listAllAuthors();
        $_p['post_status']    = 'publish';
        $_p['post_type']      = 'page';
        $_p['comment_status'] = 'closed';
        $_p['ping_status']    = 'closed';
        $_p['post_category'] = array(1); // 'Uncatrgorised'

        $this->page_id = wp_insert_post($_p);
      }
      else
      {
        // the plugin may have been previously active and the page may just be trashed...
        $this->page_id = $the_page->ID;

        //make sure the page is not trashed...
        $the_page->post_status = 'publish';
        $this->page_id = wp_update_post($the_page);
      }

      delete_option($this->_name.'_page_id');
      add_option($this->_name.'_page_id', $this->page_id);


	  
    }

    public function deactivate()
    {
      $this->deletePage();
      $this->deleteOptions();
    }

    public function listAllAuthors()
    {
    	$output = "";
    	$roleCheck = get_option($this->_name.'_role_check');
    	$output .= "<!-- " . $roleCheck . " -->";
    	$athr = strpos($roleCheck, 'author');
    	$adm = strpos($roleCheck, 'admin');
    	$ctrbr = strpos($roleCheck, 'contributor');
    	$edtr = strpos($roleCheck, 'editor');
    	if ($roleCheck){
    	if($athr !== false){
			$output .= "<!-- authors being printed -->";
				$output .= $this->printRole('author');
			}
			
			if($adm !== false){
				$output .= "<!-- admins being printed -->";
				$output .= $this->printRole('administrator');
			}
			
			if($ctrbr !== false ){
				$output .= "<!-- contributors being printed -->";
				$output .= $this->printRole('contributor');
			}
			
			if($edtr !== false ){
				$output .= "<!-- editors being printed -->";
				$output .= $this->printRole('editor');
			}
    	}else{
			$output .= "There is no roles selected to display on this page, please select some roles from the settings menu and then all the information for these people will be displayed here";
		}
    	
    	return $output;
		
    }
    
    public function printRole($role){
    	$output = "";	
		
		$wp_user_query = new WP_User_Query( array( 'role' => $role, 'fields' => 'all_with_meta' ) );

		$authors = $wp_user_query->get_results();

		if (!empty($authors))
		{
			$output .= '';

			foreach ($authors as $author)
			{

				$curauth = get_userdata($author->ID);
				$output .= '<h2><span property="v:name\">' .   $curauth->first_name . ' ' . $curauth->last_name; 
				$email = $curauth->user_email;
				$img = $this->get_gravatar($email);
				$imagesPath = plugins_url( 'images/', __FILE__ );
				$output .= $img;
				if(!empty($curauth->google_profile)){
					$gPlus = '<a rel="me" href="' .   $curauth->google_profile  . '"><img src="' . $imagesPath . 'googleplus.png" width="68px" height="68px" alt="Google+ Profile"/></a>';
				}else{
					$gPlus = '<!-- no google+ account -->';
				}
				
				if(!empty($curauth->linkedin_profile)){
					$li = '<a href="' . $curauth->linkedin_profile . '" ><img src="' . $imagesPath . 'linkedin.png" width="68px" height="68px" alt="LinkedIn Profile"/></a>';
				}else{
					$li = '<!-- no linkedIn account -->';
				}
				
				if(!empty($curauth->twitter_profile)){
					$twit = '<a href="' . $curauth->twitter_profile . '" ><img src="' . $imagesPath . 'twitter.png" width="68px" height="68px" alt="Twitter Profile"/></a>';
				}else{
					$twit = '<!-- no twitter account -->';
				}
				
				if(!empty($curauth->facebook_profile)){
					$fcbk = '<a href="' . $curauth->facebook_profile . '" ><img src="' . $imagesPath . 'facebook.png" width="68px" height="68px" alt="Facebook Profile"/></a>';
				}else{
					$fcbk = '<!-- no facebook account -->';
				}
				$showMail = get_option($this->_name.'_show_email');
				if($showMail == "true"){
				
				
				$output .= '</span></h2>
							<dl>
							<dt><b>Social Networks</b></dt>
								<dd>' . $gPlus . $li . $twit . $fcbk .'</dd>
								<dt><b>Website</b></dt>
								<dd><a href="' .   $curauth->user_url  . '\" rel="v:url\">' .   $curauth->user_url  . '</a></dd>
							<dt><b>Email</b></dt>
							<dd><a href="mailto:' .   $curauth->user_email  . '\">' .   $curauth->user_email  . '</a></dd>
							<dt><b>Profile</b></dt>
							<dd>' .   $curauth->user_description  . '</dd>
							</dl>
							<br/>';
				}else{
							$output .= '</span></h2>
							<dl>
							<dt><b>Social Networks</b></dt>
								<dd>' . $gPlus . $li . $twit . $fcbk .'</dd>
								<dt><b>Website</b></dt>
								<dd><a href="' .   $curauth->user_url  . '\" rel="v:url\">' .   $curauth->user_url  . '</a></dd>
							<dt><b>Profile</b></dt>
							<dd>' .   $curauth->user_description  . '</dd>
							</dl>
							<br/>';
				}
				/*			
				if ( have_posts() ) : while ( have_posts() ) : the_post()  
					$output .= '<li>
									<a href="' .  the_permalink()  . '\" rel="bookmark\" title="Permanent Link: ' .  the_title()  . '\">
									' .  the_title()  . '</a>,
									' .  the_time('d M Y')  . ' in ' .  the_category('&') . '
								</li>';
				endwhile else: 
					$output .= '<p>' .  _e('No posts by this author.')  . '</p>';
				endif
				*/
				
			}
		}
		
		return $output;
    }

    public function uninstall()
    {
      $this->deletePage(true);
      $this->deleteOptions();
    }

    public function query_parser($q)
    {
      if(isset($q->query_vars['page_id']) AND (intval($q->query_vars['page_id']) == $this->page_id ))
      {
        $q->set($this->_name.'_page_is_called', true);
      }
      elseif(isset($q->query_vars['pagename']) AND (($q->query_vars['pagename'] == $this->page_name) OR ($_pos_found = strpos($q->query_vars['pagename'],$this->page_name.'/') === 0)))
      {
        $q->set($this->_name.'_page_is_called', true);
      }
      else
      {
        $q->set($this->_name.'_page_is_called', false);
      }
    }

    function page_filter($posts)
    {
      global $wp_query;

	  foreach ($posts as $p){
		  if($p->post_title == 'All Authors Page')
		  {
			$p->post_content = $this->listAllAuthors();
		  }
      }
	  return $posts;
	  
    }

	
	/**
	 * Get either a Gravatar URL or complete image tag for a specified email address.
	 *
	 * @param string $email The email address
	 * @param string $s Size in pixels, defaults to 80px [ 1 - 512 ]
	 * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
	 * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
	 * @param boole $img True to return a complete IMG tag False for just the URL
	 * @param array $atts Optional, additional key/value attributes to include in the IMG tag
	 * @return String containing either just a URL or a complete image tag
	 * @source http://gravatar.com/site/implement/images/php/
	 */
	function get_gravatar( $email, $s = 80, $d = 'mm', $r = 'pg', $img = true, $atts = array() ) {
		$url = 'http://www.gravatar.com/avatar/';
		$url .= md5( strtolower( trim( $email ) ) );
		$url .= "?s=$s&d=$d&r=$r";
		if ( $img ) {
			$url = ' <img style="vertical-align: -40%;" src="' . $url . '"';
			foreach ( $atts as $key => $val )
				$url .= ' ' . $key . '="' . $val . '"';
			$url .= ' />';
		}
		return $url;
	}
	
    private function deletePage($hard = false)
    {
      global $wpdb;

      $id = get_option($this->_name.'_page_id');
      if($id && $hard == true)
        wp_delete_post($id, true);
      elseif($id && $hard == false)
        wp_delete_post($id);
    }

    private function deleteOptions()
    {
      delete_option($this->_name.'_page_title');
      delete_option($this->_name.'_page_name');
      delete_option($this->_name.'_page_id');
	  delete_option($this->_name.'_role_check');
	  delete_option($this->_name.'_show_email');
    }

	
	 
	function authorlistpage_create_menu() {
	 
			 //create new top-level menu
			 add_options_page('All Author Settings', 'All Author Settings', 'manage_options', __FILE__, array($this,'authorlistpage_settings_page'));
	 
			 //call register settings function
			 //add_action( 'admin_init', 'register_mysettings' );
	}
	 
	 
	function register_mysettings() {
			 //register our settings
			 register_setting( 'authorlistpage-settings-group', 'aap-roles' );

	}
	 
	function authorlistpage_settings_page() {
	$roleCheck = $this->_name . '_role_check';
	
	$role_val = get_option($this->_name.'_role_check');
	echo "<!-- ". $roleCheck . " = ".$role_val." -->";
	$email_val = get_option($this->_name.'_show_email');
	echo "<!-- Showing Emails = ".$email_val." -->";
	
	if(isset($_POST)){
	
		if($_POST['all_author_plug_role_check'] != "" || $_POST['all_author_plug_role_check'] != null){
			if(update_option('all_author_plug_role_check',$_POST['all_author_plug_role_check'])){
				echo "<div style=\"color: green; text-align: center;\" ><h1><b>Roles to show on 'All Author Page' Updated!</b><h1></div>
				";
			}else{
				echo "<div style=\"color: red; text-align: center;\" ><h1>There was a problem updating the database</h1></div>
				";
			}
		}
		if($_POST['email'] == "true" || $_POST['email'] == "false"){
			if(update_option($this->_name.'_show_email',$_POST['email'])){
				echo "<div style=\"color: green; text-align: center;\" ><h1><b>Email show settings on 'All Author Page' Updated!</b><h1></div>
				";
			}else{
				echo "<div style=\"color: red; text-align: center;\" ><h1>There was a problem updating the database</h1></div>
				";
			}
		}
	}
	$email_val = get_option($this->_name.'_show_email');
	$role_val = get_option($this->_name.'_role_check');
	?>
	<div align="center">
	<h1>All Authors Options</h1>
	<div style="text-align: center; font-size: 16pt; padding-top: 40px; padding-bottom: 40px;" >Current roles being displayed on Authors Page: <b><?php echo ''. $role_val; ?></b></div>
	<div style="text-align: center; font-size: 16pt; padding-top: 40px; padding-bottom: 40px;" >Author email addresses being shown on Authors Page: <b><?php echo ''. $email_val; ?></b></div><br/>
	<form method="POST" action="<?php echo $this->admin_action; ?>">
		<table style="font-size: 14pt;">
			<tr valign="top">
			<th scope="row">Roles to Include in All Authors Page (values can be: author, editor, admin, contributor) :</th>
			<td>
			  <select name="<?php echo $roleCheck; ?>" id="<?php echo $roleCheck; ?>">
			    <option value="author">authors</option>
			    <option value="admin">admins</option>
			    <option value="author, admin">authors &amp; admins</option>
			    <option value="author, contributor">authors &amp; contributors</option>
			    <option value="author, admin, contributor, editor">authors, admins, contributors &amp; editors</option>
			    <option value="author, contributor, editor">authors, contributors &amp; editors</option>
			  </select>
			  </td>
			</tr>
			<tr valign="bottom">
			<th scope="row">Show Author Email Address</th>
			<td>
				<input type="radio" name="email" value="true" checked="true"/> Yes, Show Email<br />
				<input type="radio" name="email" value="false" /> No, Don't Show
			</td>
			</tr>
		</table>
		
		<p class="submit">
		<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		</p>
	 
	</form>
	</div>
	<?php } 
  }
}
$authorlistpage = new authorlistpage_Plugin();
?>