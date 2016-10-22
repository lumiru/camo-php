<?php

define('SERVER_KEY', '24FEEDFACEDEADBEEFCAFE');

function get_camo_requested_url($key)
{
	$requestedUrl = null;

	if(isset($_SERVER['PATH_INFO']) || !empty($_SERVER['REDIRECT_URL'])) {
		$path = explode('/', isset($_SERVER['PATH_INFO']) ?
			$_SERVER['PATH_INFO'] : $_SERVER['REDIRECT_URL']);

		$hash = $path[1];

		if(!empty($_GET['url'])) {
			$url = $_GET['url'];
		}
		elseif(!empty($path[2])) {
			$url = hex2bin($path[2]);
		}

		if(!empty($hash) && !empty($url)) {
			$check = hash_hmac('sha1', $url, $key);
			if($hash === $check) {
				$requestedUrl = $url;
			}
		}
	}

	return $requestedUrl;
}

function get_request_headers(array $names)
{
	$headers = array();

	foreach ($names as $name) {
		$serverCode = strtoupper($name);
		$serverCode = str_replace('-', '_', $serverCode);

		if(!empty($_SERVER['HTTP_' . $serverCode])) {
			$headers[$name] = $_SERVER['HTTP_' . $serverCode];
		}
	}

	return $headers;
}

function open_http_stream($url, array $headers = null)
{
	$stream = null;

	if(is_null($headers)) {
		$headers = array();
	}

	$sentHeaders = array('http' => array('header' =>
		headers_key_to_value($headers)
	));
	$context = stream_context_create($sentHeaders);
	$stream = fopen($url, 'r', false, $context);

	return $stream;
}

function headers_key_to_value(array $headers)
{
	return array_map(
		function($k, $v) { return $k.': '.$v; },
		array_keys($headers), $headers
	);
}

function get_http_stream_headers($stream)
{
	$headers = array();
	$metadata = stream_get_meta_data($stream);

	$status = $metadata['wrapper_data'][0];
	$statusSplitted = explode(' ', $status);
	$statusCode = intval($statusSplitted[1]);
	$headers['Status'] = $status;
	$headers['Status-Code'] = $statusCode;

	foreach ($metadata['wrapper_data'] as $line) {
		$row = explode(': ', $line);
		if(count($row) > 1) {
			$headers[array_shift($row)] = implode(': ', $row);
		}
	}

	return $headers;
}

function pass_headers(array $headers, array $filters = null)
{
	if (!is_null($filters)) {
		$headers = array_filter($headers, function($k) use ($filters) {
			return in_array($k, $filters);
		}, ARRAY_FILTER_USE_KEY);
	}

	$sentHeaders = headers_key_to_value($headers);

	foreach ($sentHeaders as $header) {
		header($header);
	}
}

$url = get_camo_requested_url(SERVER_KEY);

if(!empty($url)) {
	$sentHeaders = array(
		'Accept' => 'image/*',
		'Connection' => 'close',
		'X-Frame-Options' => 'deny',
		'X-XSS-Protection' => '1; mode=block',
		'X-Content-Type-Options' => 'nosniff',
		'Content-Security-Policy' => 'default-src \'none\'; img-src data:; style-src \'unsafe-inline\'',
		'Accept-Encoding' => $_SERVER['HTTP_ACCEPT_ENCODING']
	);
	$receivedHeaders = get_request_headers(array(
		'Accept-Encoding', 'If-Modified-Since', 'If-None-Match'
	));

	$sentHeaders = array_merge($sentHeaders, $receivedHeaders);

	$stream = open_http_stream($url, $sentHeaders);
	$headers = get_http_stream_headers($stream);

	if($headers['Status-Code'] === 200) {
		if(strpos($headers['Content-Type'], 'image/') === 0) {
			$headers['Cache-Control'] = 'public, max-age=31536000';
			pass_headers($headers, array(
				'Content-Type',
				'Cache-Control',
				'ETag',
				'Expires',
				'Last-Modified',
				'Content-Encoding'
			));
			fpassthru($stream);
		}
		else {
			header('HTTP/1.1 403 Forbidden');
			readfile('403.html');
		}
	}
	else {
		header($headers['Status']);
	}

	fclose($stream);
}
else {
	header('HTTP/1.1 404 Not Found');
	echo 'Error 404 - Not found';
	// readfile('404.html');
}

