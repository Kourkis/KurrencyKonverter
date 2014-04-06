<?php
require dirname(__FILE__).'/connect.php';
if($_GET["action"]=="getrates"){
	header("Content-type: application/json");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	$one = $_GET["home"];
	$two = $_GET["host"];
	$sql = "SELECT `currency_code`, `value` FROM `currencies` WHERE `id` = ( SELECT `id` FROM `currencies` WHERE `currency_code`='EUR' ORDER BY `time` DESC LIMIT 1) OR `id` = ( SELECT `id` FROM `currencies` WHERE `currency_code`='USD' ORDER BY `time` DESC LIMIT 1)";
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
	$sql = "SELECT `currency_code`, `value`, `time` FROM `currencies` WHERE `currency_code`=:var1 OR `currency_code`=:var2 ORDER BY `time` ASC";
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
	$usedCurrencies = array();

	if(isset($_GET["home"])){
		array_push($usedCurrencies, $one);
		/*$key = array_search($one, $currencies);
		if($key){
			unset($currencies[$key]);
			$currencies = array_values($currencies);
		}
		array_splice($currencies, 0, 0, $one);*/
	}
	if(isset($_GET["host"])){
		if($one!=$two)
		array_push($usedCurrencies, $two);
		/*$key = array_search($two, $currencies);
		if($key){
			unset($currencies[$key]);
			$currencies = array_values($currencies);
		}
		array_splice($currencies, 1, 0, $two);*/
	}
	for ($i=0; $i < 6; $i++) { 
		if(!in_array($currencies[$i], $usedCurrencies)){
			array_push($usedCurrencies, $currencies[$i]);
		}
	}
	for ($i=0; $i < 6; $i++) { 
		$sql = "SELECT `currency_code`, `value`, (	(	SELECT `value` FROM `currencies` WHERE `currency_code`=:var1 ORDER BY `time` DESC LIMIT 1)-(	SELECT `value` FROM `currencies` WHERE `time` >= NOW( ) - INTERVAL 3 DAY AND `currency_code`=:var1 ORDER BY `time` ASC LIMIT 1))/(	SELECT `value` FROM `currencies` WHERE `currency_code`=:var1 ORDER BY `time` DESC LIMIT 1)*100 AS `percent` FROM `currencies` WHERE `time` >= NOW( ) - INTERVAL 3 DAY AND (`currency_code`=:var1 ) ORDER BY `time` ASC LIMIT 1";
		$b=$dbh->prepare($sql);
		$b->bindParam(":var1",$usedCurrencies[$i]);
		$b->execute();
		$res = $b->fetchAll(PDO::FETCH_ASSOC);
		array_push($tab, $res);
	}

	$json=json_encode($tab);
	echo $_GET['callback']."(".$json.");";
	if(DEBUG == true) {
		//error_log(date('[Y-m-d H:i e] '). $currencies[0] . $currencies[1] . $currencies[2] . $currencies[3] ." \n". PHP_EOL, 3, LOG_FILE);
	}
}

if($_GET["action"]=="setthreshold"){
	header("Content-type: application/json");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	$home = $_GET["home"];
	$host = $_GET["host"];
	$ratio = $_GET["ratio"];
	$email = $_GET["email"];
	$ratioinit = $_GET["ratioinit"];
	$sql = "INSERT INTO `micheltest`.`currencies_alerts` (`homecurrency`, `hostcurrency`, `ratio`, `email`, `ratioinit`) VALUES (:home, :host, :ratio, :email, :ratioinit)";
	$b=$dbh->prepare($sql);
	$b->bindParam(":home",$home);
	$b->bindParam(":host",$host);
	$b->bindParam(":ratio",$ratio);
	$b->bindParam(":email",$email);
	$b->bindParam(":ratioinit",$ratioinit);
	$b->execute();
	$res = $b->fetchAll(PDO::FETCH_ASSOC);
	$json=json_encode($res);
	echo $_GET['callback']."(".$json.");";
}
if($_GET["action"]=="nextlowestpoint"){
	print_r(get_loaded_extensions());
if(extension_loaded("session")){
	echo "session loaded";
}
if(extension_loaded("trader")){
	echo "trader loaded";
}
}
?>