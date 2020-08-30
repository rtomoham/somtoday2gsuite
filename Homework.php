<?php
class Homework {
  
  private $link;
  private $task;
  private $done = false;
  
  function __construct($task, $link) {
    $this->task = $task;
    $this->link = $link;
  }
  
  function getLink() {
    return $this->link;
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
  
}
?>