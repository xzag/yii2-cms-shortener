<?php
namespace app\modules\shortener;

use yii\base\Event;
use yii\db\ActiveRecord;
use app\models\Article;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\shortener\controllers';

    public function init()
    {
        parent::init();

        $events = [Article::EVENT_AFTER_PROCESSING];
        foreach ($events as $eventName) {
            Event::on(\app\models\Article::className(), $eventName, function ($event) {
                $model = $event->sender;
                $this->runAction('twitter/shorten', [$model]);
                $model->bypassEvents = true;
                $model->save();
            });
        }
    }
}
