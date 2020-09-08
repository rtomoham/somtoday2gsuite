<?php

Class ClassDetails {

  private $name;
  private $location;
  private $start;
  private $end;
  private $homework;

  private const KEYWORD_DATETIME = 'dateTime';
  private const KEYWORD_TIMEZONE = 'timeZone';

  function __construct($name, $location, $start, $end, $homework) {
    $this->name = $name;
    $this->location = 'Lokaal: ' . $location;
    $this->start = $start;
    $this->end = $end;
    $this->homework = $homework;
  }

  private function getDateTime($date) {
    $dateTimeString = $date->format(DateTimeInterface::RFC3339);

    return array(
      self::KEYWORD_DATETIME => $dateTimeString,
      self::KEYWORD_TIMEZONE => SOMTODAY_TIMEZONE
    );
  }

  function getDescription() {
    $description = '';
    if (!is_null($this->homework)) {
      if ($this->homework->isDone()) {
        $description .= 'DONE: ';
      } else {
        $description .= 'TODO: ';
      }
      $description .= $this->homework->getTask();
      $description .= "\n";
//      $description .= $this->homework->getServerCall();
//      $description .= "\n";
      $description .= $this->homework->getLink();
    }
    return $description;
  }

  function getEnd() {
    return $this->getDateTime($this->end);
  }

  function getHomework() {
    return $this->homework;
  }

  function getLocation() {
    return $this->location;
  }

  function getName() {
    $name = $this->name;
    if (!is_null($this->homework)) {
      if (!$this->homework->isDone()) {
        $name .= '*';
      }
    }
    return $name;
  }

  function getRFC3339($date) {
    return $date->format(DateTimeInterface::RFC3339);
  }

  function getStart() {
    return $this->getDateTime($this->start);
  }

  function hasHomework() {
    return (!is_null($this->homework));
  }

  function toString() {
    return $this->name . ' ' . $this->location . ' ' . $this->getRFC3339($this->start) . ' ' . $this->getRFC3339($this->end);
  }
}

?>
