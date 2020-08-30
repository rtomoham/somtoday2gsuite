<?php

include 'Utils.php';
include_once 'HtmlParser.php';
include_once 'SomtodayUser.php';
require_once 'HTTP/Request2.php';

class SomtodayWebBroker {

  // curl handle to make all the requests to the mijnklntb website
  private $curl;
  // this string will hold the responses to the curl exec calls
  private $response;
  private const FILENAME_COOKIE = PATH . 'cookies.txt';
  private const KEYWORD_COOKIEWALL = 'CookiePurposes_0_';
  private const KEYWORD_LOGIN = 'form_login';
  private const KEYWORD_PLAYER_PROFILE = 'player-profile';
  private const KEYWORD_REQUEST_VERIFICATION_TOKEN =
  '__RequestVerificationToken';
  private const URL_PLAYER_PROFILE =
  'https://mijnknltb.toernooi.nl/player-profile/';
  private const URL_COOKIEWALL_SAVE =
  'https://mijnknltb.toernooi.nl/cookiewall/Save';
  private const URL_BASE = 'https://somtoday.nl/';
  private const URL_SCHOOL = '?-1.-panel-organisatieSelectionForm&nextLink=x';

  // the username and password to access the somtoday website
  private $username;
  private $password;

  private $htmlParser;

  function __construct($somtodayUser) {
    $this->username = $somtodayUser->getLogin();
    $this->password = $somtodayUser->getPassword();
    $this->curl = curl_init();
    $this->htmlParser = HtmlParser::getInstance();

    // Let's use cookies
    curl_setopt($this->curl, CURLOPT_COOKIEJAR, self::FILENAME_COOKIE);
    curl_setopt($this->curl, CURLOPT_COOKIEFILE, self::FILENAME_COOKIE);

    // CURLOPT_VERBOSE: TRUE to output verbose information. Writes output to
    // STDERR, or the file specified using CURLOPT_STDERR.
    curl_setopt($this->curl, CURLOPT_VERBOSE, true);

    $verbose = fopen('curl-output.txt', 'w+');
    curl_setopt($this->curl, CURLOPT_STDERR, $verbose);
  }

  function __destruct() {
    curl_close($this->curl);
  }
  function getAugustinianumElo() {
    return $this->makeGetRequest('https://augustinianum-elo.somtoday.nl/', NULL);
  }
  
  function getStudent($studentIndex) {
    $baseUrl = $this->htmlParser->getBaseUrl($this->response);
    $this->makeGetRequest(
      'https://augustinianum-elo.somtoday.nl/' . 
      $baseUrl . 
      '-1.0-leerling-' . 
      $studentIndex . 
      '-selectLeerling', 
      'https://augustinianum-elo.somtoday.nl/' . 
      $baseUrl);
  }
  
  function getRoster() {
    $this->makeGetRequest('https://augustinianum-elo.somtoday.nl/home/roster', NULL);
    $classes = $this->htmlParser->getClasses($this->response);
    
    // Create a new DateTime object
    $nextMonday = new DateTime();
    // Modify the date it contains
    $nextMonday->modify('next monday');
    // Output
    echo $nextMonday->format('d-m-Y');
    
    $this->makeGetRequest('https://augustinianum-elo.somtoday.nl/home/roster?datum=' . $nextMonday->format('d-m-Y'), NULL);
    $classes = array_merge($classes, $this->htmlParser->getClasses($this->response));
    
    return $classes;
  }
  
  function getResponse() {
    return $this->response;
  }
  
  function redirectToAug() {
    $action = $this->htmlParser->getAction($this->response);
    $saml = $this->htmlParser->getSAMLResponse($this->response);
    return $this->makeHttpRequest($action, self::URL_BASE, true, 'SAMLResponse=' . rawurlencode($saml));
  }
  
  function selectSchool($somtodayUser) {
    return $this->makeHttpRequest(self::URL_BASE . self::URL_SCHOOL, self::URL_BASE, true, 'organisatieSearchField--selected-value-1=' . 
    $somtodayUser->getSchoolId() . 
    '&organisatieSearchFieldPanel:organisatieSearchFieldPanel_body:organisatieSearchField=' . $somtodayUser->getSchoolName());
  }
  
