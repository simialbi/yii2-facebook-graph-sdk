<?php
/**
 * @package yii2-facebook-graph-sdk
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\facebook\components;


use Facebook\Facebook;
use Yii;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\web\Response;

/**
 * Facebook API Client component
 *
 * ```php
 * [
 *     'client' => [
 *         'credentials' => [
 *             'app_id' => '{app_id}',
 *             'app_secret' => '{app-secret}',
 *             // 'default_graph_version' => 'v2.10'
 *         ]
 *     ]
 * ]
 * ```
 *
 * @property string $authToken
 *
 * @property-read Facebook $api
 * @property-read string $authUrl
 */
class FacebookApiClient extends Component
{
    /**
     * @var string|array the configuration json
     */
    public $credentials;

    /**
     * @var array A numeric array of permissions to ask the user for.
     */
    public $scopes = [];

    /**
     * @var string The OAuth 2.0 Redirect URI
     */
    public $redirectUri;

    /**
     * @var Facebook The Google API Client
     */
    private $client;

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        if (!isset($this->credentials)) {
            throw new InvalidConfigException(Yii::t(
                'simialbi/facebook/notifications',
                'The "credentials" param is mandatory')
            );
        }
        if (is_string($this->credentials)) {
            try {
                $this->credentials = Json::decode($this->credentials);
            } catch (InvalidArgumentException $e) {
                throw new InvalidConfigException(Yii::t(
                    'simialbi/facebook/notifications',
                    'The client component credentials are invalid'
                ), 0, $e);
            }
        }
        if (!is_array($this->credentials) || !isset($this->credentials['app_id']) || !isset($this->credentials['app_secret'])) {
            throw new InvalidConfigException(Yii::t(
                'simialbi/facebook/notifications',
                'The client component credentials are invalid'
            ));
        }
        if (!isset($this->redirectUri)) {
            throw new InvalidConfigException(Yii::t(
                'simialbi/facebook/notifications',
                'The "redirect uri" param is mandatory')
            );
        }
    }

    /**
     * Configures Facebook API Client component and returns it
     *
     * @return Facebook
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    public function getApi()
    {
        if (null !== $this->client) {
            return $this->client;
        }

        $this->client = new Facebook($this->credentials);

        if (Yii::$app->session && Yii::$app->session->has('facebookApiToken')) {
            $this->client->setDefaultAccessToken(Yii::$app->session->get('facebookApiToken'));
        }

        return $this->client;
    }

    /**
     * Returns authentication url
     *
     * @return string
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    public function getAuthUrl()
    {
        $helper = $this->getApi()->getRedirectLoginHelper();
        return $helper->getLoginUrl($this->redirectUri, $this->scopes);
    }

    /**
     * Access token getter
     *
     * @return \Facebook\Authentication\AccessToken|null access token
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    public function getAuthToken()
    {
        return $this->getApi()->getDefaultAccessToken();
    }

    /**
     * Access token setter
     *
     * @param string $code string code from accounts.google.com
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    public function setAuthToken($code)
    {
        $helper = $this->getApi()->getOAuth2Client();
        $token = $helper->getAccessTokenFromCode($code, $this->redirectUri);

        if (Yii::$app->session) {
            Yii::$app->session->set('facebookApiToken', (string)$token);
        }
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