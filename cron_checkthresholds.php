<?php
require dirname( __FILE__ ) . '/connect.php';
$sql = "SELECT `id`, `homecurrency`, `hostcurrency`, `ratio`, `email`, `ratioinit` FROM `currencies_alerts` WHERE `done`=false";
$b   = $dbh->prepare( $sql );
$b->execute();
$res = $b->fetchAll( PDO::FETCH_ASSOC );
foreach ( $res as $row ) {
    $sql = "SELECT `currency_code`, `value`, `time` FROM `currencies` WHERE `id` = ( SELECT `id` FROM `currencies` WHERE `currency_code`=:var1 ORDER BY `time` DESC LIMIT 1) OR `id` = ( SELECT `id` FROM `currencies` WHERE `currency_code`=:var2 ORDER BY `time` DESC LIMIT 1)";
    $b   = $dbh->prepare( $sql );
    $b->bindParam( ":var1", $row["homecurrency"] );
    $b->bindParam( ":var2", $row["hostcurrency"] );
    $b->execute();
    $res2 = $b->fetchAll( PDO::FETCH_ASSOC );
    if ( $res2[0]["currency_code"] == $row["homecurrency"] ) {
        $homecurnow = $res2[0]["value"];
        $hostcurnow = $res2[1]["value"];
    } else {
        $homecurnow = $res2[1]["value"];
        $hostcurnow = $res2[0]["value"];
    }
    $rationow = $homecurnow / $hostcurnow;
    if ( ( ( $row["ratioinit"] >= $row["ratio"] ) && ( $row["ratio"] >= $rationow ) ) || ( ( $row["ratioinit"] <= $row["ratio"] ) && ( $row["ratio"] <= $rationow ) ) ) {
        $to      = $row["email"];
        $subject = "It's time to change your " . $row["homecurrency"] . " into " . $row["hostcurrency"];
        $txt     = "<html><head><title>Kurrency Konverter Threshold Alert</title></head><body>";
        $txt .= "<p>You set a threshold on <a href=\"http://lemichel.eu/rgu/kurrencykonverter/\">Kurrency Konverter<a> and it reached it just now!</p>";
        $txt .= "<p>When you set up the alert, the ratio between " . $row["homecurrency"] . " and " . $row["hostcurrency"] . " was " . $row["ratioinit"] . ".</p>";
        $txt .= "<p>It is now " . round( $rationow, 4 ) . "!</p>";
        $txt .= "</body></html>";
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Kurrency Konverter <kurrencykonverter@gmail.com>" . "\r\n";
        $headers .= 'Reply-To: Kurrency Konverter <kurrencykonverter@lemichel.eu>' . "\r\n";
        $resultMail = mail( $to, $subject, $txt, $headers );
        $sql        = "UPDATE `currencies_alerts` SET `done`='1' WHERE `currencies_alerts`.`id`=:id";
        $b          = $dbh->prepare( $sql );
        $b->bindParam( ":id", $row["id"] );
        $b->execute();
        if ( DEBUG == true ) {
            error_log( date( '[Y-m-d H:i e] ' ) . "Mail to: $to \n Success: $resultMail \n" . PHP_EOL, 3, LOG_FILE );
        }
    }
}
?>
