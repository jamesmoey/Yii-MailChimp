<?php

class MailChimp extends CApplicationComponent {

  public $apiKey;

  public $list;

  protected $api;

  /**
   * @return MCAPI
   */
  public function getMCAPI() {
    if (!$this->api) {
      Yii::import('ext.mailchimp.vendors.MCAPI', true);
      if (isset($_ENV['COMPANY'])) {
        $this->apiKey = $this->list[$_ENV['COMPANY']];
      }
      $this->api = new MCAPI($this->apiKey);
    }
    return $this->api;
  }
}
