<?php

namespace app\modules\shortener\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\AttributeBehavior;
use app\models\Queue;

class Link extends Queue
{
    public static function tableName()
    {
        return '{{%link}}';
    }


    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['domain', 'url', 'url_hash'], 'string', 'max' => 255]
            ]
        );
    }

    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_VALIDATE => 'url_hash',
                ],
                'value' => function ($event) {
                    $model = $event->sender;
                    return self::hash($model->url);
                },
            ]
        ]);
    }

    public static function hash($url)
    {
        return md5($url);
    }
}
