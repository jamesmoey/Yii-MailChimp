<?php

Yii::import("ext.tools.components.*");

class MailChimpList {

  protected $id;
  protected $name;
  protected $data;
  /** @var MCAPI $api */
  protected $api;

  protected $mergeVars = null;
  protected $grouping = null;
  public $emails = array();

  protected $toBeSave = array();
  protected $toBeReplace = array();

  /** @var MailChimpMember[] $members */
  protected $members = array();

  private function __construct($data) {
    $this->id = $data['id'];
    $this->name = $data['name'];
    $this->data = $data;
    $this->api = Yii::app()->getComponent('mailchimp')->getMCAPI();
    /** Load all members  */
    $limit = 15000;
    $offset = 0;
    do {
      $list = $this->api->listMembers($this->id, "subscribed", null, $offset, $limit);
      foreach ($list['data'] as $data) {
        $this->emails[] = $data['email'];
      }
      $offset += count($list['data']);
    } while(count($list['data'])==$limit);
  }

  public function getId() {
    return $this->id;
  }

  public function _saveMember($email, $mergeVar, $replace = false) {
    if ($replace) {
      if (isset($this->toBeSave[$email])) {
        $mergeVar = array_merge_recursive($mergeVar, $this->toBeSave[$email]);
        unset($this->toBeSave[$email]);
      }
      if (isset($this->toBeReplace[$email])) {
        $mergeVar = array_merge_recursive($mergeVar, $this->toBeReplace[$email]);
      }
      $this->toBeReplace[$email] = array_merge(array('EMAIL'=>$email), $mergeVar);
    } else {
      if (isset($this->toBeReplace[$email])) {
        $this->_saveMember($email, $mergeVar);
      } else {
        $this->toBeSave[$email] = array_merge(array('EMAIL'=>$email), $mergeVar);
      }
    }
    echo "MEMS: ".(memory_get_usage(true)/(1024*1024))."MB ".$email."\n";
    if (count($this->toBeReplace) > 800 || count($this->toBeSave) > 800) {
      $this->save();
    }
  }

  public function save() {
    if (count($this->toBeSave) > 0) {
      $this->api->listBatchSubscribe(
        $this->id,
        $this->toBeSave,
        false,
        true,
        false
      );
      $this->toBeSave = array();
    }
    if (count($this->toBeReplace) > 0) {
      $this->api->listBatchSubscribe(
        $this->id,
        $this->toBeReplace,
        false,
        true,
        true
      );
      $this->toBeReplace = array();
    }
    unset($this->members);
  }

  /**
   * @param string $name
   * @param bool $forceUpdate
   * @return MailChimpMergeVars[]|bool|MailChimpMergeVars
   */
  public function getMergeVars($name = '', $forceUpdate = false) {
    if ($this->mergeVars == null || $forceUpdate) $this->mergeVars = $this->api->listMergeVars($this->id);
    if (empty($name)) {
      $list = array();
      foreach ($this->mergeVars as $data) {
        $list[] = new MailChimpMergeVars($data);
      }
      return $list;
    } else if (is_array($this->mergeVars)) {
      foreach($this->mergeVars as $key=>$value) {
        if (isset($value['name']) && $value['name'] == $name) return new MailChimpMergeVars($value);
        if (isset($value['tag']) && $value['tag'] == $name) return new MailChimpMergeVars($value);
      }
    }
    return false;
  }

  /**
   * @param string $name
   * @param bool $forceUpdate
   * @return MailChimpGrouping[]|bool|MailChimpGrouping
   */
  public function getGroupings($name = '', $forceUpdate = false) {
    if ($this->grouping == null || $forceUpdate) $this->grouping = $this->api->listInterestGroupings($this->id);
    if (empty($name)) {
      $list = array();
      foreach ($this->grouping as $data) {
        $list[] = new MailChimpGrouping($data);
      }
      return $list;
    } else if (is_array($this->grouping)) {
      foreach($this->grouping as $key=>$value) {
        if (isset($value['id']) && $value['id'] == $name) return new MailChimpGrouping($value);
        if (isset($value['name']) && $value['name'] == $name) return new MailChimpGrouping($value);
      }
    }
    return false;
  }

