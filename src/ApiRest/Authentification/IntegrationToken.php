<?php
namespace Stephane888\WbuShopify\ApiRest\Authentification;

use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\TransferStats;

/**
 * Ce fichier se charge de gerer l'authentification via le token.
 *
 * @author stephane
 *        
 */
class IntegrationToken {

  public $requestEndPoint;

  private $Errors = false;

  function __construct(array $configs = [])
  {
    $this->setConfigs($configs);
    // on doit adopter l'approche definit au niveau de : https://docs.guzzlephp.org/en/6.5/quickstart.html?highlight=file
    $this->curl = new \GuzzleHttp\Client([
      'base_uri' => $this->getUrl(),
      'headers' => $this->buildHeader()
    ]);
  }

  public function setConfigs(array $configs = [])
  {
    if (! empty($configs['domaine'])) {
      $this->setHost($configs['domaine']);
    } else {
      throw new \Exception('Hote shopify non definit');
    }
    if (! empty($configs['token'])) {
      $this->accessToken = $configs['token'];
    } else {
      throw new \Exception('Token non definit');
    }
  }

  /**
   * Methode curl GET
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  protected function get()
  {
    return $this->requeteExecute('GET');
  }

  protected function GetDatas()
  {
    return $this->requeteExecute('GET');
  }

  /**
   *
   * @param string $data
   * @return string|string[]|mixed[]|\Psr\Http\Message\RequestInterface[]
   */
  protected function post(string $data)
  {
    return $this->requeteExecute('POST', $data);
  }

  protected function PostDatas(string $data)
  {
    return $this->requeteExecute('POST', $data);
  }

  /**
   *
   * @param String $methode
   * @param string $data
   * @return string|string[]|mixed[]|\Psr\Http\Message\RequestInterface[]
   */
  private function requeteExecute(String $methode, string $data = null)
  {
    try {
      if ($data) {
        $this->payLoad = $data;
        $result = $this->curl->request($methode, trim($this->requestEndPoint, "/"), [
          'body' => $data,
          'on_stats' => function (TransferStats $stats) {
            $this->lastRequestUrl = $stats->getEffectiveUri();
            $this->transferTime = $stats->getTransferTime();
            $this->handlerStats = $stats->getHandlerStats();

            // You must check if a response was received before using the
            // response object.
            if ($stats->hasResponse()) {
              // echo $stats->getResponse()->getStatusCode();
            } else {
              // Error data is handler specific. You will need to know what
              // type of error data your handler uses before using this
              // value.
              // var_dump($stats->getHandlerErrorData());
            }
          }
        ]);
      } else {
        $result = $this->curl->request($methode, trim($this->requestEndPoint, "/"), [
          'on_stats' => function (TransferStats $stats) {
            $this->lastRequestUrl = $stats->getEffectiveUri();
            $this->transferTime = $stats->getTransferTime();
            $this->handlerStats = $stats->getHandlerStats();
          }
        ]);
      }
      return $this->traitementRequest($result);
    } catch (RequestException $e) {
      // on doit utilier RequestException
      return $this->buildError($e);
    }
  }

  protected function traitementRequest(ResponseInterface $result)
  {
    return $result->getBody()->getContents();
  }

  function buildError(RequestException $e)
  {
    $this->Errors = true;
    $body = $e->getResponse()
      ->getBody()
      ->getContents();
    return [
      'code' => $e->getCode(),
      'message' => $e->getMessage(),
      'vue par le serveur distant' => $e->hasResponse() ? 'Oui' : 'Non',
      'request' => $e->getRequest(),
      'response' => [
        'body' => json_decode($body),
        'bodyRaw' => $body,
        'Headers' => $e->getResponse()->getHeaders(),
        'Code' => $e->getResponse()->getStatusCode(),
        'title' => $e->getResponse()->getReasonPhrase()
      ],
      'payload' => json_decode($this->payLoad),
      'lastRequestUrl' => $this->lastRequestUrl->getHost() . '/' . $this->lastRequestUrl->getQuery(),
      'transferTime' => $this->transferTime,
      'handlerStats' => $this->handlerStats
    ];
  }

  /**
   * Get base url
   *
   * @return string
   */
  protected function getUrl()
  {
    $this->url = "https://" . $this->domain . '/';
    return $this->url;
  }

  private function buildHeader()
  {
    $this->headers = [
      'Accept' => 'application/json',
      'Content-Type' => 'application/json',
      'Output-Format' => 'JSON',
      'X-Shopify-Access-Token' => $this->accessToken
      // 'Authorization' => "Basic " . $this->accessToken
    ];
    return $this->headers;
  }

  /**
   * Return true s'il ya une erreur;
   *
   * @return boolean
   */
  function hasError()
  {
    return $this->Errors;
  }

  public function setHost($domain)
  {
    $this->domain = $domain;
  }

  /**
   * Permet de determiner s'il ya une erreur;
   */
  protected function ValidResult($result)
  {
    if (! empty($result['errors'])) {
      $this->has_error = true;
      $this->error_msg = $this->getErrorString($result['errors']);
    }
  }

  private function getErrorString($errors)
  {
    if (\is_array($errors)) {
      $errors = reset($errors);
      if (\is_array($errors)) {
        $errors = reset($errors);
        $this->getErrorString($errors);
      }
      return $errors;
    } else {
      return $errors;
    }
  }
}