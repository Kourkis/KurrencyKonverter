<?php
require dirname(__FILE__).'/connect.php';
if($_GET["action"]=="getrates"){
	header("Content-type: application/json");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	$one = $_GET["home"];
	$two = $_GET["host"];
	$sql = "SELECT `currency_code`, `value` FROM `currencies` WHERE `currency_code`=:var1 OR `currency_code`=:var2 ORDER BY `time` DESC LIMIT 2";
	$b=$dbh->prepare($sql);
	$b->bindParam(":var1",$one);
	$b->bindParam(":var2",$two);
	$b->execute();
	$res = $b->fetchAll(PDO::FETCH_ASSOC);
	$json=json_encode($res);
	echo $_GET['callback']."(".$json.");";
	if(DEBUG == true) {
		//var_dump($res);
		//var_dump($b);
		//error_log(date('[Y-m-d H:i e] '). $_GET["action"] . $_GET["home"] . $_GET["host"] ." \n". PHP_EOL, 3, LOG_FILE);
	}
}
if($_GET["action"]=="getcurrencieshistory"){
	header("Content-type: application/json");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	$one = $_GET["home"];
	$two = $_GET["host"];
	$sql = "SELECT `currency_code`, `value`, `time` FROM `currencies` WHERE `currency_code`=:var1 OR `currency_code`=:var2 ORDER BY `time` DESC";
	$b=$dbh->prepare($sql);
	$b->bindParam(":var1",$one);
	$b->bindParam(":var2",$two);
	$b->execute();
	$res = $b->fetchAll(PDO::FETCH_ASSOC);
	$json=json_encode($res);
	echo $_GET['callback']."(".$json.");";
	if(DEBUG == true) {
		//var_dump($res);
		//var_dump($b);
		//error_log(date('[Y-m-d H:i e] '). $_GET["action"] . $_GET["home"] . $_GET["host"] ." \n". PHP_EOL, 3, LOG_FILE);
	}
}
if($_GET["action"]=="getpopularcurrencies"){
	header("Content-type: application/json");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	$one = $_GET["home"];
	$two = $_GET["host"];
	$tab = array();
	$currencies = array("GBP", "EUR", "AUD", "CAD", "INR", "CNY");

	if(isset($_GET["home"])){
		
		$key = array_search($one, $currencies);
		if($key){
			unset($currencies[$key]);
			$currencies = array_values($currencies);
		}
		array_splice($currencies, 0, 0, $one);
	}
	if(isset($_GET["host"])){
		
		$key = array_search($two, $currencies);
		if($key){
			unset($currencies[$key]);
			$currencies = array_values($currencies);
		}
		array_splice($currencies, 1, 0, $two);
	}
	for ($i=0; $i < 6; $i++) { 
		$sql = "SELECT `currency_code`, `value`, (	(	SELECT `value` FROM `currencies` WHERE `currency_code`=:var1 ORDER BY `time` DESC LIMIT 1)-(	SELECT `value` FROM `currencies` WHERE `time` >= NOW( ) - INTERVAL 1 DAY AND `currency_code`=:var1 ORDER BY `time` ASC LIMIT 1))/(	SELECT `value` FROM `currencies` WHERE `currency_code`=:var1 ORDER BY `time` DESC LIMIT 1)*100 AS `percent` FROM `currencies` WHERE `time` >= NOW( ) - INTERVAL 1 DAY AND (`currency_code`=:var1 ) ORDER BY `time` ASC LIMIT 1";
		$b=$dbh->prepare($sql);
		$b->bindParam(":var1",$currencies[$i]);
		$b->execute();
		$res = $b->fetchAll(PDO::FETCH_ASSOC);
		array_push($tab, $res);
	}

	$json=json_encode($tab);
	echo $_GET['callback']."(".$json.");";
	if(DEBUG == true) {
		//var_dump($res);
		//var_dump($b);
		//error_log(date('[Y-m-d H:i e] '). $_GET["action"] . $_GET["home"] . $_GET["host"] ." \n". PHP_EOL, 3, LOG_FILE);
	}
}


?>