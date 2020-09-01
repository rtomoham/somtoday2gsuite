<?php

include_once __DIR__ . '/vendor/autoload.php';

include_once('BackoffTimer.php');

/*
* Singleton interface into the Google API.
* Public method:
*/
class GoogleApiBroker {
  private static $instance = NULL;
  private const FILENAME_GOOGLE_SERVICE_ACCOUNT =
    PATH_DATA . '/somtoday-SA.json';
  private const STRING_CALENDAR_DESCRIPTION_PREFIX = 'AUG.';

  private $calendarService;
  private $client;

  private function __construct() {
//    $this->processIniFile();
    putenv(
//      'GOOGLE_APPLICATION_CREDENTIALS=' . $this->filenameServiceAccount
      'GOOGLE_APPLICATION_CREDENTIALS=' . self::FILENAME_GOOGLE_SERVICE_ACCOUNT
    );

    $this->client = new Google_Client();
    // use the application default credentials, provided in
    // 'GOOGLE_APPLICATION_CREDENTIALS'
    $this->client->useApplicationDefaultCredentials();
    $this->client->setApplicationName('somtoday');
    $this->client->setScopes([Google_Service_Calendar::CALENDAR]);
  }

  public static function getInstance() {
    if (is_null(self::$instance)) { self::$instance = new GoogleApiBroker(); }
    return self::$instance;
  }

  function setGoogleCalendarAccount($googleCalendarAccount) {
    $this->googleCalendarAccount = $googleCalendarAccount;
    $this->client->setSubject($googleCalendarAccount->getAccount());
    $this->calendarService = new Google_Service_Calendar($this->client);
  }

  function printCalendars() {
    foreach ($calendars as $calendarId) {
      printBasicMessage(
        $this->calendarService->calendars->get($calendarId)->getSummary()
      );
    }
  }

  function addClasses($classes, $googleCalendarId) {
    foreach ($classes as $classDetails) {
      $this->addEvent($classDetails, $googleCalendarId);
      BackOffTimer::getInstance()->sleep('Short sleep after adding ' . $classDetails->toString());
    }
  }

  function addEvent($classDetails, $googleCalendarId) {
    /*
    * Pre:  TRUE
    * Post: $classDetails has been added to $googleCalendarAccount
    */
    $classArray = array(
      'summary' => self::STRING_CALENDAR_DESCRIPTION_PREFIX . $classDetails->getName(),
      'location' => $classDetails->getLocation(),
      'description' =>
        $classDetails->getDescription() .
        "\n\nLast update: " . date('Y-m-d H:i') . 'h',
      'start' => $classDetails->getStart(),
      'end' => $classDetails->getEnd(),
    );

    try {
      $event = new Google_Service_Calendar_Event($classArray);
      $event = $this->calendarService->events->insert($googleCalendarId, $event);
    } catch (Exception $e) {
      echo 'Exception $e: ' . $e->getMessage();
    }
  }

  function clearCalendar($googleCalendarId) {
    /*
    * Pre:  TRUE
    * Post: all events previously created by this service account have been
    *       deleted
    */
    $events = $this->calendarService->events->listEvents($googleCalendarId);

    while(true) {
      foreach ($events->getItems() as $event) {
        $summary = $event->getSummary();
        if (! (false === strpos($summary, self::STRING_CALENDAR_DESCRIPTION_PREFIX))) {
          $this->calendarService->events->delete($googleCalendarId, $event->getId());
        }
      }
      $pageToken = $events->getNextPageToken();
      if ($pageToken) {
        $optParams = array('pageToken' => $pageToken);
        $events = $this->calendarService->events->listEvents($googleCalendarId, $optParams);
      } else {
        break;
      }
    }
  }

  function getAllEvents($googleCalendarAccount) {
    $googleCalendarId = $googleCalendarAccount->getIdentifier();

    // If I omitted the next parameter, I would not get all events
    // Probably a bug on Google's side
    $optParams = array('singleEvents' => 'true');
    $eventsList = $this->calendarService->events->listEvents(
      $googleCalendarId, $optParams
    );

    $events = $eventsList->getItems();
    foreach($events as $event) {

      $creator = $event->getCreator();
      if (0 == strcmp(
        $this->calendarService->getClient()->getClientId(),
        $creator->getId())
      ) {
        printBasicMessage('Found "' .
        $event->getSummary() . '" with event-id "' .
        $event->getICalUid() . '" from Google Calendar');
      }
    }
    return $events;
  }

  function refreshCalendar($classes) {
    $googleCalendarId = $this->googleCalendarAccount->getIdentifier();
    $this->clearCalendar($googleCalendarId);
    $this->addClasses($classes, $googleCalendarId);
  }

  function processIniFile() {
    $settings = getSettings();
    $filenames = $settings[STRING_FILENAMES];
    $this->filenameServiceAccount = PATH_DATA . '/' . $filenames[STRING_SERVICE_ACCOUNT];
  }



}

?>
