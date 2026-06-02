<?php

// 1. Get the Subscription URL from the environment
$subUrl = getenv('VPN_SUB_URL');

if (!$subUrl) {
    die("❌ Error: VPN_SUB_URL environment variable is not set or empty.\n");
}

echo "⏳ Fetching subscription from: {$subUrl}\n";

$excludeTagsEnv = getenv('VPN_EXCLUDE_NODE_TAGS');
$excludeTags = $excludeTagsEnv ? array_filter(explode(',', $excludeTagsEnv)) : [];
if (!empty($excludeTags)) {
    echo "🚫 Excluded node tags: " . implode(', ', $excludeTags) . "\n";
}

// 2. Fetch the content (with a basic user-agent to avoid blocks)
$context = stream_context_create([
    'http' => [
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        'timeout' => 15
    ]
]);
$content = @file_get_contents($subUrl, false, $context);

if ($content === false) {
    $error = error_get_last();
    $errorMsg = $error ? $error['message'] : 'Unknown error';
    die("❌ Error: Failed to fetch the subscription URL.\nReason: {$errorMsg}\n");
}

// 3. Decode Base64 if necessary (some providers encode the whole list)
if (strpos($content, 'vless://') === false) {
    $decoded = base64_decode(trim($content));
    if ($decoded && strpos($decoded, 'vless://') !== false) {
        $content = $decoded;
        echo "🔓 Successfully decoded Base64 payload.\n";
    } else {
        die("❌ Error: No 'vless://' links found in the subscription content.\n");
    }
}

// 4. Extract all VLESS links
preg_match_all('/vless:\/\/[^\r\n]+/', $content, $matches);

if (empty($matches[0])) {
    die("❌ Error: Could not extract any 'vless://' links.\n");
}

$links = $matches[0];
echo "✅ Found " . count($links) . " VLESS links.\n";

// Shuffle links to randomize validation order (allows node rotation on re-run)
shuffle($links);

// 5. Find the first online VLESS link (TCP + TLS check)
$selectedLink = null;
$tag = 'vless-server';
$uuid = null;
$server = null;
$port = null;
$params = [];

