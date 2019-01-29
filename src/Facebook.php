<?php
/**
 * @package yii2-facebook-graph-sdk
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\facebook;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Response;

/**
 * Facebook API Client component
 *
 * ```php
 * [
 *     'client' => [
 *             'appId' => '{app_id}',
 *             'appSecret' => '{app-secret}',
 *             // 'defaultGraphVersion' => 'v2.10'
 *     ]
 * ]
 * ```
 *
 * @property string $authToken
 *
 * @property-read \Facebook\Facebook $api
 * @property-read \FacebookAds\Api $adsApi
 * @property-read string $loginUrl
 * @property-write string $appId
 * @property-write string $appSecret
 * @property-write string $defaultGraphVersion
 */
class Facebook extends Component
{
    /**
     * @var \Facebook\Facebook The Facebook API client
     */
    private $_client;

    /**
     * @var \FacebookAds\Api The Facebook ads API client
     */
    private $_adsClient;

    /**
     * @var string Application Id
     */
    private $_appId;

    /**
     * @var string Application secret
     */
    private $_appSecret;

    /**
     * @var string Default Graph SDK version
     */
    private $_defaultGraphVersion = 'v2.10';

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        if (!isset($this->_appId)) {
            throw new InvalidConfigException(Yii::t(
                'simialbi/facebook/notifications',
                'The "{param}" param is mandatory',
                [
                    'param' => 'app id'
                ]
            ));
        }
        if (!isset($this->_appSecret)) {
            throw new InvalidConfigException(Yii::t(
                'simialbi/facebook/notifications',
                'The "{param}" param is mandatory',
                [
                    'param' => 'app secret'
                ]
            ));
        }
    }

    /**
     * Configures Facebook API Client component and returns it
     *
     * @return \Facebook\Facebook
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    public function getApi()
    {
        if (null !== $this->_client) {
            return $this->_client;
        }

        $this->_client = new \Facebook\Facebook([
            'app_id' => $this->_appId,
            'app_secret' => $this->_appSecret,
            'default_graph_version' => $this->_defaultGraphVersion
        ]);

        if (Yii::$app->session && Yii::$app->session->has('facebookApiToken')) {
            $this->_client->setDefaultAccessToken(Yii::$app->session->get('facebookApiToken'));
        }

        return $this->_client;
    }

    /**
     * Configures Facebook Ads API Client component and returns it
     *
     * @return \FacebookAds\Api
     *
     * @throws InvalidConfigException
     */
    public function getAdsApi()
    {
        if (!class_exists('\FacebookAds\Api')) {
            throw new InvalidConfigException(
                'The package "facebook/php-business-sdk" must be installed to use ads api'
            );
        }

        if (null !== $this->_adsClient) {
            return $this->_adsClient;
        }

        $this->_adsClient = \FacebookAds\Api::init(
            $this->_appId,
            $this->_appSecret,
            Yii::$app->session->get('facebookApiToken')
        );

        return $this->_adsClient;
    }

    /**
     * Returns authentication url
     *
     * @param array $options Set redirect uri and scopes
     *
     * @return string
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    public function getLoginUrl($options = [])
    {
        $helper = $this->getApi()->getRedirectLoginHelper();

        $scheme = Yii::$app->request->isSecureConnection ? 'https' : 'http';
        $redirectUri = ArrayHelper::remove($options, 'redirect_uri', Url::current([], $scheme));

        return $helper->getLoginUrl($redirectUri, $options);
    }

    /**
     * Access token getter
     *
     * @return \Facebook\Authentication\AccessToken|null access token
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    public function getAccessToken()
    {
        $helper = $this->getApi()->getRedirectLoginHelper();
        return $helper->getAccessToken();
    }

    /**
     * Access token setter
     *
     * @param string $token string code from accounts.google.com
     */
    public function setAccessToken($token)
    {
        $this->api->setDefaultAccessToken($token);

        if (Yii::$app->session) {
            Yii::$app->session->set('facebookApiToken', (string)$token);
        }
    }

    /**
     * @param string $appId Application id
     */
    public function setAppId($appId)
    {
        $this->_appId = $appId;
    }

    /**
     * @param string $appSecret Application secret
     */
    public function setAppSecret($appSecret)
    {
        $this->_appSecret = $appSecret;
    }

    /**
     * @param string $defaultGraphVersion Application secret
     */
    public function setDefaultGraphVersion($defaultGraphVersion)
    {
        $this->_defaultGraphVersion = $defaultGraphVersion;
    }

    /**
     * Send facebook request and return yii response
     *
     * @param string $method
     * @param string $endpoint
     * @param array $params
     * @param \Facebook\Authentication\AccessToken|string|null $accessToken
     * @param string|null $eTag
     * @param string|null $graphVersion
     *
     * @return Response
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    public function request($method, $endpoint, $params = [], $accessToken = null, $eTag = null, $graphVersion = null)
    {
        $response = $this->getApi()->sendRequest($method, $endpoint, $params, $accessToken, $eTag, $graphVersion);

        $yiiResponse = new Response([
            'format' => Response::FORMAT_JSON,
            'data' => $response->getDecodedBody(),
            'content' => $response->getBody(),
            'statusCode' => $response->getHttpStatusCode()
        ]);
        $yiiResponse->headers->fromArray($response->getHeaders());

        return $yiiResponse;
    }
}