  function setUsername($action, $auth, $somtodayUser) {
    $username = $somtodayUser->getUsername();
    return $this->makeHttpRequest(
      self::URL_BASE . $action . '&loginLink=x', SELF::URL_BASE . '?' . $auth, 
      true, 'usernameFieldPanel:usernameFieldPanel_body:usernameField=' . rawurlencode($username));
  }
  
  function setPassword($action, $somtodayUser) {
    $username = $somtodayUser->getUsername();
    $password = $somtodayUser->getPassword();
    $schoolName = $somtodayUser->getSchoolName();
    $schoolId = $somtodayUser->getSchoolId();
    return $this->makeHttpRequest(self::URL_BASE . $action . '&loginLink=x', SELF::URL_BASE . 'login?1', true, 
      'organisatieSearchField--selected-value-1=' . $schoolId . '&' . 
      'organisatieSearchFieldPanel:organisatieSearchFieldPanel_body:organisatieSearchField=' . $schoolName . '&' . 
      'usernameFieldPanel:usernameFieldPanel_body:usernameField=' . rawurlencode($username) . '&' . 
      'passwordFieldPanel:passwordFieldPanel_body:passwordField=' . $password);
  }

  private function makeHttpRequestOLD($url, $referer, $isPostRequest, $payload) {
    $request = new HTTP_Request2();
    $request->setUrl($url);
    $request->setMethod(HTTP_Request2::METHOD_POST);
    $request->setConfig(array(
      'follow_redirects' => TRUE
    ));
    $request->setHeader(array(
//      'Origin' => 'https://somtoday.nl',
//      'Referer' => $referer,
      'Sec-Fetch-Dest' => 'document',
      'Sec-Fetch-Mode' => 'navigate',
      'Sec-Fetch-Site' => 'same-origin',
      'Sec-Fetch-User' => '?1',
      'Content-Type' => 'application/x-www-form-urlencoded'
    ));
    $request->addPostParameter($payload);
    
    echo ('*** REQUEST ***');
    var_dump($request);
    
    try {
      $this->response = $request->send();
      echo '*** RESPONSE ***';
      var_dump($this->response);
      if ($this->response->getStatus() == 200) {
        echo 'Response: ' . $this->response->getBody();
      }
      else {
        echo 'Unexpected HTTP status: ' . $this->response->getStatus() . ' ' .
        $this->response->getReasonPhrase();
      }
    }
    catch(HTTP_Request2_Exception $e) {
      echo 'Error: ' . $e->getMessage();
    }
  }

  private function makeHttpRequest($url, $referer, $isPostRequest, $payload) {
    
    var_dump($url);

    $httpHeader = array(
//      "Origin: https://somtoday.nl",
      "Referer: $referer",
      "Sec-Fetch-Dest: document",
      "Sec-Fetch-Mode: navigate",
      "Sec-Fetch-Site: same-origin",
      "Sec-Fetch-User: ?1",
      "Content-Type: application/x-www-form-urlencoded" 
    );

    curl_setopt_array($this->curl, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//          CURLOPT_CUSTOMREQUEST => "POST",
      // Have to use the "old" non-compliant CURLOPT_POST, due to redirect from
      // POST to GET by mijnknltb.toernooi.nl
      CURLOPT_POST => $isPostRequest,
      CURLOPT_HTTPHEADER => $httpHeader
    ));

    if (!is_null($payload)) {
      curl_setopt($this->curl, CURLOPT_POSTFIELDS, $payload);
    }

    $this->response = curl_exec($this->curl);
    return $this->response;
  }

  private function makeGetRequest($url, $referer) {
    // $referer is needed to select student (after initial login to the school site)
    
    var_dump($url);

    $httpHeader = array(
      "Referer: $referer",
      "Sec-Fetch-Dest: empty",
      "Sec-Fetch-Mode: cors",
      "Sec-Fetch-Site: same-origin",
      "Content-Type: application/x-www-form-urlencoded" 
    );

    curl_setopt_array($this->curl, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//          CURLOPT_CUSTOMREQUEST => "POST",
      // Have to use the "old" non-compliant CURLOPT_POST, due to redirect from
      // POST to GET by mijnknltb.toernooi.nl
      CURLOPT_POST => false,
      CURLOPT_HTTPHEADER => $httpHeader
    ));

    $this->response = curl_exec($this->curl);

    var_dump($this->response);
    
    return  $this->response;
  }

} ?>