foreach ($links as $index => $link) {
    $tempLink = $link;
    $tempTag = 'vless-server';
    if (strpos($tempLink, '#') !== false) {
        [$tempLink, $tempTag] = explode('#', $tempLink, 2);
        $tempTag = urldecode(trim($tempTag));
    }

    $skip = false;
    foreach ($excludeTags as $excludeTag) {
        if (stripos($tempTag, $excludeTag) !== false) {
            $skip = true;
            break;
        }
    }

    if ($skip) {
        continue;
    }

    if (strpos($tempLink, 'vless://') !== 0) {
        continue;
    }
    $urlInfo = substr($tempLink, 8);

    @[$tempUuid, $hostInfo] = explode('@', $urlInfo, 2);
    if (!$tempUuid || !$hostInfo) {
        continue;
    }

    @[$serverPort, $queryString] = explode('?', $hostInfo, 2);
    @[$tempServer, $tempPort] = explode(':', $serverPort, 2);

    if (!$tempServer || !$tempPort) {
        continue;
    }

    $tempPort = (int)$tempPort;

    $tempParams = [];
    parse_str((string)$queryString, $tempParams);

    $sni = isset($tempParams['sni']) ? $tempParams['sni'] : null;
    $isTls = isset($tempParams['security']) && in_array($tempParams['security'], ['tls', 'reality']);
    $typeStr = $isTls ? "TLS" : "TCP";

    if (isset($tempParams['type']) && $tempParams['type'] === 'xhttp') {
        echo "🔍 Skipping link " . ($index + 1) . "/" . count($links) . ": {$tempTag} (Unsupported transport: xhttp)\n";
        continue;
    }

    echo "🔍 Testing link " . ($index + 1) . "/" . count($links) . ": {$tempTag} ({$tempServer}:{$tempPort}) via {$typeStr}... ";
    flush();

    $online = false;
    if ($isTls && $sni) {
        $context = stream_context_create([
            'ssl' => [
                'peer_name' => $sni,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ]);
        // Perform standard TLS handshake check
        $fp = @stream_socket_client("ssl://{$tempServer}:{$tempPort}", $errno, $errstr, 2.5, STREAM_CLIENT_CONNECT, $context);
        if ($fp) {
            fclose($fp);
            $online = true;
        }
    } else {
        $fp = @fsockopen($tempServer, $tempPort, $errno, $errstr, 2.5);
        if ($fp) {
            fclose($fp);
            $online = true;
        }
    }

    if ($online) {
        echo "🟢 Online!\n";
        $selectedLink = $tempLink;
        $tag = $tempTag;
        $uuid = $tempUuid;
        $server = $tempServer;
        $port = $tempPort;
        $params = $tempParams;
        break;
    } else {
        echo "🔴 Offline\n";
    }
}

if (!$selectedLink) {
    die("❌ Error: All VLESS links are offline.\n");
}

echo "🎲 Selected node: {$tag}\n";

// Build the base configuration
$outbound = [
    'type' => 'vless',
    'tag' => $tag,
    'server' => $server,
    'server_port' => (int)$port,
    'uuid' => $uuid,
];

// Add flow parameter if it exists (usually used with xtls-rprx-vision)
if (!empty($params['flow'])) {
    $outbound['flow'] = $params['flow'];
}

// 6. Configure TLS and Reality settings
$tlsEnabled = isset($params['security']) && in_array($params['security'], ['tls', 'reality']);
if ($tlsEnabled) {
    $outbound['tls'] = [
        'enabled' => true,
        'server_name' => $params['sni'] ?? '',
        'utls' => [
            'enabled' => true,
            'fingerprint' => 'chrome',
        ],
    ];

    // If it specifically uses Reality, add the Reality block
    if ($params['security'] === 'reality') {
        $outbound['tls']['reality'] = [
            'enabled' => true,
            'public_key' => $params['pbk'] ?? '',
            'short_id' => $params['sid'] ?? '',
        ];
    }
}

// 7. Configure Transport (grpc, ws, xhttp)
$transportType = $params['type'] ?? 'tcp';
if ($transportType !== 'tcp' && $transportType !== 'none') {
    $transport = [
        'type' => $transportType,
    ];

    if (!empty($params['path'])) {
        $transport['path'] = urldecode($params['path']);
    }
    if (!empty($params['serviceName'])) {
        $transport['service_name'] = urldecode($params['serviceName']);
    }
    if (!empty($params['host'])) {
        $transport['headers'] = ['Host' => urldecode($params['host'])];
    }

    $outbound['transport'] = $transport;
}

// Load template and inject the new outbound
$templateStr = file_get_contents(__DIR__ . '/proxy-config.example.json');
if (!$templateStr) {
    die("❌ Error: Could not read proxy-config.example.json template.\n");
}
$config = json_decode($templateStr, true);

foreach ($config['outbounds'] as $key => $out) {
    if (isset($out['tag']) && $out['tag'] === 'vless-server') {
        $outbound['tag'] = 'vless-server'; 
        $config['outbounds'][$key] = $outbound;
        break;
    }
}

// 8. Save the result to JSON
file_put_contents(
    __DIR__ . '/proxy-config.json',
    json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
);

echo "✅ proxy-config.json successfully generated!\n";

// 9. Restart glueless and dependent containers via Docker socket
$containersToRestart = ['glueless'];
$gluelessId = null;

$fp = @stream_socket_client('unix:///var/run/docker.sock', $errno, $errstr);
if ($fp) {
    fwrite($fp, "GET /containers/json HTTP/1.0\r\nHost: localhost\r\n\r\n");
    $response = '';
    while (!feof($fp)) {
        $response .= fgets($fp, 4096);
    }
    fclose($fp);
    
    if (strpos($response, "\r\n\r\n") !== false) {
        list($headers, $body) = explode("\r\n\r\n", $response, 2);
        $containers = json_decode($body, true);
        if (is_array($containers)) {
            foreach ($containers as $c) {
                if (!empty($c['Names']) && in_array('/glueless', $c['Names'])) {
                    $gluelessId = $c['Id'];
                    break;
                }
            }
            
            foreach ($containers as $c) {
                if (isset($c['HostConfig']['NetworkMode'])) {
                    $mode = $c['HostConfig']['NetworkMode'];
                    if ($mode === 'container:glueless' || ($gluelessId && $mode === 'container:' . $gluelessId)) {
                        $name = ltrim($c['Names'][0], '/');
                        if (!in_array($name, $containersToRestart)) {
                            $containersToRestart[] = $name;
                        }
                    }
                }
            }
        }
    }
}

foreach ($containersToRestart as $container) {
    echo "🔄 Restarting $container via Docker socket...\n";
    $fp = @stream_socket_client('unix:///var/run/docker.sock', $errno, $errstr);
    if ($fp) {
        fwrite($fp, "POST /containers/$container/restart?t=5 HTTP/1.0\r\nHost: localhost\r\n\r\n");
        while (!feof($fp)) {
            fgets($fp, 128);
        }
        fclose($fp);
        echo "✅ $container restarted successfully!\n";
        
        if ($container === 'glueless') {
            sleep(3);
        }
    } else {
        echo "⚠️ Warning: Failed to restart $container via socket. Error: $errstr\n";
    }
}
