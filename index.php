<?php

use Codebird\Codebird;

require 'vendor/autoload.php';
$config = require 'config.php';

Codebird::setConsumerKey($config['tweeter']['consumer_key']['key'], $config['tweeter']['consumer_key']['secret']);

$cb = Codebird::getInstance();
$cb->setReturnFormat(CODEBIRD_RETURNFORMAT_ARRAY);

$cb->setToken($config['tweeter']['access_token']['token'], $config['tweeter']['access_token']['secret']);

$mentions = $cb->statuses_mentionsTimeline();

if (!isset($mentions[0])) {
    return;
}

$tweets = [];

foreach ($tweets as $i => $mention) {
    if (isset($mention['id'])) {
        $tweets[] = [
            'id' => $mention['id'],
            'user_screen_name' => $mention['user']['screen_name'],
            'text' => $mention['text'],
        ];
    }
}

$tweetsText = array_map(function ($tweet) {
    return $tweet['text'];
}, $tweets);
