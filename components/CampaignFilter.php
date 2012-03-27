<?php
class CampaignFilter {
  /** @var string $campaign_id optional - return the campaign using a know campaign_id. Accepts multiples separated by commas when not using exact matching. */
  public $campaign_id;
  /** @var string $list_id optional - the list to send this campaign to- get lists using lists(). Accepts multiples separated by commas when not using exact matching. */
  public $list_id;
  /** @var int $folder_id optional - only show campaigns from this folder id - get folders using campaignFolders(). Accepts multiples separated by commas when not using exact matching. */
  public $folder_id;
  /** @var int $template_id optional - only show campaigns using this template id - get templates using templates(). Accepts multiples separated by commas when not using exact matching. */
  public $template_id;
  /** @var string $status optional - return campaigns of a specific status - one of "sent", "save", "paused", "schedule", "sending". Accepts multiples separated by commas when not using exact matching. */
  public $status;
  /** @var string $type optional - return campaigns of a specific type - one of "regular", "plaintext", "absplit", "rss", "trans", "auto". Accepts multiples separated by commas when not using exact matching. */
  public $type;
  /** @var string $from_name optional - only show campaigns that have this "From Name" */
  public $from_name;
  /** @var string $from_email optional - only show campaigns that have this "Reply-to Email" */
  public $from_email;
  /** @var string $title optional - only show campaigns that have this title */
  public $title;
  /** @var string $subject optional - only show campaigns that have this subject */
  public $subject;
  /** @var string $sendtime_start optional - only show campaigns that have been sent since this date/time (in GMT) - format is YYYY-MM-DD HH:mm:ss (24hr) */
  public $sendtime_start;
  /** @var string $sendtime_end optional - only show campaigns that have been sent before this date/time (in GMT) - format is YYYY-MM-DD HH:mm:ss (24hr) */
  public $sendtime_end;
  /** @var boolean $exact optional - flag for whether to filter on exact values when filtering, or search within content for filter values - defaults to true. Using this disables the use of any filters that accept multiples. */
  public $exact;
}