  public function addMergeVar($name, $description, $field_type = "text") {
    if ($this->getMergeVars($name)!==false) return false;
    $options = array(
      'public'=>false,
      'show'=>false,
      'field_type'=>$field_type,
    );
    if ($field_type == "date") {
      $options['dateformat'] = 'DD/MM/YYYY';
    }
    $result = $this->api->listMergeVarAdd($this->id, $name, $description, $options);
    if ($result) $this->getMergeVars($name, true);
    return $result;
  }

  /**
   * Add or update MailChimp Grouping's group.
   *
   * @throws Exception
   * @param string $groupingName
   * @param array $groups
   * @return bool
   */
  public function addGroups($groupingName, $groups) {
    if (($grouping = $this->getGroupings($groupingName))===false) {
      $result = $this->api->listInterestGroupingAdd($this->id, $groupingName, 'hidden', $groups);
    } else {
      $result = false;
      foreach ($groups as $group) {
        if (!$grouping->existGroup($group)) {
          $result = ($result || $this->api->listInterestGroupAdd($this->id, $group, $grouping->id));
        }
      }
    }
    if ($result) $this->getGroupings($groupingName,true);
    return $result;
  }

  /**
   * Get or create Mail Chimp member from Account Active Record.
   * @param Accounts $account
   * @return MailChimpMember[]
   */
  public function getOrCreateMember($account) {
    if (!isset($account->email_addr_bean_rel) || !isset($account->email_addr_bean_rel)) return array();
    $emailaddresses = ArrayHelper::extractListOfValuesFromModels($account->email_addr_bean_rel, 'valid_email_addresses.email_address');
    $list = array();
    foreach ($emailaddresses as $email) {
      if ($email == null) continue;
      if (isset($this->members[$email])) $member = $this->members[$email];
      else {
        $member = new MailChimpMember($this, $email);
        $member->setMergeVar("FNAME", $account->accounts_cstm->first_name_c);
        $member->setMergeVar("LNAME", $account->accounts_cstm->last_name_c);
      }
      $list[] = $this->members[$email] = $member;
    }
    return $list;
  }

  protected static $list = array();

  /**
   * Get a list from MailChimp
   * @static
   * @throws Exception
   * @param string $name List name
   * @return MailChimpList
   */
  public static function get($name) {
    if (isset(MailChimpList::$list[$name])) return MailChimpList::$list[$name];
    /** @var $api MCAPI */
    $api = Yii::app()->getComponent('mailchimp')->getMCAPI();
    $list = $api->lists(array(
      'list_name'=>$name
    ));
    if ($api->errorCode) throw new Exception($api->errorMessage);
    if ($list['total'] == 1) {
      MailChimpList::$list[$name] = new MailChimpList($list['data'][0]);
      return MailChimpList::$list[$name];
    } else throw new Exception("List was not found or more than 1 were found.");
  }
}

class MailChimpMergeVars {
  public function __construct($data) {
    $this->name       = $data['name'];
    $this->req        = $data['req'];
    $this->field_type = $data['field_type'];
    $this->public     = $data['public'];
    $this->show       = $data['show'];
    $this->order      = $data['order'];
    $this->default    = $data['default'];
    $this->size       = $data['size'];
    $this->tag        = $data['tag'];
  }

  public $name;
  public $req;
  public $field_type;
  public $public;
  public $show;
  public $order;
  public $default;
  public $size;
  public $tag;
}

class MailChimpGrouping {
  public function __construct($data) {
    $this->id         = $data['id'];
    $this->name       = $data['name'];
    $this->form_field = $data['form_field'];
    foreach ($data['groups'] as $group) {
      $this->groups[] = new MailChimpGroup($group);
    }
  }

  public $id;
  public $name;
  public $form_field;

  /** @var MailChimpGroup[] $groups */
  public $groups = array();

  public function existGroup($groupname) {
    foreach ($this->groups as $group) {
      if ($group->name == $groupname) return true;
    }
    return false;
  }
}

class MailChimpGroup {
  public function __construct($data) {
    $this->bit           = $data['bit'];
    $this->name          = $data['name'];
    $this->display_order = $data['display_order'];
    $this->subscribers   = $data['subscribers'];
  }

  public $bit;
  public $name;
  public $display_order;
  public $subscribers;
}