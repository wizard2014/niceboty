<?php

use Codebird\Codebird;

require 'vendor/autoload.php';

require 'emojis.php';
$config = require 'config.php';

$db = new PDO('mysql:host=localhost;dbname=' . $config['db']['dbname'], $config['db']['username'], $config['db']['password']);

$ml = new MonkeyLearn\Client($config['monkeylearn']['token']);

Codebird::setConsumerKey($config['tweeter']['consumer_key']['key'], $config['tweeter']['consumer_key']['secret']);

$cb = Codebird::getInstance();
$cb->setReturnFormat(CODEBIRD_RETURNFORMAT_ARRAY);

$cb->setToken($config['tweeter']['access_token']['token'], $config['tweeter']['access_token']['secret']);

$lastId = $db->query("SELECT * FROM tracking ORDER BY twitter_id DESC LIMIT 1")
             ->fetch(PDO::FETCH_OBJ);

$mentions = $cb->statuses_mentionsTimeline($lastId ? 'since_id=' . $lastId->twitter_id : '');

if (!isset($mentions[0])) {
    return;
}

$tweets = [];

foreach ($tweets as $i => $mention) {
    if (isset($mention['id'])) {
        $tweets[] = [
            'id'                => $mention['id'],
            'user_screen_name'  => $mention['user']['screen_name'],
            'text'              => $mention['text'],
        ];
    }
}

$tweetsText = array_map(function ($tweet) {
    return $tweet['text'];
}, $tweets);

$analysis = $ml->classifiers->classify('cl_qkjxv9Ly', $tweetsText, true);

foreach ($tweets as $i => $tweet) {
    switch (strtolower($analysis->result[$i][0]['label'])) {
        case 'positive':
            $emojiSet = $positiveEmojis;
            break;
        case 'neutral':
            $emojiSet = $neutralEmojis;
            break;
        case 'negative':
            $emojiSet = $negativeEmojis;
            break;
    }

    $cb->statuses_update([
        'status' => '@' . $tweet['user_screen_name'] . ' ' . html_entity_decode($emojiSet[rand(0, count($emojiSet) - 1)], 0, 'UTF-8'),
        'in_reply_to_status_id' => $tweet['id'],
    ]);

    $track = $db->prepare("INSERT INTO tracking (twiiter_id) VALUES (:twiiter_id)");
    $track->execute([
        'twitterId' => $tweet['id'],
    ]);
}
