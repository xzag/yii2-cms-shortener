<?php
namespace app\modules\shortener\controllers;

use app\models\Article;
use app\models\Setting;

class TwitterController extends ShortenerController
{
    public function validateTokenData($data)
    {
        return $data === array_filter($data);
    }

    public function apiShorten($link)
    {
        $tokData = [
            'token' => Setting::find()->select('value')->where(['key' => 'social.twitter.token'])->scalar(),
            'token_secret' => Setting::find()->select('value')->where(['key' => 'social.twitter.token_secret'])->scalar(),
            'consumer_key' => Setting::find()->select('value')->where(['key' => 'social.twitter.consumer_key'])->scalar(),
            'consumer_secret' => Setting::find()->select('value')->where(['key' => 'social.twitter.consumer_secret'])->scalar(),
            'screen_name' => Setting::find()->select('value')->where(['key' => 'social.twitter.screen_name'])->scalar()
        ];

        if (!$this->validateTokenData($tokData)) {
            return false;
        }

        $token = new \yii\authclient\OAuthToken([
            'token' => $tokData['token'],
            'tokenSecret' => $tokData['token_secret']
        ]);

        $client = new \yii\authclient\clients\Twitter(
            [
                'accessToken' => $token,
                'consumerKey' => $tokData['consumer_key'],
                'consumerSecret' => $tokData['consumer_secret']
            ]
        );

        $text = $link->url;

        try {
            $result = $client->api('direct_messages/new.json', 'POST', ['text' => $text, 'screen_name' => $tokData['screen_name']]);
            if (isset($result['id_str'], $result['entities']['urls'][0]['url'])) {
                $link->shortened_url = $result['entities']['urls'][0]['url'];
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}
