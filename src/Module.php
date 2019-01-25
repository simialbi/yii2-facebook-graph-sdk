<?php
/**
 * @package yii2-facebook-graph-sdk
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\facebook;

use yii\base\InvalidConfigException;
use Yii;

/**
 * Class Module
 * @package simialbi\yii2\facebook
 *
 * @property-read \simialbi\yii2\facebook\components\FacebookApiClient $client
 */
class Module extends \simialbi\yii2\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'simialbi\yii2\facebook\controllers';

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        if (!$this->has('client')) {
            throw new InvalidConfigException(Yii::t('simialbi/facebook/notifications', 'The "client" component is mandatory'));
        }

        $this->registerTranslations();
    }

    /**
     * Returns the google api client component.
     * @return \simialbi\yii2\facebook\components\FacebookApiClient
     * @throws InvalidConfigException
     */
    public function getClient()
    {
        $client = $this->get('client');
        /* @var \simialbi\yii2\facebook\components\FacebookApiClient $client */

        return $client;
    }
}