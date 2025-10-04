<?php
$caf='C:/xampp/php/extras/ssl/cacert.pem';
$s=stream_context_create(['ssl'=>['capture_peer_cert'=>true,'verify_peer'=>true,'verify_peer_name'=>true,'cafile'=>$caf,'peer_name'=>'smtp.gmail.com']]);
$fp=@stream_socket_client('tcp://smtp.gmail.com:587', $e,$es,10,STREAM_CLIENT_CONNECT,$s);
if(!$fp){ echo 'connect failed: '.json_encode(error_get_last())."\n"; exit; }
fwrite($fp,"EHLO test\r\n");
while($l=fgets($fp)){ if(trim($l)==='') break; }
fwrite($fp,"STARTTLS\r\n");
$r=fgets($fp); echo "STARTTLS RESP: $r\n";
$ok=stream_socket_enable_crypto($fp,true,STREAM_CRYPTO_METHOD_TLS_CLIENT);
echo "enable_crypto: ".var_export($ok,true)."\n";
$params=stream_context_get_params($fp);
var_dump(array_keys($params));
if(isset($params['options']['ssl']['peer_certificate'])){
    $cert=$params['options']['ssl']['peer_certificate'];
    $p=openssl_x509_parse($cert);
    echo 'CN: '.($p['subject']['CN']??'') ."\n";
} else { echo 'no cert in params\n'; }
fclose($fp);
