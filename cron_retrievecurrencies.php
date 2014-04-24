<?php
require dirname( __FILE__ ) . '/connect.php';
$contents = file_get_contents( 'http://finance.yahoo.com/webservice/v1/symbols/allcurrencies/quote?format=json' );
$results  = json_decode( $contents, true );
$sql      = "INSERT INTO `currencies` (`currency_code`, `value`, `time`) VALUES ";
$max      = count( $results["list"]["resources"] );
for ( $i = 0; $i < $max; $i++ ) {
    $timearray = explode( "+", $results["list"]["resources"][$i]["resource"]["fields"]["utctime"] );
    $time      = $timearray[0];
    $symbol    = substr( $results["list"]["resources"][$i]["resource"]["fields"]["symbol"], 0, 3 );
    $price     = $results["list"]["resources"][$i]["resource"]["fields"]["price"];
    $sql .= "('" . $symbol . "','" . $price . "','" . $time . "')";
    if ( $i < $max - 1 )
        $sql .= ",";
}
$insertion = $dbh->exec( $sql );
if ( DEBUG == true ) {
    error_log( date( '[Y-m-d H:i e] ' ) . "$insertion \n" . PHP_EOL, 3, LOG_FILE );
}
if ( $insertion === false )
    echo "failed";
else
    echo "success";
?>