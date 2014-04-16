<?php
require dirname(__FILE__).'/connect.php';

class ema{
	
	/**
	 * @var double[]
	 */
	private $ema;
	
	/**
    * @param double[] $data 
    * @param int $range
	* @return double[]
    */
	function get($data,$range){
		$size_data = count($data);

		$my_sma = new sma();
		$sma = $my_sma->get($data,$range);
		
		//Check the position of the first value of the sma calculated - needed for DEMA/TEMA
		$position = 0;
		while(empty($sma[$position])){
			$position++;
		}
		
		//Initialization : the first ema is equal to the first sma
		$this->ema[$position] = $sma[$position];
		
		//Calculation of the following values
		$k = 2/($range+1);
		$i=$position+1;
		while (true){
			if(empty($data[$i])){break;}
			$this->ema[$i] = $this->ema[$i-1] + $k * ($data[$i]-$this->ema[$i-1]);
			$i++;
		}
		return $this->ema; 
	}
}

class sma{
	
	/**
	 * @var double[]
	 */
	private $sma;
	
	/**
     * @param double[] $data
	 * @param int $range
	 * @return double[]
     */
	function get($data,$range){
		
		$position = 0;
		while(empty($data[$position])){
			$position++;
		}
		
		$i=$position;
		while (true){
			if(empty($data[$i+$range-1])){break;}
			$temp_sum=0;
			for ($j=$i; $j<$i+$range; $j++){
				$temp_sum += $data[$j];
			}
			$this->sma[$i+$range-1] = $temp_sum / $range;
			$i++;
		}
		return $this->sma; 
	}
}

if($_GET["action"]=="getrates"){
	header("Content-type: application/json");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	$one = $_GET["home"];
	$two = $_GET["host"];
	$sql = "SELECT `currency_code`, `value` FROM `currencies` WHERE `id` = ( SELECT `id` FROM `currencies` WHERE `currency_code`=:var1 ORDER BY `time` DESC LIMIT 1) OR `id` = ( SELECT `id` FROM `currencies` WHERE `currency_code`=:var2 ORDER BY `time` DESC LIMIT 1)";
	$b=$dbh->prepare($sql);
	$b->bindParam(":var1",$one);
	$b->bindParam(":var2",$two);
	$b->execute();
	$res = $b->fetchAll(PDO::FETCH_ASSOC);
	$json=json_encode($res);
	echo $_GET['callback']."(".$json.");";

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
	}
	if(isset($_GET["host"])){
		if($one!=$two)
		array_push($usedCurrencies, $two);
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
	if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$sql = "INSERT INTO `micheltest`.`currencies_alerts` (`homecurrency`, `hostcurrency`, `ratio`, `email`, `ratioinit`) VALUES (:home, :host, :ratio, :email, :ratioinit)";
		$b=$dbh->prepare($sql);
		$b->bindParam(":home",$home);
		$b->bindParam(":host",$host);
		$b->bindParam(":ratio",$ratio);
		$b->bindParam(":email",$email);
		$b->bindParam(":ratioinit",$ratioinit);
		$b->execute();
		$res = $b->fetchAll(PDO::FETCH_ASSOC);
		$arr = array('status' => 'ok');
		$json=json_encode($arr);
		echo $_GET['callback']."(".$json.");"; 
	}
	else{
		$arr = array('status' => 'bademail');
		$json=json_encode($arr);
		echo $_GET['callback']."(".$json.");"; 
	}
	
}
if($_GET["action"]=="gettrend"){
	header("Content-type: application/json");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	$home = $_GET["home"];
	$sql = "SELECT `value` FROM `currencies` WHERE `currency_code`=:home  ORDER BY `time` ASC";
	$b=$dbh->prepare($sql);
	$b->bindParam(":home",$home);
	$b->execute();
	$res = $b->fetchAll(PDO::FETCH_ASSOC);
	$data = array();
	for ($i=0; $i < count($res); $i++) { 
		array_push($data, $res[$i]["value"]);
	}
	$range = 24*7;
	$my_ema = new ema();
	$ema = $my_ema->get($data,$range);
	$dema = $my_ema->get($ema,$range);
	$better = end($dema) > $res[count($res)-1]["value"];
	$trend = $better ? "Better" : "Worse";
	$arr = array('trend' => $trend, 'ema' => end($ema), 'dema' => end($dema), 'last' => $res[count($res)-1]["value"] );
	$json=json_encode($arr);
	echo $_GET['callback']."(".$json.");";
}
if($_GET["action"]=="getbeerprices"){
	header("Content-type: application/json");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	$home = $_GET["home"];
	$sql = "SELECT `country`, (`price` * ((SELECT `value` FROM `currencies` WHERE `id` = ( SELECT `id` FROM `currencies` WHERE `currency_code`=:home ORDER BY `time` DESC LIMIT 1)))/(SELECT `value` FROM `currencies` WHERE `id` = ( SELECT `id` FROM `currencies` WHERE `currency_code`='GBP' ORDER BY `time` DESC LIMIT 1))) AS price FROM `currencies_beer` ORDER BY RAND() LIMIT 6";
	$b=$dbh->prepare($sql);
	$b->bindParam(":home",$home);
	$b->execute();
	$res = $b->fetchAll(PDO::FETCH_ASSOC);
	$json=json_encode($res);
	echo $_GET['callback']."(".$json.");";

}

?>