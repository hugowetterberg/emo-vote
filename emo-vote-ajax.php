<?
	/* Ugly hack to get access to all WP-functions */
	require('../../../wp-load.php');
	
	if(isset($_POST['emo_vote'])) {
		emo_vote($_POST['option'],$_POST['post']);
	}
?>