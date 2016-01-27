<?php
/**
 * Created by PhpStorm.
 * User: yidashi
 * Date: 15/12/25
 * Time: 下午8:50.
 */
namespace frontend\controllers;

use common\models\ArticleData;
use frontend\models\Article;
use yii\data\Pagination;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;

class MyController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }
    public function actions()
    {
        return [
            'upload' => [
                'class' => 'kucha\ueditor\UEditorAction',
                'config' => [
                    'imageUrlPrefix' => \Yii::getAlias('@static').'/', //图片访问路径前缀
                    'imagePathFormat' => 'upload/image/{yyyy}{mm}{dd}/{time}{rand:6}', //上传保存路径
                ],
            ],
            'webupload' => 'yidashi\webuploader\WebuploaderAction',
        ];
    }
    public function actionArticleList()
    {
        $userId = \Yii::$app->user->id;
        $query = Article::find()->where(['user_id' => $userId]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count()]);
        $models = $query->offset($pages->offset)
            ->orderBy('id desc')
            ->limit($pages->limit)
            ->all();

        return $this->render('article-list', [
            'models' => $models,
            'pages' => $pages,
        ]);
    }
    public function actionCreateArticle()
    {
        $model = new Article();
        $dataModel = new ArticleData();
        if ($model->load(\Yii::$app->request->post()) && $dataModel->load(\Yii::$app->request->post())) {
            $isValid = $model->validate();
            if ($isValid) {
                $model->save(false);
                $dataModel->id = $model->id;
                $isValid = $dataModel->validate();
                if ($isValid) {
                    $dataModel->save(false);
                    \Yii::$app->session->setFlash('success', '投稿成功，请等待管理员审核！');

                    return $this->redirect(['create-article']);
                }
            }
        }

        return $this->render('create-article', [
            'model' => $model,
            'dataModel' => $dataModel,
        ]);
    }
    public function actionUpdateArticle($id)
    {
        $userId = \Yii::$app->user->id;
        $model = Article::find()->where(['id' => $id, 'user_id' => $userId])->one();
        $dataModel = ArticleData::find()->where(['id' => $id])->one();
        if (!isset($model, $dataModel)) {
            throw new NotFoundHttpException('文章不存在!');
        }
        if ($model->load(\Yii::$app->request->post()) && $dataModel->load(\Yii::$app->request->post())) {
            $isValid = $model->validate();
            $isValid = $dataModel->validate() && $isValid;
            if ($isValid) {
                $model->save(false);
                $dataModel->save(false);
                \Yii::$app->session->setFlash('success', '修改成功，请等待管理员审核！');

                return $this->redirect(['update-article', 'id' => $id]);
            }
        }

        return $this->render('update-article', [
            'model' => $model,
            'dataModel' => $dataModel,
        ]);
    }
}
