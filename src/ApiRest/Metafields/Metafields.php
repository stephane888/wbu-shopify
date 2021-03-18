<?php
namespace Wbu\ApiRest\Metafields;

use Wbu\ApiRest\Shopify;

class Metafields extends Shopify {
  use MetafieldsTrait;

  protected $namespace = '';

  function __construct($configs, $namespace)
  {
    $this->namespace = $namespace;
    parent::__construct($configs);
  }
}