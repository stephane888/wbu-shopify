<?php
namespace Wbu\ApiRest\Metafields;

use PhpParser\Error;

/**
 * Ce trait doit etre ajouter dans une class sui etend la classe Wbu\ApiRest\Shopify
 *
 * @author stephane
 *
 */
trait MetafieldsTrait {

  /**
   * Permet de retourner la reponse brute.
   *
   * @var boolean
   */
  public $default_ressource = false;

  public function LoadMetafiels($url = null)
  {
    if (! empty($url)) {
      $this->path = $url;
    }
    $datas = $this->GetDatas();
    return json_decode($datas, true);
  }

  /**
   * Enregistre les données formatés utiliser par my.nutribe.fr
   *
   * @param array $metafields
   * @param string $value_type
   * @deprecated ne doit plus etre utilisé
   * @return mixed
   */
  public function saveMetafields($metafields, $value_type = "string")
  {
    if ($this->validation($metafields)) {
      /**
       * On surcharge le type.
       */
      if (! empty($metafields['type_metafield'])) {
        $value_type = $metafields['type_metafield'];
      }
      if ($value_type == "json_string") {
        $metafields['value'] = json_encode($metafields['value']);
      }
      $id_entity = $metafields['id_entity'];
      switch ($metafields['type']) {
        case 'blog':
          $this->path = 'admin/api/' . $this->api_version . '/blogs/' . $id_entity . '/metafields.json';
          return $this->sendMetafields($metafields, $value_type);
          break;

        case 'article':
          if (empty($metafields['id_parent'])) {
            $this->has_error = true;
            $this->error_msg = 'L\'id_parent n\'est pas definie';
          } else {
            $id_parent = $metafields['id_parent'];
            $this->path = 'admin/api/' . $this->api_version . '/blogs/' . $id_parent . '/articles/' . $id_entity . '/metafields.json';
            return $this->sendMetafields($metafields, $value_type);
          }
          break;
        case 'product':
          $this->path = 'admin/api/' . $this->api_version . '/products/' . $id_entity . '/metafields.json';
          return $this->sendMetafields($metafields, $value_type);
          break;

        default:
          $this->has_error = true;
          $this->error_msg = 'Le type de metafileds n\'est pas encore pris en charge';
          break;
      }
    }
  }

  public function save(array $metafields)
  {
    $result = [];
    foreach ($metafields as $metafield) {
      $this->Validated($metafield);
      $result[] = $this->sendMetafields($metafield, $metafield['value_type'], $metafield['namespace']);
    }
    return $result;
  }

  protected function Validated($metafield)
  {
    if (! isset($metafield['namespace'])) {
      throw new \Error("L'attribut 'namespace' non definit");
    }
    if (! isset($metafield['key'])) {
      throw new \Error("L'attribut 'key' non definit");
    }
    if (! isset($metafield['value'])) {
      throw new \Error("L'attribut 'value' non definit");
    }
    if (! isset($metafield['value_type'])) {
      throw new \Error("L'attribut 'value_type' non definit");
    }
  }

  /**
   *
   * @param array $metafields
   * @param string $value_type
   */
  protected function sendMetafields($metafields, $value_type, $namespace = null)
  {
    if (is_array($metafields['value'])) {
      $metafields['value'] = json_encode($metafields['value']);
    }
    $data = [];
    $data['metafield'] = [
      'namespace' => $namespace ? $namespace : $this->namespace,
      'key' => $metafields['key'],
      'value' => $metafields['value'],
      'value_type' => $value_type
    ];
    $result = $this->PostDatas(json_encode($data));
    if ($this->default_ressource) {
      return $result;
    }
    $result = json_decode($result, true);
    $this->ValidResult($result);
    return $result;
  }

  /**
   *
   * @deprecated
   * @param array $metafields
   * @return boolean
   */
  protected function validation($metafields)
  {
    $this->has_error = true;
    if (empty($metafields['key'])) {
      $this->error_msg = ('La clée n\'est pas definie');
      return false;
    }
    if (! isset($metafields['value'])) {
      $this->error_msg = ('La valeur n\'est pas definie');
      return false;
    }
    if (empty($metafields['type'])) {
      $this->error_msg = ('Le type n\'est pas definie');
      return false;
    }
    if (empty($metafields['id_entity'])) {
      $this->error_msg = ('L\'id_entity n\'est pas definie');
      return false;
    }
    $this->has_error = false;
    return true;
  }
}