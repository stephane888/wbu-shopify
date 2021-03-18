<?php
namespace Wbu\ApiRest;

/**
 *
 * @deprecated Ce fichier n'est plus à mettre à jour.
 *             Car il contient les fichiers qui sont utilisés par l'app my.nutribe.fr/
 *
 *             Class permettant d'effectuer les requettes vers Shopify.
 *
 * @author stephane
 *
 */
class WbuShopify extends CurlShopify {

  /**
   * sous domaine shopify
   */
  protected $shop_domain = null;

  /**
   * clé de l'application privé
   */
  protected $api_key = null;

  /**
   * clé secrete de l'application privé
   */
  protected $secret = null;

  /**
   * clée pour les services de notification via webhook
   */
  protected $webhook_key = null;

  const api_version = '2019-07';

  protected $data = NULL;

  public $json = false;

  public $post = NULL;

  public $namespace = 'wbu';

  public $shopify__http_code = null;

  function __construct($configs)
  {
    parent::__construct($configs);
  }

  /**
   *
   * @param string $key
   * @param string $value
   * @param string $url
   * @param string $value_type
   * @return array[]|mixed[]
   */
  public function SaveMetafield($key, $value, $url, $value_type = "string")
  {
    $this->path = $url;
    $result = [];
    if (! $url) {
      die('EndPoints Not define');
    }
    $data = [];
    $data['metafield'] = [
      'namespace' => $this->namespace,
      'key' => $key,
      'value' => $value,
      'value_type' => $value_type
    ];
    $result['valueToSave'] = $value;
    $result['returnSave'] = json_decode($this->PostDatas(json_encode($data)), true);
    if ($this->valueInCache($url)) {
      if ($this->getValueInCache($url)) {
        $this->removeInCache($url);
      }
    }
    $this->shopify__http_code = $this->get_http_code();
    return $result;
  }

  public function SaveMetafieldProduct($id_product, $metafieldName, $value, $type = "json_string")
  {
    $url = '/admin/api/' . self::api_version . '/products/' . $id_product . '/metafields.json';
    if ($type == "json_string") {
      return $this->SaveMetafield($metafieldName, json_encode($value), $url, $type);
    } else {
      return $this->SaveMetafield($metafieldName, $value, $url, $type);
    }
  }

  /**
   *
   * @param string $arg
   * @param string $url
   * @return mixed
   */
  public function put_datas($arg, $url)
  {
    $this->path = $url;
    $result = $this->PutDatas($arg);
    $this->shopify__http_code = $this->get_http_code();
    return $result;
  }

  /**
   *
   * @param string $arg
   * @param string $url
   * @return mixed
   */
  public function post_datas(array $arg, $url)
  {
    $result = [];
    $this->path = $url;
    $data = [];
    $data['metafield'] = [
      'namespace' => $this->namespace,
      'key' => 'recettes_slider',
      'value' => json_encode($arg),
      'value_type' => 'json_string'
    ];

    $result['url'] = $this->path;
    $result['arg'] = $arg;
    $result['save'] = $this->PostDatas(json_encode($data));
    $this->shopify__http_code = $this->get_http_code();
    return $result;
  }

  public function delete_metafield($url)
  {
    $result = [];
    $this->path = $url;
    $result['url'] = $url;
    $result['save'] = $this->DeleteDatas();
    $result['url_full'] = $this->api_full_url;
    $this->shopify__http_code = $this->get_http_code();
    return $result;
  }

  /**
   */
  public function createCodePromo($code, $id_PriceRule)
  {
    $this->path = '/admin/api/2019-04/price_rules/' . $id_PriceRule . '/discount_codes.json';
    // $result = [];
    if (empty($code)) {
      return false;
    }
    $data = [];
    $data['discount_code'] = [
      'code' => $code
    ];
    return json_decode($this->PostDatas(json_encode($data)), true);
  }

