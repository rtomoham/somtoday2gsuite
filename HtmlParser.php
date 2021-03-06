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

  /*
  * Check if the $htmlString contains the continue button, which is required
  * since we do not have javascript running
  */
  function findContinue($htmlString) {
    $this->domDoc->loadHTML($this->cleanUpString($htmlString));
    libxml_clear_errors();

    $inputs = $this->domDoc->getElementsByTagName('input');
    if (is_null($inputs)) {
      return false;
    }
    foreach ($inputs as $input) {
      $value = $input->getAttribute('value');
      if (0 == strcmp('Continue', $value)) {
        return true;
      }
    }
    return false;
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

  function isError($htmlString) {
    $this->domDoc->loadHTML($this->cleanUpString($htmlString));
    libxml_clear_errors();

    $htmls = $this->domDoc->getElementsByTagName('html');
    if (is_null($htmls)) {
      return false;
    }
    foreach ($htmls as $html) {
      $class = $html->getAttribute('class');
      var_dump($class);
      if (0 == strcmp('error', $class)) {
        return true;
      }
    }
    return false;
/*
    $divs = $this->domDoc->getElementsByTagName('div');
    if (is_null($divs)) {
      return false;
    }
    foreach ($divs as $div) {
      $class = $div->getAttribute('class');
      var_dump($class);
      if (0 == strcmp('stpanel--error--message', $class)) {
        return true;
      }
    }
    return false;
*/
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

  function getClasses($htmlString, $homeworkServerCalls) {
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
            $a = $div2->getElementsByTagName('a')[0];
            $id = $a->getAttribute('id');
            $link = $a->getAttribute('href');
            $spans = $div2->getElementsByTagName('span');
            $classHomework = $spans[2]->getAttribute('class');
            $homework = new Homework($id, $spans[2]->textContent, $link);
            if (array_key_exists($id, $homeworkServerCalls)) {
              $homework->setServerCall($homeworkServerCalls[$id]);
            }
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

  function getHomeworkDetails($html) {
    $this->domDoc->loadHTML($this->cleanUpString($html));
    libxml_clear_errors();

    $divs = $this->domDoc->getElementsByTagName('div');
    foreach ($divs as $div) {
      $divClass = $div->getAttribute('class');
      if (0 == strcmp($divClass, 'm-wrapper active')) {
        $spans = $div->getElementsByTagName('span');
        foreach ($spans as $span) {
          $spanClass = $span->getAttribute('class');
          if (0 == strcmp($spanClass, 'huiswerk')) {
//            return str_replace('\<br\>', '___', $span->textContent);
            return preg_replace('/<br\\s*\/>/', '___', $span->textContent);
          }
        }
      }
    }
  }

  function getHomeworkServerCalls($text) {
    $regExId =
      '/#id\\S+\'\\)\\.bindServerCall\\(\\{ajax\\:\\ \\{"u":"\\..+","m/';
    $regExServerCall =
//      '/\'\\)\\.bindServerCall\\(\\{ajax\\:\\ \\{"u"\\:"\\./';
      '/\'\\)\\.bindServerCall\\(\\{ajax\\:\\ \\{"u"\\:"\\.\/roster?/';

    preg_match_all($regExId, $text, $matches);
    $results = [];
    foreach ($matches[0] as $match) {
      $splitMatch = preg_split($regExServerCall, $match);
      $splitMatch[0] = substr($splitMatch[0], 1, strlen($splitMatch[0]) - 1);
      $start = strpos($splitMatch[1], '-');
      $splitMatch[1] = substr(
        $splitMatch[1],
        $start,
        strpos($splitMatch[1], '"') - $start);
      $results[$splitMatch[0]] = $splitMatch[1];
    }
    return $results;
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

  function getBaseUrl($htmlString, $complete = true) {
    $pos = strpos($htmlString, 'baseUrl');
    if (! (false === $pos)) {
      $baseUrl = substr($htmlString, $pos + 9);
      if ($complete) {
        $pos = strpos($baseUrl, '"');
      } else {
        $pos = strpos($baseUrl, '&');
      }
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
