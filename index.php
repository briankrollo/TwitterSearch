<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Brian Rollo's Twitter Search Project</title>
		<link rel="stylesheet" href="css/style.css" />
		
		<script src="js/jquery-1.9.1.js"></script>
		<script src="http://code.highcharts.com/highcharts.js"></script>
		
		<script src="js/twittersearch.js"></script>
    </head>
    <body>
		<?php
		require('lib/twittersearch.php');
		
		$TwitterSearch = new twittersearch();
		$QueryResult = $TwitterSearch->getsearches();
		?>
		
		<div class="wrapper">
			<h3>Search Twitter</h3>
			<p class='instructions'>Search Twitter using the form below.  
				Your search and the top 100 results will be saved.  
				A graph of interesting and unusual statistics will also be made available.</p>
			<div id="search" class="internal">
				<form id="searchform">
					<input type="text" name="q" value="" />&nbsp;&nbsp;
					<input type="submit" name="search" value="search" />
				</form>
			</div>
		</div>
		
		<div class="wrapper" id='querieswrapper' style='<?= mysql_num_rows($QueryResult) == 0 ? "display:none;" : "" ?>'>
			<h3>Twitter Searches</h3>
			<p class='instructions'>Click a search to view the results and a graph of unusual statistics.</p>
			<div id="queries" class="internal">
				<ul>
				<?php
				while ($Data = mysql_fetch_assoc($QueryResult)) {
					echo "<li class='searchlink'><a SearchId='$Data[SearchId]' href='javascript://'>$Data[Query]</a></li>";
				}
				?>
				</ul>
			</div>
		</div>
		
		<div id='results' style="clear:both;">
			
			<div class="wrapper">
				<h3>Results for '<span class='SearchName'></span>'</h3>
				<p class='instructions'>See the results for the selected search</p>
				<div id="tweets" class="internal">
					<ul>

					</ul>
				</div>
			</div>

			<div class="wrapper">
				<h3>Graph for '<span class='SearchName'></span>'</h3>
				<p class='instructions' style='width:40em;'>A graph showing the alphabetic distribution of the first letters of each tweet.
					Click on the bar for any letter to see the distribution of the tweets across specific second ranges.</p>
				<div id="graph" class="internal">

				</div>
			</div>
		</div>
    </body>
</html>
