<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Test-only loopback server. Captures one message and never forwards it.
$server = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
if (!$server) exit(1);
echo stream_socket_get_name($server, false), "\n";
flush();
$client = stream_socket_accept($server, 20);
if (!$client) exit(1);
stream_set_timeout($client, 20);
fwrite($client, "220 localhost SMTP test\r\n");
$message = '';
$receiving = false;
while (($line = fgets($client)) !== false) {
    if ($receiving) {
        if ($line === ".\r\n") { $receiving = false; fwrite($client, "250 Accepted\r\n"); }
        else $message .= $line;
    } elseif (str_starts_with($line, 'EHLO') || str_starts_with($line, 'HELO')) {
        fwrite($client, "250-localhost\r\n250 SIZE 1000000\r\n");
    } elseif (str_starts_with($line, 'DATA')) {
        $receiving = true;
        fwrite($client, "354 Send message\r\n");
    } elseif (str_starts_with($line, 'QUIT')) {
        fwrite($client, "221 Bye\r\n");
        break;
    } else {
        fwrite($client, "250 OK\r\n");
    }
}
fclose($client);
fclose($server);
echo json_encode(['message' => $message]), "\n";
