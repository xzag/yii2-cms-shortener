<?php
namespace app\modules\shortener\controllers;

use yii\console\Controller;
use yii\helpers\HtmlPurifier;
use yii\helpers\Url;
use app\modules\shortener\models\Link;
use app\modules\shortener\models\ArticleLink;
use app\models\Article;
use app\modules\parsers\models\ArticleQueue;

abstract class ShortenerController extends Controller
{
    public function actionShorten($model)
    {
        $dom = new \DOMDocument('1.0', 'utf-8');
        @$dom->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $model->content);
        $xpath = new \DOMXPath($dom);

        $contentModified = false;

        $hrefs = ($nodes = $xpath->query('//a')) && $nodes->length ? $nodes : null;
        if (empty($hrefs)) {
            return [];
        }

        for ($i = 0; $i < $hrefs->length; $i++) {
            $a_node = $hrefs->item($i);
            $href = $a_node->getAttribute('href');

            if ($href && $this->shouldReplace($href)) {
                $contentModified = true;
                $a_node->removeAttribute('href');

                $replacement = $this->getReplacement($model, $href);
                $host = parse_url($replacement, PHP_URL_HOST);
                $path = parse_url($replacement, PHP_URL_PATH);
                $a_node->setAttribute('href', $replacement);

                $a_node->removeAttribute('target');
                if (($host != \Yii::$app->params['hostname']) || (mb_strpos($path, '/external/') === 0)) {
                    $a_node->setAttribute('target', "_blank");
                }
            }
        }

        if ($contentModified) {
            $model->content = $dom->saveHTML($dom);
        }

        unset($xpath, $dom);
    }

    protected function shouldReplace($href)
    {
        $host = parse_url($href, PHP_URL_HOST);
        if (!$host) {
            return false;
        }

        $nonReplacableList = [\Yii::$app->params['hostname'], 'twitter', 'facebook', 'youtube.com', 'instagram.com'];

        foreach ($nonReplacableList as $leaveThisHostAlone) {
            if (mb_stripos($host, $leaveThisHostAlone) !== false) {
                return false;
            }
        }

        return true;
    }

    protected function getReplacement($article, $href)
    {
        $hash = Link::hash($href);
        if ($q = ArticleQueue::find()->where(['url_hash' => $hash, 'status' => ArticleQueue::STATUS_PROCESSED])->one()) {
            if ($article = Article::find()->where(['id' => $q->article_id, 'status' => Article::STATUS_PUBLISHED])->one()) {
                return Url::to(['/article/view', 'id' => $article->id, 'slug' => $article->slug], true);
            }
        }

        $link = Link::find()->where(['url_hash' => $hash])->one();
        if (!$link) {
            $link = new Link();
            $link->domain = parse_url($href, PHP_URL_HOST);
            $link->url = $href;
            $link->save();

            if ($link && !$al = ArticleLink::find()->where(['article_id' => $article->id, 'link_id' => $link->id])->one()) {
                $al = new ArticleLink();
                $al->article_id = $article->id;
                $al->link_id = $link->id;
                $al->save();
            }
        }

        return Url::to(['redirect/go', 'id' => $link->id, 'hash' => $link->url_hash], true);
    }

    public function actionProcess()
    {
        $queue = new Link();
        if (!$queue->capture()) {
            return;
        }

        $pid = $queue->getPid();

        while ($queueItems = $queue->getCaptured()) {
            foreach ($queueItems as $item) {
                if ($this->apiShorten($item)) {
                    $item->processed_at = time();
                    $item->status = Link::STATUS_PROCESSED;
                } else {
                    $item->status = Link::STATUS_FAILED;
                }

                $item->save();
                break;
            }
            break;
        }
    }
}
