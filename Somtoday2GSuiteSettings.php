<?php

require_once('Settings.php');

/*
 * Singleton class containing all the settings for Somtoday2GSuite
 */
class Somtoday2GSuiteSettings extends Settings {

  private const KEYWORD_GCAL_ACCOUNTS = 'google_calendar_accounts';
  private const KEYWORD_MIJNKNLTB_ACCOUNT = 'mijnknltb_account';
  private const FILENAME_COOKIE = 'cookies.txt';

  private const STRING_GOOGLE_CALENDAR = 'google_calendar';
  private const STRING_SOMTODAY = 'somtoday';

  private $filenames;  // [ 'accounts' => 'x', 'serviceAccount' => 'x' ]

  private $googleSuiteAccount;
  private $somtodaySettings;

  function __construct($test) {
    parent::__construct($test);
  }

  public static function getInstance($test = false) {
    if (is_null(self::$instance)) {
      self::$instance = new Somtoday2GSuiteSettings($test); }
    return self::$instance;
  }

  function init($programName) {
    parent::init($programName);
    $this->googleSuiteAccount = $this->data[self::STRING_GOOGLE_CALENDAR];
    $this->somtodaySettings = $this->data[self::STRING_SOMTODAY];
  }

  function getAccountsFilename() {
    return $this->data[STRING_FILENAMES][STRING_ACCOUNTS];
  }

  function getCookiesFilename() {
    return $this->dataPath . self::FILENAME_COOKIE;
  }

  function getGoogleCalendarAccount() {
    return $this->googleSuiteAccount;
  }

  function getSomtodaySettings() {
    return $this->somtodaySettings;
  }

  function getGoogleSuiteAccounts() {
    return $this->googleSuiteAccounts;
  }

  function getMijnknltbAccounts() {
    return $this->mijnknltbAccounts;
  }

  function getHeaderString($header) {
    if (MAX_HEADER_TEXT < strlen($header)) {
      $header = substr($header, 0, MAX_HEADER_TEXT-1);
    }
    return str_pad(' ' . $header . ' ', MAX_HEADER_WIDTH, '-', STR_PAD_BOTH) . "\n";
  }

}

?>
