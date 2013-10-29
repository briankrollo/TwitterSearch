<?php
		
$SearchId = (isset($_POST['SearchId']) && trim($_POST['SearchId']) != '' ? $_POST['SearchId'] : 1);
//TODO: filter q

require('lib/twittersearch.php');
$TwitterSearch = new twittersearch();
$TwitterSearch->createconnection();
$TwitterSearch->setsearchid($SearchId);
$data = $TwitterSearch->getsearchresults();

echo $data;
?>
