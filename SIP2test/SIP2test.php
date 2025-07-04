<?php

$servername = "127.0.0.1";
$username   = "uvgzpvd7etoas";
$password   = "5owadbxnyynr";
$dbname     = "dbh8ydrmngjls7";

function validate_sip2_card($card_number, $sip_host, $sip_port, $login_user = '', $login_pass = '') {
    $socket = fsockopen($sip_host, $sip_port, $errno, $errstr, 10);
    if (!$socket) {
        return "Connection failed.";
    }

    // Example login message (9300 = login message, CN=login user, CO=login password)
    $login_msg = "9300CN{$login_user}|CO{$login_pass}|\r";
    fwrite($socket, $login_msg);
    $login_response = fgets($socket); // Read login response

    if ($login_response == 941) {   // 941 response is a successful login to the SIP2 server
        // Create Patron Status message (23)
        // Format: 23<language><transaction date>AO<login username>|AA<patron identifier>|AC<terminal password>|
        $transaction_date = date('Ymd    His');  // the 4 spaces between "Ymd" and "His" are important
        $msg = "23000" . $transaction_date . "AO{$login_user}|AA{$card_number}|AC{$login_pass}|\r";
        fwrite($socket, $msg);
        $card_valid_response = fgets($socket);
    } else {
        fclose($socket);    
        return 'SIP2 username or password is incorrect.';
    }

    fclose($socket);

    // SIP2 response starts with 24 if successful Patron Status response
    if (strpos($card_valid_response, '|BLY|') !== false) {
        // "|BLY|" in the response indicates that the card number matches an existing card number
        return null;
    }

    return 'Invalid response or bad card';
}

$result = [];
if (!session_id()) {
    session_start();
}

$library_number = $_SESSION['temp_library_number'] ?? '';
$card_number    = $_SESSION['temp_card_number'] ?? '';
unset($_SESSION['temp_library_number']);
unset($_SESSION['temp_card_number']);

$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_errno) {
    echo 'Cannot connect to database';
    exit;
}

$query = "select meta_key, meta_value from qpl_usermeta where user_id={$library_number} AND meta_key LIKE 'evergreen-sip2%'";
$result = $conn->query($query);

if (!$result) {
    echo 'No sip2 credentials for this library number';
    exit;
}

$sip2_credentials = [];
while ($row = $result->fetch_assoc()) {
    $sip2_credentials[$row['meta_key']] = $row['meta_value'];
}

$sip2_host = $sip2_credentials['evergreen-sip2-domain'] ?? '';
$sip2_user = $sip2_credentials['evergreen-sip2-username'] ?? '';
$sip2_pass = $sip2_credentials['evergreen-sip2-password'] ?? '';

$result = validate_sip2_card(
    $card_number,       // Card Number
    $sip2_host,         // SIP2 Host
    6001,               // Port
    $sip2_user,         // Login user (AO)
    $sip2_pass          // Terminal password (AC)
);

if ($result) {
    echo 'SIP2 info is incorrect or card number is invalid';
    exit;
}

$query = "DELETE FROM qpl_gs_library_cards WHERE library_number={$library_number} AND card_number={$card_number}";
$result = $conn->query($query);

$expired_at = time() + 86400;
$query = "INSERT INTO qpl_gs_library_cards (library_number, card_number, expired_at) VALUES ('{$library_number}', '{$card_number}', '{$expired_at}')";
$result = $conn->query($query);

mysqli_close($conn);

$_SESSION['library_number']     = $library_number;
$_SESSION['card_number']        = $card_number;

$redirect_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST']."/access/";
header("Location: {$redirect_url}");
exit;

?>
