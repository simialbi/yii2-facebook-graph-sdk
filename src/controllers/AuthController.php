<?php
/**
 * @package yii2-facebook-graph-sdk
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\facebook\controllers;

use yii\helpers\Url;
use yii\web\Controller;
use yii\web\ServerErrorHttpException;
use Yii;

/**
 * Class AuthController
 *
 * @property-read \simialbi\yii2\facebook\Module $module
 */
class AuthController extends Controller
{
    /**
     * Redirect action
     *
     * @return \yii\web\Response
     */
    public function actionRedirect()
    {
        Url::remember(Yii::$app->request->referrer);

        return $this->redirect($this->module->client->authUrl);
    }

    /**
     * Oauth callback action
     *
     * @param string $code
     * @param string $state
     * @return \yii\web\Response
     * @throws ServerErrorHttpException
     */
    public function actionOauthCallback($code = null, $state = null)
    {
        try {
            $this->module->client->setAuthToken($code);
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            throw new ServerErrorHttpException($e->getMessage(), $e->getCode(), $e);
        }
        return $this->goBack();
    }
}