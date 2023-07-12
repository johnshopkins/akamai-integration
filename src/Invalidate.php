<?php

namespace AkamaiIntegration;

use Akamai\Open\EdgeGrid\Authentication;
use Http\Client\Exception\HttpException;
use PhpRedisQueue\QueueWorker;
use Psr\Log\LoggerInterface;

class Invalidate
{
  public function __construct(
    protected \HttpExchange\Interfaces\ClientInterface $http,
    protected string $host,
    protected string $clientToken,
    protected string $clientSecret,
    protected string $accessToken
  )
  {

  }

  /**
   * @param $urls
   * @param $network
   * @return object Response object. See examples here: https://techdocs.akamai.com/purge-cache/reference/invalidate-url
   * @throws \ErrorException
   */
  public function invalidate($urls = [], $network = 'production'): object
  {
    $path = '/ccu/v3/invalidate/url/' . $network;
    $body = json_encode(['objects' => $urls], JSON_UNESCAPED_SLASHES);
    $headers = ['Content-Type' => 'application/json'];

    $auth = new Authentication();
    $auth
      ->setAuth($this->clientToken, $this->clientSecret, $this->accessToken)
      ->setHttpMethod('POST')
      ->setHost($this->host)
      ->setPath($path)
      ->setBody($body);

    $headers['Authorization'] = $auth->createAuthHeader();

    $url = 'https://' . rtrim($this->host, '/') . $path;

    $this->http->post($url, [
      'body' => $body,
      'headers' => $headers
    ]);

    $response = $this->http->getBody();

    if ($response->httpStatus !== 201) {
      throw new \ErrorException($response->title . ' - ' . $response->detail, $response->httpStatus);
    }

    return $response;
  }
}
