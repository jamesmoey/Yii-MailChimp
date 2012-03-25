<?php

class MailChimpMember {

  /** @var \MailChimpList $mclist */
  protected $mclist;
  protected $email;

  protected $vars = array();
  protected $replaceGrouping = true;
  protected $grouping = array();

  /**
   * @param MailChimpList $list
   * @param string $email
   */
  public function __construct($list, $email) {
    $this->mclist = $list;
    $this->email = $email;
  }

  /**
   * Add group to grouping
   *
   * @throws Exception
   * @param string $grouping
   * @param string|array $groups
   */
  public function addGroupToGrouping($grouping, $groups) {
    if (($g = $this->mclist->getGroupings($grouping))===false) {
      throw new Exception('Grouping:'.$grouping.' does not exist');
    } else {
      if (!is_array($groups)) $groups = array($groups);
      foreach ($groups as $group) {
      if (!$g->existGroup($group)) {
        $this->mclist->addGroups($grouping, array($group));
      }
      $this->grouping[$grouping][] = $group;
    }
  }
  }

  /**
   * Set Merge Var
   *
   * @throws Exception
   * @param string $variable
   * @param string $value
   */
  public function setMergeVar($variable, $value) {
    if ($this->mclist->getMergeVars($variable)===false) {
      throw new Exception('Merge Var:'.$variable.' does not exist');
    } else {
      $this->vars[$variable] = $value;
    }
  }

  public function save($replace = false) {
    $groups = array();
    foreach ($this->grouping as $name=>$group) {
      $groups[] = array(
        'name'=>$name,
        'groups'=>join(',',$group),
      );
    }
    $this->mclist->_saveMember(
      $this->email,
      array_merge(
        $this->vars,
        array('GROUPINGS'=>$groups)
      ),
      $replace
    );
  }

  public function getMergeVars($var) {
    return $this->mclist->getMergeVars($var);
  }
}