<?
/*
Plugin Name: Emo Vote
Plugin URI: http://wordpress.org/extend/plugins/emo-vote/
Description: Encourage your users be letting them express their feelings by "emoting" rather then voting.
Version: 1.0
Author: Anton Lindqvist
Author URI: http://qvister.se
Contributors: Mindpark

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
	
	define('EMO_FOLDER',basename(dirname(__FILE__)));
	define('EMO_OPTIONS','emo_options');
	
	function emo_install() {
		global $wpdb;
		$table = $wpdb->prefix . 'emo';
		
		if($wpdb->get_var('show tables like \''.$table.'\'') != $table) {
			$wpdb->query('create table '.$table.'(
				id int(10) not null auto_increment,
				post_ID int(10) not null,
				vote_0 int(10) not null default \'0\',
				vote_1 int(10) not null default \'0\',
				vote_2 int(10) not null default \'0\',
				vote_3 int(10) not null default \'0\',
				vote_4 int(10) not null default \'0\',
				vote_total int(10) not null default \'0\',
				primary key (id)
			);');
		}
		
		$url = str_replace('http://','',get_bloginfo('wpurl'));
		$path_real = '/';
		$paths = split('/',$url);
		
		/* Need to make sure if the 'Giving WordPress it's own directory' install method has been used */
		if(get_bloginfo('wpurl') == get_bloginfo('home')) {
			if(count($paths) > 0) {
				unset($paths[0]);
				foreach($paths as $path) {
					$path_real .= $path;
				}
			}
		}
		
		if(preg_match('/^http:\/\/www\./',get_bloginfo('wpurl'))) {
			$url = str_replace('http://www','',get_bloginfo('wpurl'));
		} else {
			$url = str_replace('http://','',get_bloginfo('wpurl'));
		}
		if(count($url = split('/',$url)) > 0) {
			$url = $url[0];
		}
		
		$url = split('/',$url);
		$url = $url[0];
		$options = array(
			'path' => $path_real,
			'url' => $url,
			'titles' => '0:mad#1:bored#2:curious#3:ok#4:happy',
			'list' => 1,
			'total' => 1,
			'installed' => 1
		);
		add_option(EMO_OPTIONS,$options);
	}
	function emo_options_page() {
		$options = get_option(EMO_OPTIONS);
		$post = $_POST[EMO_OPTIONS];
		
		if(!$options['installed'])
			emo_install();
		
		if(get_bloginfo('version') < 2.7)
			echo '<div class="error"><p><strong>Emo Vote requires WordPress 2.7, you are highly recommended to upgrade.</strong></p></div>';
		
		if(!is_array($options))
			$options = array();
		
		if(isset($post['submit'])) {
			$options['titles'] = $post['titles'];
			$options['list'] = $post['list'];
			$options['total'] = $post['total'];
			update_option(EMO_OPTIONS,$options);
			echo '<div class="updated"><p><strong>Options Updated</strong></p></div>';
		}
		
		/* Necessary to get the new values */
		$options = get_option(EMO_OPTIONS);
		$options['titles'] = split('#',$options['titles']);
?>
	<form id="emo_form" method="post" action="options-general.php?page=emo-vote.php">
		<div class="wrap">
			<h2>Emo Vote Options</h2>
			<p>Choose number of fields, give them a desired name and move them around until you're satisfied then hit Update Options.</p>
			<ul id="emo-list">
<?
			foreach($options['titles'] as $option) {
				$option_value = split(':',$option);
?>
			<li id="<?=EMO_TITLES?>-<?=$option_value[0]?>" class="emo_titles_field">
				<span class="emotion" style="background: transparent url(<?=WP_PLUGIN_URL . '/' . basename(dirname(__FILE__))?>/images/checkbox_<?=$option_value[0]?>.png);"></span></label><input type="text" size="16" name="<?=EMO_TITLES?>-<?=$option_value[0]?>" id="<?=EMO_TITLES?>-<?=$option_value[0]?>" value="<?=$option_value[1]?>" /><span class="emo-delete" id="<?=EMO_TITLES?>-<?=$option_value[0]?>"></span>
			</li>
<?
			}
?>
			</ul>
			<p class="plain">
				<label for="<?=EMO_OPTIONS?>[list]">Display votes in</label>
				<select name="<?=EMO_OPTIONS?>[list]">
					<option value="1" <?=($options['list'] == '1') ? 'selected' : ''?>>Percent</option>
					<option value="0" <?=($options['list'] == '1') ? '' : 'selected'?>>Numbers</option>
				</select>
			</p>
			<p class="plain">
				<label for="<?=EMO_OPTIONS?>[total]">Display total votes</label>
				<select name="<?=EMO_OPTIONS?>[total]">
					<option value="1" <?=($options['total'] == '1') ? 'selected' : ''?>>Yes</option>
					<option value="0" <?=($options['total'] == '1') ? '' : 'selected'?>>No</option>
				</select>
			</p>
			<p class="submit">
				<span id="emo_vote_url" style="display: none;"><?=WP_PLUGIN_URL . '/' . basename(dirname(__FILE__))?>/images/</span>
				<input type="hidden" name="<?=EMO_OPTIONS?>[titles]" id="emo_options_titles" value="" />
				<input type="submit" name="<?=EMO_OPTIONS?>[submit]" id="emo_options_submit" value="Update Options" />
				<input type="submit" name="emo_add" value="Add field" id="emo_add" />
				<span class="emo_vote_error"></span>
			</p>
		</div>
	</form>
	<script type="text/javascript" src="../wp-content/plugins/<?=EMO_FOLDER?>/emo-vote-admin.js"></script>
<?
	}
	function emo_vote_display($zero='No votes',$one='1 vote',$more='% votes') {
		global $wpdb;
		$i = 0;
		$options = get_option(EMO_OPTIONS);
		$options['titles'] = split('#',$options['titles']);
		$post_id = get_the_ID();
		$question = get_post_meta($post_id,'emo-vote',true);
		$return = (!$question) ? '<div class="emo-vote" id="post-'.$post_id.'">': '<div class="emo-vote" id="post-'.$post_id.'"><p class="emo-vote-title">'.$question.'</p>';
		$table = $wpdb->prefix . 'emo';
		
		if(!$wpdb->get_var('select id from '.$table.' where post_ID='.$post_id.' limit 1')) {
			$wpdb->query('insert into '.$table.'(post_ID) VALUES('.$post_id.')');
		}
		
		if($options['list'] > 0) {
			$values = $wpdb->get_results('select concat(round(vote_0/vote_total*100,0),\'%\') as vote_0,concat(round(vote_1/vote_total*100,0),\'%\') as vote_1,concat(round(vote_2/vote_total*100,0),\'%\') as vote_2,concat(round(vote_3/vote_total*100,0),\'%\') as vote_3,concat(round(vote_4/vote_total*100,0),\'%\') as vote_4,vote_total from '.$table.' where post_ID='.$post_id.' limit 1',ARRAY_A);
		} else {
			$values = $wpdb->get_results('select vote_0,vote_1,vote_2,vote_3,vote_4,vote_total from '.$table.' where post_ID='.$post_id.' limit 1',ARRAY_A);
		}
		
		if(isset($_COOKIE['emo_vote-'.$post_id.''])) {
			$disabled = 'disabled="disabled" ';
		}
		
		foreach($options['titles'] as $title) {
			$title = split(':',$title);
			
			if($_COOKIE['emo_vote-'.$post_id.''] == $title[0]) {
				$checked = 'checked="checked" ';
			}
			
			$value = (!$values[0]['vote_' . $title[0]]) ? '0' : $values[0]['vote_' . $title[0]];
			
			$return .= '<input type="checkbox" name="emo_vote-'.$title[0].'" value="'.$title[0].'" id="emo_vote-'.$title[0].'" '.$disabled.''.$checked.'/><label for="emo_vote-'.$title[0].'">'.$title[1].'</label> <span class="emo_vote-'.$title[0].'">('.$value.')</span>';
			$checked = null;
			++$i;
		}
		
		if($options['total'] > 0) {
			if($values[0]['vote_total'] > 1) {
				$total = str_replace('%',$values[0]['vote_total'],$more);
			} elseif($values[0]['vote_total'] == 1) {
				$total = $one;
			} else {
				$total = $zero;
			}
			$return .= '<input id="emo_locale" type="hidden" value="'.$zero.'#'.$one.'#'.$more.'" /><input id="emo_url" type="hidden" value="'.WP_PLUGIN_URL.'/'.basename(dirname(__FILE__)).'/" /><div class="emo_vote_total">'.$total.'</div></div>';
		} else {
			$return .= '<input id="emo_url" type="hidden" value="'.WP_PLUGIN_URL.'/'.basename(dirname(__FILE__)).'/" /></div>';
		}
		
		echo $return;
	}
	function emo_vote($option,$post_id) {
		global $wpdb;
		$options = get_option(EMO_OPTIONS);
		$option = $wpdb->escape($option);
		$post_id = $wpdb->escape($post_id);
		$option_var = 'vote_' . $option;
		$query = 'select vote_'.$option.',vote_total from '.$wpdb->prefix.'emo where post_ID='.$post_id.' limit 1';
		$results = $wpdb->get_results($query);
		$option_new = intval($results[0]->$option_var);
		$option_total = intval($results[0]->vote_total);

		++$option_new;
		++$option_total;
		
		$query = 'update '.$wpdb->prefix.'emo set '.$option_var.'='.$option_new.',vote_total='.$option_total.' where post_id='.$post_id.'';
		
		if($wpdb->query($query)) {
			/* Thanks to Stephen Cronin for solving the setcookie()-problem, http://www.scratch99.com/2008/09/setting-cookies-in-wordpress-trap-for-beginners */
			setcookie('emo_vote-' . $post_id,$option,(time() + 2592000),$options['path'],$options['url']);
			if($options['list'] > 0) {
				$return = $wpdb->get_results('select concat(round(vote_0/vote_total*100,0),\'%\') as vote_0,concat(round(vote_1/vote_total*100,0),\'%\') as vote_1,concat(round(vote_2/vote_total*100,0),\'%\') as vote_2,concat(round(vote_3/vote_total*100,0),\'%\') as vote_3,concat(round(vote_4/vote_total*100,0),\'%\') as vote_4,vote_total from '.$wpdb->prefix.'emo where post_ID='.$post_id.' limit 1');
				echo $_POST['callback'] . '(' . json_encode(array('response' => array('status' => 200, 'numbers' => $return))) . ')';
			} else {
				$return = $wpdb->get_results('select vote_0,vote_1,vote_2,vote_3,vote_4,vote_total from '.$wpdb->prefix.'emo where post_ID='.$post_id.' limit 1',ARRAY_A);
				echo $_POST['callback'] . '(' . json_encode(array('response' => array('status' => 200, 'numbers' => $return))) . ')';
			}
		} else {
			echo $_POST['callback'] . '(' . json_encode(array('response' => array('status' => 500, 'numbers' => $return))) . ')';
		}
	}
	function emo_options_menu() {
		add_options_page('Emo Vote', 'Emo Vote',8,basename(__FILE__),'emo_options_page');
	}
	function emo_js_frontend() {
		echo '<link rel="stylesheet" href="'.get_bloginfo('wpurl').'/wp-content/plugins/'.EMO_FOLDER.'/emo-vote.css" type="text/css" />';
		wp_enqueue_script('emo-vote.php',path_join(WP_PLUGIN_URL,basename(dirname(__FILE__)) . '/emo-vote-user.js'),array('jquery'));
	}
	
	add_action('admin_menu','emo_options_menu');
	add_action('wp_print_scripts','emo_js_frontend');
?>