  /**
   *
   * @param array $codes
   * @param number $value
   * @param string $date
   * @return boolean
   */
  public function createPriceRule(array $codes, $value = - 10, $date = null)
  {
    $this->path = '/admin/api/2019-04/price_rules.json';
    $result = [];
    if (! $date) {
      $date = date('Y-m-n');
    }
    if (empty($codes)) {
      return false;
    }
    foreach ($codes as $code) {
      $data = [];
      $data['price_rule'] = [
        'title' => $code,
        'target_type' => "line_item",
        'target_selection' => "all",
        'allocation_method' => "across",
        'value_type' => "percentage",
        'value' => $value,
        'customer_selection' => "all",
        'starts_at' => $date . "T00:00:10Z"
      ];
      $result['PriceRule'][$code] = json_decode($this->PostDatas(json_encode($data)), true);
    }
    return $result;
  }

  /**
   *
   * @param string $key
   * @param string $value
   * @param string $url
   * @param string $value_type
   */
  public function LoadMetafieldArticle($url)
  {
    $this->path = $url;
    if (! $url) {
      die('EndPoints Not define');
    }
    if ($this->valueInCache($url)) {
      $datas = $this->getValueInCache($url);
      return json_decode($datas, true);
    }
    $datas = $this->GetDatas();
    $this->SaveCache($url, $datas);
    return json_decode($datas, true);
  }

  /**
   *
   * @param string $url
   *          or
   * @return mixed
   */
  public function Load_article($url)
  {
    $this->path = $url;
    if (! $url) {
      die('EndPoints Not define');
    }
    return json_decode($this->GetDatas(), true);
  }

  /**
   * Retourne les articles du blog si $blog_id correspond effectivement à un id de blog.
   * Sinon retourne les articles aleatoirements.
   * Par defaut 50 articles sont retournées.
   *
   * @param string $url
   * @return mixed
   */
  public function Load_articles($blog_id = 125)
  {
    if (empty($blog_id)) {
      die('blog_id Not define');
    }
    $this->path = '/admin/api/' . self::api_version . '/blogs/' . $blog_id . '/articles.json';
    if ($this->valueInCache('articles' . $blog_id)) {
      $datas = $this->getValueInCache('articles' . $blog_id);
      return json_decode($datas, true);
    }
    $datas = $this->GetDatas();
    $this->SaveCache('articles' . $blog_id, $datas);
    return json_decode($datas, true);
  }

  /**
   * Load commande
   */
  public function Load_orders($order_id = null)
  {
    $this->path = '/admin/api/' . self::api_version . '/orders.json';
    if (! empty($order_id)) {
      $this->path = '/admin/api/' . self::api_version . '/orders/' . $order_id . '.json';
    }
    if ($this->valueInCache('orders' . $order_id)) {
      $datas = $this->getValueInCache('orders' . $order_id);
      return json_decode($datas, true);
    }
    $datas = $this->GetDatas();
    $this->SaveCache('orders' . $order_id, $datas);
    return json_decode($datas, true);
  }

  /**
   *
   * @param int $blog_id
   * @return mixed[][]
   */
  public function Load_articles_with_metafields($blog_id)
  {
    if (empty($blog_id)) {
      die('blog_id Not define');
    }
    $url = '/admin/api/' . self::api_version . '/blogs/' . $blog_id . '/articles.json';
    $articles = $this->Load_articles($blog_id);
    $results = [];
    foreach ($articles['articles'] as $article) {
      $url = '/admin/blogs/' . $blog_id . '/articles/' . $article['id'] . '/metafields.json';
      $results[] = [
        'article' => $article,
        'metafields' => $this->LoadMetafieldArticle($url)
      ];
    }
    return $results;
  }

  /**
   * load all blogs if id_blog is empty.
   */
  public function Load_blogs($id_blog = null)
  {
    $this->path = 'admin/blogs.json';
    if (! empty($id_blog)) {
      $this->path = 'admin/blogs/' . $id_blog . '.json';
    }
    if ($this->valueInCache('blogs' . $id_blog)) {
      $datas = $this->getValueInCache('blogs' . $id_blog);
      return json_decode($datas, true);
    }
    $datas = $this->GetDatas();
    $this->SaveCache('blogs' . $id_blog, $datas);
    return json_decode($datas, true);
  }

