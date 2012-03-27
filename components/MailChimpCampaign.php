<?php

Yii::import("ext.tools.components.*");

/**
 * MailChimp Campaign
 *
 * @class                              MailChimpCampaign
 * @property string $id                 Campaign Id (used for all other campaign functions)
 * @property int $web_id                The Campaign id used in our web app, allows you to create a link directly to it
 * @property string $list_id            The List used for this campaign
 * @property int $folder_id             The Folder this campaign is in
 * @property int $template_id           The Template this campaign uses
 * @property string $content_type       How the campaign's content is put together - one of 'template', 'html', 'url'
 * @property string $title              Title of the campaign
 * @property string $type               The type of campaign this is (regular,plaintext,absplit,rss,inspection,trans,auto)
 * @property string $create_time        Creation time for the campaign
 * @property string $send_time          Send time for the campaign - also the scheduled time for scheduled campaigns.
 * @property int $emails_sent           Number of emails email was sent to
 * @property string $status             Status of the given campaign (save,paused,schedule,sending,sent)
 * @property string $from_name          From name of the given campaign
 * @property string $from_email         Reply-to email of the given campaign
 * @property string $subject            Subject of the given campaign
 * @property string $to_name            Custom "To:" email string using merge variables
 * @property string $archive_url        Archive link for the given campaign
 * @property boolean $inline_css        Whether or not the campaign content's css was auto-inlined
 * @property string $analytics          Either "google" if enabled or "N" if disabled
 * @property string $analytics_tag      The name/tag the campaign's links were tagged with if analytics were enabled.
 * @property boolean $authenticate      Whether or not the campaign was authenticated
 * @property boolean $ecomm360          Whether or not ecomm360 tracking was appended to links
 * @property boolean $auto_tweet        Whether or not the campaign was auto tweeted after sending
 * @property string $auto_fb_post       A comma delimited list of Facebook Profile/Page Ids the campaign was posted to after sending. If not used, blank.
 * @property boolean $auto_footer       Whether or not the auto_footer was manually turned on
 * @property boolean $timewarp          Whether or not the campaign used Timewarp
 * @property boolean $timewarp_schedule The time, in GMT, that the Timewarp campaign is being sent. For A/B Split campaigns, this is blank and is instead in their schedule_a and schedule_b in the type_opts array
 * @property array $tracking            containing "text_clicks", "html_clicks", and "opens" as boolean values representing whether or not they were enabled
 * @property string $segment_text       a string marked-up with HTML explaining the segment used for the campaign in plain English
 * @property array $segment_opts        the segment used for the campaign - can be passed to campaignSegmentTest() or campaignCreate()
 * @property array $type_opts           the type-specific options for the campaign - can be passed to campaignCreate()
 */
class MailChimpCampaign extends StatefulModel {

  /** @var $mc MCAPI */
  protected $mc;
  protected $placeHolders = array();

  /**
   * Get list of MailChimp Campaign.
   *
   * @static
   * @param CampaignFilter $filter
   * @return MailChimpCampaign[]
   */
  public static function get($filter) {
    /** @var $mc MCAPI */
    $mc = Yii::app()->getComponent("mailchimp")->getMCAPI();
    $list = $mc->campaigns((array)$filter, 0, 1000);
    $result = array();
    foreach ($list['data'] as $c) {
      $result[] = Yii::createComponent(
        array('class'=>'MailChimpCampaign')+$c
      );
    }
    return $result;
  }

  /**
   * Get Campaign by ID.
   *
   * @static
   * @param string $id
   * @return MailChimpCampaign|null
   */
  public static function getById($id) {
    $filter = new CampaignFilter();
    $filter->campaign_id = $id;
    $list = self::get($filter);
    if (isset($list[0])) return $list[0];
    else return null;
  }

  /**
   * Get Campaign by Title.
   *
   * @static
   * @param string $title
   * @return MailChimpCampaign|null
   */
  public static function getByTitle($title) {
    $filter = new CampaignFilter();
    $filter->title = $title;
    $list = self::get($filter);
    if (isset($list[0])) return $list[0];
    else return null;
  }

  public function __construct() {
    $this->mc = Yii::app()->getComponent("mailchimp")->getMCAPI();
  }

  /**
   * Replicate this campaign
   *
   * @return MailChimpCampaign
   */
  public function replicate() {
    $id = $this->mc->campaignReplicate($this->id);
    return self::getById($id);
  }

  public function save() {
    if (!empty($this->placeHolders)) {
      $contents = array();
      foreach ($this->placeHolders as $phName => $value) {
        $contents['html_'.$phName] = $value;
      }
      $this->mc->campaignUpdate($this->id, 'content', $contents);
      $this->placeHolders = array();
    }
    foreach ($this->getChanges() as $field=>$value) {
      $this->mc->campaignUpdate($this->id, $field, $value);
    }
    $this->commitChanges();
  }

  /**
   * Send campaign now or schedule it
   *
   * @param string $schedule in format of YYYY-MM-DD HH:II:SS in GMT
   * @return bool
   */
  public function send($schedule = 'now') {
    if ($schedule == 'now') {
      return $this->mc->campaignSendNow($this->id);
    } else {
      return $this->mc->campaignSchedule($this->id, $schedule);
    }
  }

  /**
   * Send a test email of the campaign to the email address
   *
   * @param $email string
   * @return bool
   */
  public function sendTest($email) {
    return $this->mc->campaignSendTest($this->id, array($email));
  }

  /**
   * Update placeholder in the campaign content. Remember to call save to save the changes onto mailchimp.
   *
   * @param string $name
   * @param string $value
   * @return MailChimpCampaign
   */
  public function updateContent($name, $value, $htmlentities = true) {
    if ($htmlentities) $this->placeHolders[$name] = htmlentities($value);
    else $this->placeHolders[$name] = $value;
    return $this;
  }

  /**
   * Returns the list of attribute names of the model.
   * @return array list of attribute names.
   * @since 1.0.1
   */
  public function attributeNames() {
    return array(
      'id',
      'web_id',
      'list_id',
      'folder_id',
      'template_id',
      'content_type',
      'title',
      'type',
      'create_time',
      'send_time',
      'emails_sent',
      'status',
      'from_name',
      'from_email',
      'subject',
      'to_name',
      'archive_url',
      'inline_css',
      'analytics',
      'analytics_tag',
      'authenticate',
      'ecomm360',
      'auto_tweet',
      'auto_fb_post',
      'auto_footer',
      'timewarp',
      'timewarp_schedule',
      'tracking',
      'segment_text',
      'segment_opts',
      'type_opts',
    );
  }
}