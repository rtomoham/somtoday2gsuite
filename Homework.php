<?php
class Homework {

  private $id;
  private $serverCall;
  private $link;
  private $task;
  private $done = false;

  function __construct($id, $task, $link) {
    $this->id = $id;
    $this->task = $task;
    $this->link = $link;
  }

  function getId() {
    return $this->id;
  }

  function getLink() {
    return $this->link;
  }

  function getServerCall() {
    return $this->serverCall;
  }

  function getTask() {
    return $this->task;
  }

  function isDone() {
    return $this->done;
  }

  function setDone($done = false) {
    $this->done = $done;
  }

  function setServerCall($serverCall) {
    $this->serverCall = $serverCall;
  }

}
?>
