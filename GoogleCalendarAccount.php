<?php

class GoogleCalendarAccount {
  private $description = 'Default description for Google Calendar';
  private $account;
  private $identifier;
  private $lastUpdate = 0;

  function __construct($account, $identifier) {
    $this->account = $account;
    $this->identifier = $identifier;
  }

  function getAccount() {
    return $this->account;
  }
  
  function getIdentifier() {
    return $this->identifier;
  }

  function getDescription() {
    return $this->description;
  }

  function getLastUpdate() {
    return $this->lastUpdate;
  }

  function setDescription($description) {
    $this->description = $description;
  }

  function setLastUpdate($lastUpdate) {
    $this->lastUpdate = $lastUpdate;
  }

  function toString() {
    return 'Account: ' . $this->account . "\t" . 'Identifier: ' . "$this->identifier\n";
  }

} ?>
