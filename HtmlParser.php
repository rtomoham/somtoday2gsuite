<?php

include_once('ClassDetails.php');
include_once('Utils.php');
include_once('Homework.php');

class HtmlParser {

  private static $instance = NULL;
  private $domDoc;
  private $timezone;
  private const DATETIMEFORMAT = 'j-m-Y H:i';

  private function __construct() {
    // Suppress DOM warnings
    libxml_use_internal_errors(true);
    $this->domDoc = new DOMDocument();
    $this->timezone = new DateTimeZone(SOMTODAY_TIMEZONE); 
  }

  static function getInstance() {
    if (is_null(self::$instance)) {
      self::$instance = new HtmlParser();
    }
    return self::$instance;
  }

  function findSignInForm($htmlString) {
    return $this->findForm($htmlString, 'signInForm');
  }
  
  function findPasswordForm($htmlString) {
    return $this->findForm($htmlString, 'passwordForm');
  }
  
  function isUpdating($htmlString) {
    $this->domDoc->loadHTML($this->cleanUpString($htmlString));
    libxml_clear_errors();

    $iframes = $this->domDoc->getElementsByTagName('iframe');
    if (is_null($iframes)) {
      return false;
    }
    foreach ($iframes as $iframe) {
      $source = $iframe->getAttribute('src');
      if (0 == strcmp('https://www.som.today/updaten', $source)) {
        return true;
      }
    }
    return false;
  }
  
  private function findForm($htmlString, $formName) {
    $pos = strpos($htmlString, $formName);
    if (false === $pos) {
      return false;
    } else {
      if (0 == $pos) {
        return true;
      }
    }
    return $pos;
  }
  
  function getAction($htmlString) {
    $this->domDoc->loadHTML($this->cleanUpString($htmlString));
    libxml_clear_errors();

    $forms = $this->domDoc->getElementsByTagName('form');
    foreach ($forms as $form) {
      $method = $form->getAttribute('method');
      if (0 == strcmp($method, 'post')) {
        $action = $form->getAttribute('action');
        return $action;
      }
    }
  }
  
  function getClasses($htmlString) {
    $this->domDoc->loadHTML($this->cleanUpString($htmlString));
    libxml_clear_errors();

    $classes = [];
    $divs = $this->domDoc->getElementsByTagName('div');
    foreach ($divs as $div) {
      $class = $div->getAttribute('class');
      $truncate_afspraak = strpos($class, 'truncate afspraak');
      if (! (false === $truncate_afspraak)) {
        $kwtinfoCounter = 0;
        $homework = NULL;
        $id = $div->getAttribute('id');
        $divs2 = $div->getElementsByTagName('div');
        foreach ($divs2 as $div2) {
          $class2 = $div2->getAttribute('class');
          if (0 == strcmp($class2, 'afspraakVakNaam truncate')) {
            $className = $div2->textContent;
          } elseif (0 == strcmp($class2, 'afspraakLocatie')) {
            $classLocation = $div2->textContent;
          } elseif (0 == strcmp($class2, 'toekenning truncate')) {
            $link = $div2->getElementsByTagName('a')[0]->getAttribute('href');
            $spans = $div2->getElementsByTagName('span');
            $classHomework = $spans[2]->getAttribute('class');
            $homework = new Homework($spans[2]->textContent, $link);
            $homework->setDone(0 == strcmp('huiswerk-gemaakt', $classHomework));
          } elseif (0 == strcmp($class2, 'kwtinfo')) {
            switch ($kwtinfoCounter) {
              case 0: 
                // this line contains the location, so ignore
              break;
              case 1: 
                $start = $this->getKwtinfo($div2->textContent);
              break;
              case 2: 
                $end = $this->getKwtinfo($div2->textContent);
              break;
              default: 
                // ignore
              break;
            }
            $kwtinfoCounter++;
          }
        }
        $classDetails = new ClassDetails($className, $classLocation, $start, $end, $homework);
        $classes[] = $classDetails;
      }
    }
    return $classes;
  }
  
  function getKwtinfo($html) {
    $html = substr($html, strpos($html, '<span>') + 5, 16);
    return $this->getDateTimeImmutable($html);
  }
  
  function getSAMLResponse($htmlString) {
    $this->domDoc->loadHTML($this->cleanUpString($htmlString));
    libxml_clear_errors();

    $inputs = $this->domDoc->getElementsByTagName('input');
    foreach ($inputs as $input) {
      $name = $input->getAttribute('name');
      if (0 == strcmp($name, 'SAMLResponse')) {
        $value = $input->getAttribute('value');
        return $value;
      }
    }    
  }
  
  /**
  * DEPRECATED
  */
  function getSelectLeerling($htmlString) {
    return $this->getBaseUrl($htmlString) . '-1.0-leerling-0-selectLeerling';
  }
  
  function getBaseUrl($htmlString) {
    $pos = strpos($htmlString, 'baseUrl');
    if (! (false === $pos)) {
      $baseUrl = substr($htmlString, $pos + 9);
      $pos = strpos($baseUrl, '"');
      return substr($baseUrl, 0, $pos);
    }
  }
  
  private function getDateTimeImmutable($date) {
    return DateTimeImmutable::createFromFormat(self::DATETIMEFORMAT, $date, $this->timezone);
  }

  private function cleanUpString($htmlString) {
    //Replace the newline and carriage return characters
    //using str_replace.
    // And while I'm at it, remove a large string of spaces that shows up
    // in the LeagueName
    return trim(str_replace(array("\n", "\r", '             '), '', $htmlString));
  }

} ?>
