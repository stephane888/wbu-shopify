<?php
namespace Wbu\ApiRest\WebHook;

use Wbu\ApiRest\WebHook\Traits\Order as TraitOrder;

class Order extends WebHook {
  use TraitOrder;

  protected $order;

  protected $id_order;

  function __construct($SHOPIFY_APP_SECRET = null, $entity = null)
  {
    parent::__construct($SHOPIFY_APP_SECRET, $entity);
  }
}