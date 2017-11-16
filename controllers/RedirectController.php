<?php
namespace app\modules\shortener\controllers;

use yii\web\Controller;
use yii\helpers\HtmlPurifier;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use app\modules\shortener\models\Link;
use app\modules\shortener\models\ArticleLink;
use app\models\Article;
use app\modules\parsers\models\ArticleQueue;

class RedirectController extends Controller
{
    
    public function actionGo($id, $hash)
    {
        $model = $this->findModel($id, $hash);
        
        // maybe we parsed this url so we should redirect locally
        if ($q = ArticleQueue::find()->where(['url_hash' => $hash, 'status' => ArticleQueue::STATUS_PROCESSED])->one()) {
            if ($article = Article::find()->where(['id' => $q->article_id, 'status' => Article::STATUS_PUBLISHED])->one()) {
                return $this->redirect(Url::to(['/article/view', 'id' => $article->id, 'slug' => $article->slug]));
            }
        }
        
        return $this->redirect($model->shortened_url ? $model->shortened_url : $model->url);
    }

    protected function findModel($id, $hash)
    {
        if (($model = Link::find()->where(['id' => $id, 'url_hash' => $hash])->one()) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
