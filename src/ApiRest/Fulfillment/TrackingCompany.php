<?php
namespace Wbu\ApiRest\Fulfillment;

class TrackingCompany {

  static public function list()
  {
    return [
      '4PX' => '4PX',
      'APC' => 'APC',
      'Amazon Logistics UK' => 'Amazon Logistics UK',
      'Amazon Logistics US' => 'Amazon Logistics US',
      'La Poste' => 'La Poste'
    ];
  }

  static public function isValid($key)
  {
    if (! empty(self::list()[$key])) {
      return true;
    }
    return false;
  }
}