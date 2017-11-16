<?php

namespace app\modules\shortener\models;

use Yii;
use yii\db\ActiveRecord;

class ArticleLink extends ActiveRecord
{
    public static function tableName()
    {
        return 'article_link';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['article_id', 'link_id'], 'required'],
            [['article_id', 'link_id'], 'integer'],
        ];
    }
}
