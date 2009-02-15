<?
/*
Plugin Name: Emo Vote
Plugin URI: http://emo.vote.nu/
Donate link: http://emo.vote.nu/donate/
Description: Encourage your users be letting them express their feelings by "emoting" rather then voting.
Version: 1.1
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

define('EMO_OPTIONS','emo_options');

function emo_install() {
	global $wpdb;
	$table = $wpdb->prefix . 'emo';
	
	if($wpdb->get_var('show tables like \''. $table .'\'') != $table) {
		$wpdb->query('create table '. $table .'(
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
	
	$options = array(
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
	
	if(isset($post['submit'])) {
		$options['titles'] = $post['titles'];
		$options['list'] = $post['list'];
		$options['total'] = $post['total'];
		$options['rss'] = $post['rss'];
		update_option(EMO_OPTIONS,$options);
		echo '<div class="updated"><p><strong>Options Updated</strong></p></div>';
	}
	
	$options['titles'] = split('#',$options['titles']);
?>
<form id="emo_form" method="post" action="options-general.php?page=emo-vote.php">
	<div class="wrap">
		<h2>Emo Vote Options</h2>
		<p>Choose number of fields, give them a desired name and move them around until you're satisfied then hit Update Options.</p>
		<ul id="emo-list">
		<? foreach($options['titles'] as $option) { ?>
			<? $option_value = split(':',$option); ?>
			<li id="emo_titles-<?=$option_value[0]?>" class="emo_titles_field">
				<span class="emotion" style="background: transparent url(<?=emo_path('images/checkbox_'. $option_value[0] .'.png')?>);"></span></label><input type="text" size="16" name="emo_titles-<?=$option_value[0]?>" id="emo_titles-<?=$option_value[0]?>" value="<?=$option_value[1]?>" /><span class="emo-delete" id="emo_titles-<?=$option_value[0]?>"></span>
			</li>
		<? } ?>
		</ul>
		<p class="plain">
			<label for="<?=EMO_OPTIONS?>[list]">Display votes in</label>
			<select name="<?=EMO_OPTIONS?>[list]">
				<option value="1" <?=($options['list'] == '1') ? 'selected' : ''?>>Percent</option>
				<option value="0" <?=($options['list'] == '1') ? '' : 'selected'?>>Numbers</option>
			</select>
			<label for="<?=EMO_OPTIONS?>[total]">Display total votes</label>
			<select name="<?=EMO_OPTIONS?>[total]">
				<option value="1" <?=($options['total'] == '1') ? 'selected' : ''?>>Yes</option>
				<option value="0" <?=($options['total'] == '1') ? '' : 'selected'?>>No</option>
			</select>
			<label for="<?=EMO_OPTIONS?>[rss]">Enable RSS emoting</label>
			<select name="<?=EMO_OPTIONS?>[rss]">
				<option value="1" <?=($options['rss'] == '1') ? 'selected' : ''?>>Yes</option>
				<option value="0" <?=($options['rss'] == '1') ? '' : 'selected'?>>No</option>
			</select>
		</p>
		<p class="submit">
			<span id="emo_vote_url" style="display: none;"><?=emo_path('images/')?></span>
			<input type="hidden" name="<?=EMO_OPTIONS?>[titles]" id="emo_options_titles" value="" />
			<input type="submit" name="<?=EMO_OPTIONS?>[submit]" id="emo_options_submit" value="Update Options" />
			<input type="submit" name="emo_add" value="Add field" id="emo_add" />
			<span class="emo_vote_error"></span>
		</p>
	</div>
</form>
<script type="text/javascript" src="<?=emo_path('emo-vote-admin.js')?>"></script>
<?
}
function emo_vote_display($zero='No votes',$one='1 vote',$more='% votes') {
	global $wpdb;
	$i = 0;
	$vote = strval($_GET['emo']);
	$options = get_option(EMO_OPTIONS);
	$options['titles'] = split('#',$options['titles']);
	$post_id = get_the_ID();
	$question = get_post_meta($post_id,'emo-vote',true);
	$return = (!$question) ? '<div class="emo-vote" id="emo-vote_'. $post_id .'">' : '<div class="emo-vote" id="emo-vote_'. $post_id .'"><p class="emo-vote-title">'. $question .'</p>';
	
	if(!$wpdb->get_var('select id from '. $wpdb->prefix .'emo where post_ID = \''. $post_id .'\' limit 1')) {
		$unit = ($options['list'] != 0) ? '%' : '';
		$values[0] = array('vote_0' => 0 . $unit,'vote_1' => 0 . $unit,'vote_2' => 0 . $unit,'vote_3' => 0 . $unit,'vote_4' => 0 . $unit,'vote_total' => 0);
	} else {
		if($options['list'] > 0) {
			$values = $wpdb->get_results('select concat(round(vote_0/vote_total*100,0),\'%\') as vote_0,concat(round(vote_1/vote_total*100,0),\'%\') as vote_1,concat(round(vote_2/vote_total*100,0),\'%\') as vote_2,concat(round(vote_3/vote_total*100,0),\'%\') as vote_3,concat(round(vote_4/vote_total*100,0),\'%\') as vote_4,vote_total from '. $wpdb->prefix .'emo where post_ID = \''. $post_id .'\' limit 1',ARRAY_A);
		} else {
			$values = $wpdb->get_results('select vote_0,vote_1,vote_2,vote_3,vote_4,vote_total from '. $wpdb->prefix .'emo where post_ID = \''. $post_id .'\' limit 1',ARRAY_A);
		}
	}
	
	if(is_emo())
		$disabled = 'disabled="disabled" ';
	
	foreach($options['titles'] as $title) {
		$title = split(':',$title);
		
		if($_COOKIE['emo_vote-' . $post_id] == $title[0])
			$checked = 'checked="checked" ';
		
		$value = (!$values[0]['vote_' . $title[0]]) ? '0' : $values[0]['vote_' . $title[0]];
		
		$return .= '<input type="checkbox" name="emo_vote-'. $title[0] .'" value="'. $title[0] .'" class="emo_vote-'. $title[0] .'" '. $disabled .''. $checked .'/><label>'. $title[1] .'</label> <span class="emo_vote-'. $title[0] .'">('. $value .')</span>';
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
		$return .= '<input class="emo_locale" type="hidden" value="'. $zero .'#'. $one .'#'. $more .'" /><div class="emo_vote_total">'. $total .'</div>';
	}
	
	$return .= '<input class="emo_url" type="hidden" value="'. emo_path() .'" /></div>';
	
	if(is_single() && !is_emo() && strlen($vote) > 0) {
		$return .= '<script type="text/javascript">jQuery(document).ready(function(){jQuery(\'body\').emoDialog({option:'. $vote .',str:\''. $_GET['vote'] .'\'});});</script>';
	}
	
	echo $return;
}
function emo_vote_display_rss() {
	$options = get_option(EMO_OPTIONS);
	
	if($options['rss'] > 0 && is_feed()) {
		$question = get_post_meta(get_the_ID(),'emo-vote',true);
		$content = get_the_content() .'<p style="text-align:center;">';
		$content .= (!$question) ? '' : $question .'</p><p style="text-align:center;">';
		$permalink = (!get_option('permalink_structure')) ? get_permalink(get_the_ID()) . '&amp;' : get_permalink(get_the_ID()) . '?';
		$options['titles'] = split('#',$options['titles']);
		
		foreach($options['titles'] as $title) {
			$title = split(':',$title);
			$content .= '<a href="'. $permalink .'emo='. $title[0] .'&amp;vote='. urlencode($title[1]) .'" title="'. $title[1] .'">'. $title[1] .'?</a> ';
		}
		
		$content .= '</p>';
		
		echo $content;
	} else {
		echo get_the_content();
	}
}
function is_emo($post_id=null) {
	$post_id = (!$post_id) ? get_the_ID() : $post_id;
	
	// Determines whether a user already has emoted or not.
	return (!$_COOKIE['emo_vote-'. $post_id]) ? false : true;
}
function emo_path($file=null) {
	return path_join(WP_PLUGIN_URL,basename(dirname(__FILE__))) .'/'. $file;
}
function emo_vote($option,$post_id) {
	global $wpdb;
	$options = get_option(EMO_OPTIONS);
	$option = $wpdb->escape($option);
	$post_id = $wpdb->escape($post_id);
	$option_var = 'vote_'. $option;
	
	if(!$wpdb->get_results('select vote_'. $option .',vote_total from '. $wpdb->prefix .'emo where post_id = \''. $post_id .'\' limit 1'))
		$wpdb->query('insert into '. $wpdb->prefix .'emo(post_ID) values(\''.$post_id.'\')');
	
	$results = $wpdb->get_results('select vote_'. $option .',vote_total from '. $wpdb->prefix .'emo where post_ID = \''. $post_id .'\' limit 1');
	$option_new = intval($results[0]->$option_var);
	$option_total = intval($results[0]->vote_total);
	
	if($wpdb->query('update '. $wpdb->prefix .'emo set '. $option_var .' = \''. ++$option_new .'\',vote_total = \''. ++$option_total .'\' where post_id = \''. $post_id .'\' limit 1')) {
		/* Thanks to Stephen Cronin for solving the setcookie()-problem, http://www.scratch99.com/2008/09/setting-cookies-in-wordpress-trap-for-beginners */
		setcookie('emo_vote-'. $post_id,$option,time() + 2592000,COOKIEPATH,COOKIE_DOMAIN);
		
		if($options['list'] > 0) {
			$return = $wpdb->get_results('select concat(round(vote_0/vote_total*100,0),\'%\') as vote_0,concat(round(vote_1/vote_total*100,0),\'%\') as vote_1,concat(round(vote_2/vote_total*100,0),\'%\') as vote_2,concat(round(vote_3/vote_total*100,0),\'%\') as vote_3,concat(round(vote_4/vote_total*100,0),\'%\') as vote_4,vote_total from '. $wpdb->prefix .'emo where post_ID = \''. $post_id .'\' limit 1');
			echo $_POST['callback'] .'('. json_encode(array('response' => array('status' => 200, 'numbers' => $return))) .')';
		} else {
			$return = $wpdb->get_results('select vote_0,vote_1,vote_2,vote_3,vote_4,vote_total from '. $wpdb->prefix .'emo where post_ID = \''. $post_id .'\' limit 1',ARRAY_A);
			echo $_POST['callback'] .'('. json_encode(array('response' => array('status' => 200, 'numbers' => $return))) .')';
		}
	} else {
		echo $_POST['callback'] .'('. json_encode(array('response' => array('status' => 500, 'numbers' => null))) .')';
	}
}
function emo_options_menu() {
	add_options_page('Emo Vote','Emo Vote',8,basename(__FILE__),'emo_options_page');
}
function emo_js_frontend() {
	echo '<link rel="stylesheet" href="'. emo_path('emo-vote.css') .'" type="text/css" />';
	wp_enqueue_script('emo-vote.php',emo_path('emo-vote-user.js'),array('jquery'),true);
}

add_filter('the_content','emo_vote_display_rss');
add_action('admin_menu','emo_options_menu');
add_action('wp_print_scripts','emo_js_frontend');
?>