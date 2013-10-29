<?php
		
$q = (isset($_POST['q']) && trim($_POST['q']) != '' ? $_POST['q'] : "Salt Lake City");
//TODO: filter q

require('lib/twittersearch.php');
$TwitterSearch = new twittersearch();
$TwitterSearch->createconnection();
$TwitterSearch->setq($q);
$TwitterSearch->searchtwitter();
$data = $TwitterSearch->storetwitterresults();

echo $data;
?>
