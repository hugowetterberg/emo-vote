<?
header('Cache-Control: no-cache');
header('Content-type: application/json');

require('../../../wp-load.php');

if(isset($_POST['emo_vote']) && !isset($_COOKIE['emo_vote-' . $_POST['post']])) {
	emo_vote($_POST['option'],$_POST['post']);
}
?>