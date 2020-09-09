<?php

include_once('SomtodayWebBroker.php');
include_once('GoogleApiBroker.php');
include_once('GoogleCalendarAccount.php');
include_once('BackoffTimer.php');
include_once('HtmlParser.php');

class Somtoday2GSuite {

  private $googleApiBroker;
  private $googleCalendarAccount;
  private $somtodayWebBroker;
  private $somtodayUser;
  private $htmlParser;
  
  private $googleCalenderSettings;
  private $somtodaySettings;
  private $backoffTimers;

  function __construct($test = false) {
    $this->processIniFile();
    $this->htmlParser = HtmlParser::getInstance();
    BackoffTimer::getInstance()->init(
      $this->backoffTimers['short'],
      $this->backoffTimers['long']);
    $this->googleApiBroker = GoogleApiBroker::getInstance();
    $this->somtodayUser = new SomtodayUser(
      $this->somtodaySettings['schoolName'], 
      $this->somtodaySettings['schoolId'], 
      $this->somtodaySettings['username'], 
      $this->somtodaySettings['password'], 
      $this->somtodaySettings['studentIndex']);
    $this->somtodayWebBroker = new SomtodayWebBroker($this->somtodayUser);
    
    $googleCalendarAccount = new GoogleCalendarAccount($this->googleCalendarSettings['account'], $this->googleCalendarSettings['identifier']);
    $this->googleApiBroker->setGoogleCalendarAccount($googleCalendarAccount);
  }

  // Start Getters and Setters

  // End Getters and Setters

  function getAllEvents() {
    $this->googleApiBroker->getAllEvents($this->googleCalendarAccount);
  }

  function clearGoogleCalendar() {
    $this->googleApiBroker->clearEvents($this->googleCalendarAccount);
  }
  
  function processIniFile() {
    $settings = getSettings();
    $this->backoffTimers = $settings['backoff_timers'];
    $this->googleCalendarSettings = $settings['google_calendar'];
    $this->somtodaySettings = $settings['somtoday'];
  }

  function refresh() {
    printMessage('START refresh');
    $response = $this->somtodayWebBroker->getAugustinianumElo();
    if ($this->htmlParser->isUpdating($response)) {
      // The site is being updated. So we cannot do anything.
      printMessage('somtoday is updating');
      error_log('STOP: somtoday is in maintenance mode.');
    } elseif ($this->htmlParser->isError($response)) {
      // We received an error message. So we cannot do anything.
      printMessage('somtoday threw an error');
      error_log('STOP: somtoday threw an error.');
    } else {
      if ($this->htmlParser->findSignInForm($response)) {    
        printMessage('Sending username');
        $action = $this->htmlParser->getAction($response);
        $action = substr($action, 2);
        $auth = substr($action, strpos($action, 'auth='));
        $response = $this->somtodayWebBroker->setUsername($action, $auth, $this->somtodayUser);
      }
      if ($this->htmlParser->findPasswordForm($response)) {
        printMessage('Sending password');
        $action = $this->htmlParser->getAction($response);
        $action = substr($action, 2);
        $response = $this->somtodayWebBroker->setPassword($action, $this->somtodayUser);
      }
      if ($this->htmlParser->findContinue($response)) {
        printMessage('Redirecting to school site (click continue)');
        $action = $this->htmlParser->getAction($response);
        $action = substr($action, 2);
        $response = $this->somtodayWebBroker->redirectToAug();
      }

      printMessage('Get student home page');
      $this->somtodayWebBroker->getStudent($this->somtodayUser->getStudentIndex());

      printMessage('Get roster');
      $classes = $this->somtodayWebBroker->getRoster();
      
      if (0 < count($classes)) {
        printMessage('Received ' . count($classes) . ' classes - sending to GSuite');
        $this->googleApiBroker->refreshCalendar($classes);
      } else {
        printMessage('Did not receive any classes - something probably went wrong');
      }

    }   
  }
}

?>
