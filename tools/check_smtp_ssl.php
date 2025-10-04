<?php
$host = 'smtp.gmail.com';
$port = 465;
$cafile = 'C:\\xampp\\php\\extras\\ssl\\cacert.pem';
$target = "ssl://$host:$port";

echo "Connecting to $target with cafile $cafile\n";
$context = stream_context_create([
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
        'allow_self_signed' => false,
        'cafile' => $cafile,
        'peer_name' => $host,
    ]
]);
$errno = 0; $errstr = '';
$fp = stream_socket_client($target, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
if (!$fp) {
    echo "CONNECT FAILED: $errno - $errstr\n";
    while ($err = openssl_error_string()) { echo "OpenSSL: $err\n"; }
    exit(1);
}
stream_set_timeout($fp, 5);
$line = fgets($fp); echo "S: $line";
fwrite($fp, "EHLO localhost\r\n");
while ($l = fgets($fp)) {
    echo "S: $l";
    if (strpos($l, '250 ') === 0) break;
}
$params = stream_context_get_params($fp);
if (isset($params['options']['ssl']['peer_certificate'])) {
    $cert = $params['options']['ssl']['peer_certificate'];
    $parsed = openssl_x509_parse($cert);
    echo "Cert CN: " . ($parsed['subject']['CN'] ?? 'n/a') . "\n";
    echo "Issuer: " . ($parsed['issuer']['O'] ?? ($parsed['issuer']['CN'] ?? 'n/a')) . "\n";
    echo "Valid from: " . date('c', $parsed['validFrom_time_t']) . " to " . date('c', $parsed['validTo_time_t']) . "\n";
} else {
    echo "No peer_certificate in context\n";
}
fwrite($fp, "QUIT\r\n");
fgets($fp);
fclose($fp);
echo "Done\n";
