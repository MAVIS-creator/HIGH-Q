<?php
// Simple SMTP STARTTLS cert check using PHP streams
$host = 'smtp.gmail.com';
$port = 587;
$cafile = 'C:\\xampp\\php\\extras\\ssl\\cacert.pem';

echo "Connecting to $host:$port using CA file $cafile\n";
$errno = 0; $errstr = '';
$socket = stream_socket_client("tcp://$host:$port", $errno, $errstr, 10);
if (!$socket) {
    echo "socket connect failed: $errno - $errstr\n";
    exit(1);
}
stream_set_timeout($socket, 5);
$line = fgets($socket);
echo "S: $line";
// send EHLO
fwrite($socket, "EHLO localhost\r\n");
while ($l = fgets($socket)) {
    echo "S: $l";
    if (preg_match('/^250[ \-]/', $l) === 0) break;
    if (trim($l) === '') break;
    // break when end of EHLO response
    if (strpos($l, 'STARTTLS') !== false) { $hasStartTLS = true; }
    if (substr($l,0,4) === '250 ') break;
}
if (empty($hasStartTLS)) {
    echo "Server did not advertise STARTTLS\n";
    fclose($socket);
    exit(1);
}
// request STARTTLS
fwrite($socket, "STARTTLS\r\n");
$resp = fgets($socket); echo "S: $resp";
if (strpos($resp, '220') !== 0) { echo "STARTTLS not accepted\n"; fclose($socket); exit(1); }

// enable crypto
$context = stream_context_create([
    'ssl' => [
        'capture_peer_cert' => true,
        'verify_peer' => true,
        'verify_peer_name' => true,
        'allow_self_signed' => false,
        'cafile' => $cafile,
    ]
]);
$ok = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
if ($ok === false) {
    echo "stream_socket_enable_crypto returned false\n";
    while ($err = openssl_error_string()) { echo "OpenSSL error: $err\n"; }
    fclose($socket);
    exit(1);
} elseif ($ok === 0) {
    echo "stream_socket_enable_crypto returned 0 (async)\n";
} else {
    echo "TLS enabled successfully\n";
}
// get peer cert
$cont = stream_context_get_params($socket);
if (isset($cont['options']['ssl']['peer_certificate'])) {
    $cert = $cont['options']['ssl']['peer_certificate'];
    $parsed = openssl_x509_parse($cert);
    echo "Cert subject: " . ($parsed['subject']['CN'] ?? 'n/a') . "\n";
    echo "Cert valid from: " . date('c', $parsed['validFrom_time_t']) . " to " . date('c', $parsed['validTo_time_t']) . "\n";
} else {
    echo "No peer_certificate available in context\n";
}

fwrite($socket, "QUIT\r\n");
fgets($socket);
fclose($socket);
echo "Done\n";
