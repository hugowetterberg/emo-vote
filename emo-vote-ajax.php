<?
header('Cache-Control: no-cache');
header('Content-type: application/json');

require('../../../wp-load.php');

if(isset($_POST['emo_vote']) && !is_emo())
	emo_vote($_POST['option'],$_POST['post']);
?>