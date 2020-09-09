<?php
class Homework {

  private $id;
  private $serverCall;
  private $link;
  private $task;
  private $done = false;
  private $details = NULL;

  function __construct($id, $task, $link) {
    $this->id = $id;
    $this->task = $task;
    $this->link = $link;
  }

  function getDetails() {
    if (is_null($this->details)) {
      return 'No details.';
    }
    return $this->details;
  }

  function getId() {
    return $this->id;
  }

  function getLink() {
    return $this->link;
  }

  function getServerCall($baseUrl) {
    return $baseUrl . $this->serverCall;
  }

  function getTask() {
    return $this->task;
  }

  function isDone() {
    return $this->done;
  }

  function setDetails($details) {
    $this->details = $details;
  }
  function setDone($done = false) {
    $this->done = $done;
  }

  function setServerCall($serverCall) {
    $this->serverCall = $serverCall;
  }

}
?>
