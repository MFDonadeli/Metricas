<?php
require_once __DIR__ . '/vendor/autoload.php';

use Facebook\Facebook;
use Facebook\FacebookApp;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\FacebookRequest;

$fb = new Facebook([
  'app_id' => '241324603009226',
  'app_secret' => '8ef0601083d6dec4bb7cc3e71f33556d',
  'default_graph_version' => 'v2.8',
  ]);

try {
  // Requires the "read_stream" permission
  $response = $fb->get('/me/feed?fields=id,message&amp;limit=5');
} catch(Facebook\Exceptions\FacebookResponseException $e) {
  // When Graph returns an error
  echo 'Graph returned an error: ' . $e->getMessage();
  exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
  // When validation fails or other local issues
  echo 'Facebook SDK returned an error: ' . $e->getMessage();
  exit;
}

// Page 1
$feedEdge = $response->getGraphEdge();

foreach ($feedEdge as $status) {
  var_dump($status->asArray());
}

// Page 2 (next 5 results)
$nextFeed = $fb->next($feedEdge);

foreach ($nextFeed as $status) {
  var_dump($status->asArray());
}



?>