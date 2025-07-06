<?php

if (!session_id()) {
    session_start();
}

$sip_host = $_SESSION['sip2_host'];
$sip_port = 6001;
$login_user = $_SESSION['sip2_user'];
$login_pass = $_SESSION['sip2_pass'];
$card_number = $_SESSION['sip2_card'];
$library_number = $_SESSION['sip2_lib'];

unset($_SESSION['sip2_host']);
unset($_SESSION['sip2_user']);
unset($_SESSION['sip2_pass']);
// unset($_SESSION['sip2_card']);
// unset($_SESSION['sip2_lib']);

$result = 4;

$socket = @stream_socket_client("tcp://$sip_host:$sip_port", $errno, $errstr, 10);
if ($socket) {
    $result = 3;

    $login_msg = "9300CN{$login_user}|CO{$login_pass}|\r";
    fwrite($socket, $login_msg);

    $transaction_date = date('Ymd    His');
    $msg = "23000" . $transaction_date . "AO{$login_user}|AA{$card_number}|AC{$login_pass}|\r";
    fwrite($socket, $msg);

    $response = fgets($socket, 1024);

    fclose($socket);

    if (substr( $response, 0, strlen('941') ) === '941') {
        if (strpos($response, '|BLY|') !== false) {
            $result = 1;
        } else {
            $result = 2;
        }
    }
}

$_SESSION['sip2_response'] = $result;
$login_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST']."/login-evergreen/{$library_number}/";
header("Location: {$login_url}");
exit;
?>
