<?php
namespace Wbu\ApiRest\Metafields;

use Wbu\ApiRest\Authentification\IntegrationToken;

class MetafieldsToken extends IntegrationToken {
  use MetafieldsTrait;

  protected $namespace = '';

  function __construct($configs, $namespace)
  {
    $this->namespace = $namespace;
    parent::__construct($configs);
  }
}