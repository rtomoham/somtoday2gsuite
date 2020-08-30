<?php
class SomtodayUser {
  private $schoolName;
  private $schoolId;
  private $username;
  private $password;
  private $studentIndex;
  
  function __construct($schoolName, $schoolId, $username, $password, $studentIndex) {
    $this->schoolName = $schoolName;
    $this->schoolId = $schoolId;
    $this->username = $username;
    $this->password = $password;
    $this->studentIndex = $studentIndex;
  }

  function getLogin() {
    return $this->getUsername();
  }

  function getUsername() {
    return $this->username;
  }

  function getPassword() {
    return $this->password;
  }
  
  function getSchoolName() {
    return $this->schoolName;
  }
  
  function getSchoolId() {
    return $this->schoolId;
  }
  
  function getStudentIndex() {
    return $this->studentIndex;
  }
  
} ?>
