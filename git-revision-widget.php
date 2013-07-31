<?php
/**
 * @package WP Chaos Client
 * @version 1.0
 */
/*
Plugin Name: WordPress Git Revision Widget (for the administrative dashboard)
Plugin URI: https://github.com/kraenhansen/wp-git-version-display
Description: Adds a widget to the administrative dashboard, displaying the git ref of the repository containing the wordpress installation.
Author: Kræn Hansen <kraen@opensourceshift.com>
Version: 1.0
Author URI: http://kraenhansen.dk
*/
 
class GitRevisionWidget {
	public function __construct() {
		add_action('wp_dashboard_setup', array(&$this, 'add_dashboard_widget') );
	}
	
	public static function determine_git_directory() {
		$directory = realpath(__DIR__);
		$path_elements = explode(DIRECTORY_SEPARATOR, $directory);
		for($p = count($path_elements)-1; $p > 0; $p--) {
			$path = implode(DIRECTORY_SEPARATOR, array_slice($path_elements, 0, $p)) . DIRECTORY_SEPARATOR . '.git';
			if(is_dir($path) && is_readable($path)) {
				return $path;
			}
		}
		return null;
	}
	
	public static function install() {
		// Traverse directories from here towards the root, to set the .git directory.
		$git_directory = self::determine_git_directory();
		if($git_directory) {
			update_option(__CLASS__.'-git-path', $git_directory);
		} else {
			die("Couldn't determine the root of the git repository.");
		}
	}
	
	protected function get_git_revision() {
		$result = array();
		$git_directory = get_option(__CLASS__.'-git-path', null);
		if($git_directory) {
			$head_filename = $git_directory . DIRECTORY_SEPARATOR . 'HEAD';
			if(is_readable($head_filename)) {
				$head = trim(file_get_contents($head_filename));
				$exploded_head = explode('/', $head);
				// Did you see that variable name? ;)
				$result['branch'] = $exploded_head[count($exploded_head)-1];
				$ref_filename = $git_directory . DIRECTORY_SEPARATOR . str_replace('ref: ', '', $head);
				if(is_readable($ref_filename)) {
					$ref = trim(file_get_contents($ref_filename));
					$result['reference'] = $ref;
				} else {
					$result['reference'] = "unknown";
				}
			} else {
				$result['branch'] = 'unknown';
				$result['reference'] = "unknown";
			}
		}
		return $result;
	}
	
	public function display_revision_vision() {
		$reference = $this->get_git_revision();
		printf('Git repository at the <strong>%s</strong> branch (commit <strong>%s</strong>)', $reference['branch'], $reference['reference']);
	}
	
	public function add_dashboard_widget() {
		wp_add_dashboard_widget('git_revision_dispay_widget', 'Git Revision', array(&$this, 'display_revision_vision'));
	}
}
new GitRevisionWidget();
register_activation_hook( __FILE__, array( 'GitRevisionWidget', 'install' ) );