<?
/*
Plugin Name: Emo Vote
Plugin URI: http://emo.vote.nu/
Donate link: http://emo.vote.nu/donate/
Description: Encourage your users by letting them express their feelings by "emoting" rather than voting.
Version: 1.2 Beta
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
define('EMO_LOCAL','emo-vote');

function emo_install() {
	global $wpdb;
	$wpdb->emo_vote = $wpdb->prefix .'emo';
	
	if($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->emo_vote}'") != $wpdb->emo_vote)
		$wpdb->query("CREATE TABLE {$wpdb->emo_vote} (
			id int(10) NOT NULL auto_increment,
			post_ID int(10) NOT NULL,
			vote_0 int(10) NOT NULL default '0',
			vote_1 int(10) NOT NULL default '0',
			vote_2 int(10) NOT NULL default '0',
			vote_3 int(10) NOT NULL default '0',
			vote_4 int(10) NOT NULL default '0',
			vote_total int(10) NOT NULL default '0',
			PRIMARY KEY (id)
			)
		");
	
	add_option(EMO_OPTIONS,
		array(
			'titles' => '0:mad#1:bored#2:curious#3:ok#4:happy',
			'list' => 1,
			'total' => 1
		)
	);
}
register_activation_hook(__FILE__,'emo_install');

function emo_vote_init() {
	global $wpdb;
	$wpdb->emo_vote = $wpdb->prefix .'emo';
	
	load_plugin_textdomain(EMO_LOCAL,'/wp-content/plugins/emo-vote/language');
}
add_action('init','emo_vote_init');

function emo_options_page() {
	$options = get_option(EMO_OPTIONS);
	$post = $_POST[EMO_OPTIONS];
	
	if(isset($post['submit'])) {
		$options['titles'] = $post['titles'];
		$options['list'] = $post['list'];
		$options['total'] = $post['total'];
		$options['rss'] = $post['rss'];
		update_option(EMO_OPTIONS,$options);
		echo '<div class="updated"><p><strong>'. __('Options Updated',EMO_LOCAL) .'</strong></p></div>';
	}
	
	$options['titles'] = split('#',$options['titles']);
?>
<form id="emo_form" method="post" action="options-general.php?page=emo-vote.php">
	<div class="wrap">
		<h2><? _e('Emo Vote Options',EMO_LOCAL); ?></h2>
		<p><? _e('Choose number of fields, give them a desired name and move them around until you\'re satisfied then hit Update Options.',EMO_LOCAL); ?></p>
		<ul id="emo-list">
		<? foreach($options['titles'] as $option) { ?>
			<? list($val,$key) = split(':',$option); ?>
			<li id="emo_titles-<? echo $val; ?>" class="emo_titles_field">
				<span class="emotion" style="background: transparent url(<? echo emo_path('images/checkbox_'. $val .'.png'); ?>);"></span></label><input type="text" size="16" name="emo_titles-<? echo $val; ?>" id="emo_titles-<? echo $val; ?>" value="<? echo $key; ?>" /><span class="emo-delete" id="emo_titles-<? echo $val; ?>"></span>
			</li>
		<? } ?>
		</ul>
		<p class="plain">
			<label for="<? echo EMO_OPTIONS; ?>[list]"><? _e('Display votes in',EMO_LOCAL); ?></label>
			<select name="<? echo EMO_OPTIONS; ?>[list]">
				<option value="1" <? echo ($options['list'] == '1' ? 'selected' : ''); ?>><? _e('Percent',EMO_LOCAL); ?></option>
				<option value="0" <? echo ($options['list'] == '1' ? '' : 'selected'); ?>><? _e('Numbers',EMO_LOCAL); ?></option>
			</select>
			<label for="<? echo EMO_OPTIONS; ?>[total]"><? _e('Display total votes',EMO_LOCAL); ?></label>
			<select name="<? echo EMO_OPTIONS; ?>[total]">
				<option value="1" <? echo ($options['total'] == '1' ? 'selected' : ''); ?>><? _e('Yes',EMO_LOCAL); ?></option>
				<option value="0" <? echo ($options['total'] == '1' ? '' : 'selected'); ?>><? _e('No',EMO_LOCAL); ?></option>
			</select>
			<label for="<? echo EMO_OPTIONS; ?>[rss]"><? _e('Enable RSS emoting',EMO_LOCAL); ?></label>
			<select name="<? echo EMO_OPTIONS; ?>[rss]">
				<option value="1" <? echo ($options['rss'] == '1' ? 'selected' : ''); ?>><? _e('Yes',EMO_LOCAL); ?></option>
				<option value="0" <? echo ($options['rss'] == '1' ? '' : 'selected'); ?>><? _e('No',EMO_LOCAL); ?></option>
			</select>
		</p>
		<p class="submit">
			<span id="emo_vote_url" style="display: none;"><? echo emo_path('images/'); ?></span>
			<input type="hidden" name="<? echo EMO_OPTIONS; ?>[titles]" id="emo_options_titles" value="" />
			<input type="submit" name="<? echo EMO_OPTIONS; ?>[submit]" id="emo_options_submit" value="<? _e('Update Options',EMO_LOCAL); ?>" />
			<input type="submit" name="emo_add" value="<? _e('Add field',EMO_LOCAL); ?>" id="emo_add" />
			<span class="emo_vote_error hidden"><? _e('You can\'t add more then five fields',EMO_LOCAL); ?></span>
		</p>
	</div>
</form>
<script type="text/javascript" src="<? echo emo_path('emo-vote-admin.js'); ?>"></script>
<?
}
add_action('admin_menu','emo_options_menu');

function emo_vote_display($zero='No votes',$one='1 vote',$more='% votes') {
	global $wpdb;
	$vote = strval($_GET['emo']);
	$options = get_option(EMO_OPTIONS);
	$titles = split('[#]+',$options['titles']);
	$post_id = get_the_ID();
	$question = get_post_meta($post_id,'emo-vote',true);
	$return = !$question ? '<div class="emo-vote" id="emo-vote_'. $post_id .'">' : '<div class="emo-vote" id="emo-vote_'. $post_id .'"><p class="emo-vote-title">'. $question .'</p>';
	
	if(!$wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->emo_vote} WHERE post_ID = %d LIMIT 1",$post_id))) {
		$unit = $options['list'] > 0 ? '%' : '';
		$values = array('vote_0' => 0 . $unit,'vote_1' => 0 . $unit,'vote_2' => 0 . $unit,'vote_3' => 0 . $unit,'vote_4' => 0 . $unit,'vote_total' => 0);
	} else {
		if($options['list'] > 0)
			$values = $wpdb->get_row($wpdb->prepare("SELECT CONCAT(round(vote_0/vote_total*100,0),'&#37;') as vote_0, CONCAT(round(vote_1/vote_total*100,0),'&#37;') as vote_1, CONCAT(round(vote_2/vote_total*100,0),'&#37;') as vote_2, CONCAT(round(vote_3/vote_total*100,0),'&#37;') as vote_3, CONCAT(round(vote_4/vote_total*100,0),'&#37;') as vote_4, vote_total FROM {$wpdb->emo_vote} WHERE post_ID = %d",$post_id),ARRAY_A);
		else
			$values = $wpdb->get_row($wpdb->prepare("SELECT vote_0, vote_1, vote_2, vote_3, vote_4, vote_total FROM {$wpdb->emo_vote} WHERE post_ID = %d",$post_id),ARRAY_A);
	}
	
	if(is_emo())
		$disabled = 'disabled="disabled" ';
	
	foreach($titles as $title) {
		list($key,$val) = split(':',$title);
		
		if($_COOKIE['emo_vote-'. $post_id] == $key)
			$checked = 'checked="checked" ';
		
		$value = $values['vote_'. $key] ? $values['vote_'. $key] : '0';
		$return .= '<input type="checkbox" name="emo_vote-'. $key .'" value="'. $key .'" class="emo_vote-'. $key .'" '. $disabled .''. $checked .'/><label>'. $val .'</label> <span class="emo_vote-'. $key .'">('. $value .')</span>';
		$checked = null;
	}
	
	if($options['total'] > 0) {
		$total = $values['vote_total'] > 1 ? str_replace('%',$values['vote_total'],$more) : ($values['vote_total'] == 1 ? $one : $zero);
		$return .= '<input class="emo_locale" type="hidden" value="'. $zero .'#'. $one .'#'. $more .'" /><div class="emo_vote_total">'. $total .'</div>';
	}
	
	$return .= '<input class="emo_url" type="hidden" value="'. emo_path() .'" /></div>';
	
	if(is_single() && !is_emo() && strlen($vote) > 0)
		$return .= '<script type="text/javascript">jQuery(document).ready(function(){jQuery(\'body\').emoDialog({option:'. $vote .',str:\''. __('Feeling',EMO_LOCAL) .' '. $_GET['vote'] .'\'});});</script>';
	
	echo $return;
}

function emo_vote_display_rss($content) {
	$options = get_option(EMO_OPTIONS);
	
	if($options['rss'] > 0 && is_feed()) {
		$question = get_post_meta(get_the_ID(),'emo-vote',true);
		$content .= '<p style="text-align:center;">';
		$content .= (!$question) ? '' : $question .'</p><p style="text-align:center;">';
		$permalink = (!get_option('permalink_structure')) ? get_permalink(get_the_ID()) . '&amp;' : get_permalink(get_the_ID()) . '?';
		$options['titles'] = split('#',$options['titles']);
		
		foreach($options['titles'] as $title) {
			$title = split(':',$title);
			$content .= '<a href="'. $permalink .'emo='. $title[0] .'&amp;vote='. urlencode($title[1]) .'" title="'. $title[1] .'">'. $title[1] .'?</a> ';
		}
		
		$content .= '</p>';
		
		return $content;
	} else {
		return $content;
	}
}
add_filter('the_content','emo_vote_display_rss');

function is_emo($post_id=null) {
	$post_id = (!$post_id) ? get_the_ID() : $post_id;
	
	// Determines whether a user already has emoted or not.
	return isset($_COOKIE['emo_vote-'. $post_id]) ? true : false;
}

function emo_path($file=null) {
	return path_join(WP_PLUGIN_URL,basename(dirname(__FILE__))) .'/'. $file;
}

function emo_vote() {
	if(isset($_POST['emo_vote']) && isset($_POST['option']) && isset($_POST['post']) && !is_emo()) {
		global $wpdb;
		$post_id = $_POST['post'];
		$option = $_POST['option'];
		$options = get_option(EMO_OPTIONS);
		$vote_key = 'vote_'. $option;
	
		// Insert a row for the post first when a user emotes.
		if(!$wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->emo_vote} WHERE post_id = %d LIMIT 1",$post_id)))
			$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->emo_vote} (post_ID) VALUES (%d)",$post_id));
	
		// Update the choosen key and total sum.
		$results = $wpdb->get_row($wpdb->prepare("SELECT {$vote_key},vote_total FROM {$wpdb->emo_vote} WHERE post_ID = %d",$post_id),ARRAY_A);
		$vote_val = intval($results[$vote_key]);
		$vote_total = intval($results['vote_total']);
		++$vote_val;
		++$vote_total;
	
		if($wpdb->query($wpdb->prepare("UPDATE {$wpdb->emo_vote} SET {$vote_key} = %d,vote_total = %d WHERE post_ID = %d LIMIT 1",$vote_val,$vote_total,$post_id))) {
			// Thanks to Stephen Cronin for solving the setcookie probs: http://www.scratch99.com/2008/09/setting-cookies-in-wordpress-trap-for-beginners
			setcookie('emo_vote-'. $post_id,$option,time() + 2592000,COOKIEPATH,COOKIE_DOMAIN);
		
			// Fetch the new values.
			if($options['list'] > 0)
				$return = $wpdb->get_row($wpdb->prepare("SELECT CONCAT(round(vote_0/vote_total*100,0),'&#37;') as vote_0, CONCAT(round(vote_1/vote_total*100,0),'&#37;') as vote_1, CONCAT(round(vote_2/vote_total*100,0),'&#37;') as vote_2, CONCAT(round(vote_3/vote_total*100,0),'&#37;') as vote_3, CONCAT(round(vote_4/vote_total*100,0),'&#37;') as vote_4, vote_total FROM {$wpdb->emo_vote} WHERE post_ID = %d",$post_id),ARRAY_A);
			else
				$return = $wpdb->get_row($wpdb->prepare("SELECT vote_0, vote_1, vote_2, vote_3, vote_4, vote_total FROM {$wpdb->emo_vote} WHERE post_ID = %d",$post_id),ARRAY_A);
		
			echo $_POST['callback'] .'('. json_encode(array('response' => array('status' => 200, 'numbers' => $return))) .')';
		} else {
			echo $_POST['callback'] .'('. json_encode(array('response' => array('status' => 500, 'numbers' => null))) .')';
		}
		
		// End ajax-request.
		die();
	}
}
add_action('init','emo_vote');

function emo_options_menu() {
	add_options_page('Emo Vote','Emo Vote',8,basename(__FILE__),'emo_options_page');
}

function emo_js_frontend() {
	echo '<link rel="stylesheet" href="'. emo_path('emo-vote.css') .'" type="text/css" />';
	wp_enqueue_script('emo-vote.php',emo_path('emo-vote-user.js'),array('jquery'),true);
}
add_action('wp_print_scripts','emo_js_frontend');
?>