  public function GetAccessScope()
  {
    $this->path = '/admin/oauth/access_scopes.json';
    $datas = $this->GetDatas();
    return json_decode($datas, true);
  }

  /**
   *
   * @param int $note_user
   * @param string $url
   */
  public function Calcul_note_Article($note_user, /* string */ $url)
  {
    $name_key = 'notation';
    $note_user = intval($note_user);
    $result = [];
    // load metafiled
    $result['metafields'] = $metafields = $this->LoadMetafieldArticle($url);
    // checks if note_moyenne existe
    foreach ($metafields['metafields'] as $metafield) {
      if (isset($metafield['key']) && $metafield['key'] == $name_key) {
        $notes = json_decode($metafield['value'], true);
        $notes['note_total'] = $notes['note_total'] + $note_user;
        $notes['nombre_note'] = $notes['nombre_note'] + 1;
        $result['save'] = $this->SaveMetafieldArticle($name_key, json_encode($notes), $url, "json_string");
        return $result;
      }
    }
    $notes = [
      'note_total' => $note_user,
      'nombre_note' => 1
    ];
    $result['save'] = $this->SaveMetafieldArticle($name_key, json_encode($notes), $url, "json_string");
    return $result;
  }

  /**
   *
   * @param mixed $url_image
   * @param mixed $fileName
   * @param mixed $url
   * @return mixed
   */
  public function send_image($url_image, $fileName, $url)
  {
    $imagedata = file_get_contents($url_image);
    $base64 = base64_encode($imagedata);
    $img = [
      'asset' => [
        "key" => "assets/$fileName",
        "attachment" => $base64
      ]
    ];
    $this->path = $url;
    $re_img = $this->PutDatas(json_encode($img));
    $img_save = json_decode($re_img);
    return $img_save;
  }

  public function getOrder($id_order)
  {
    // https://7c23ab0e542bd6f9a887b894c538e308:f62c0ea80c3c7fc28038abd863ff0299@nutribe-test.myshopify.com/admin/orders/count.json?status=any
    $this->path = 'admin/orders/' . $id_order . '.json';
    return json_decode($this->GetDatas(), true);
  }

  public function getOrders($query = null)
  {
    // https://7c23ab0e542bd6f9a887b894c538e308:f62c0ea80c3c7fc28038abd863ff0299@nutribe-test.myshopify.com/admin/orders/count.json?status=any
    $this->path = 'admin/orders.json';
    return json_decode($this->GetDatas(), true);
  }

  protected function SaveCache($key, $value)
  {
    $_SESSION[$key] = $value;
  }

  protected function removeInCache($key)
  {
    unset($_SESSION[$key]);
  }

  protected function getValueInCache($key)
  {
    return $_SESSION[$key];
  }

  /**
   *
   * @param string $key
   * @return boolean
   */
  protected function valueInCache($key)
  {
    return FALSE;
    if (isset($_SESSION[$key])) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Getionnaire d'erreur de logique.
   *
   * @param string $title
   * @param int $code
   * @param string|array $error
   */
  public function buildError($title, $code, $error)
  {
    $msg = rawurlencode($title);
    header('HTTP/1.1 ' . $code . " " . $msg);
    // $error = json_encod
    die('BGQB###' . json_encode($error) . 'ENDQB###');
  }

  /**
   * Mettre à jour une commande.
   *
   * @param int $id_order
   * @param array $arg
   * @return mixed
   */
  public function update_order($id_order, $arg)
  {
    $url = '/admin/api/' . self::api_version . '/orders/' . $id_order . '.json';
    $result = $this->put_datas(json_encode($arg), $url);
    return $result;
  }
}