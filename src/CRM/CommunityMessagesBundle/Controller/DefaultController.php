<?php

namespace CRM\CommunityMessagesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DefaultController extends Controller {

  const CONTENT_SOURCE = 'https://docs.google.com/spreadsheets/d/1OnJXtxTaS3FfQRMHLffPETdDKk3OHmd1fxLc8zQt9PE/pub?gid=0&single=true&output=csv';

  /**
   * @var \civicrm_api3
   */
  public $api;

  /**
   * @var array
   */
  public $args = array();

  /**
   * @var array
   */
  public $tokens = array();

  /**
   * @var bool
   */
  public $isTest = FALSE;

  /**
   * @var string
   */
  public $tplFile = 'CRMCommunityMessagesBundle:Default:tips.html.twig';

  /**
   * @var array
   */
  public $membershipStatuses = array(
    1 => 'New',
    2 => 'Current',
    3 => 'Grace',
    4 => 'Expired',
    5 => 'Pending',
    6 => 'Cancelled',
    7 => 'Deceased',
  );

  /**
   * Mapping between string in the csv file and allowed statuses
   */ 
  public $statusRules = array(
    "yes" => array('New', 'Current', 'Grace'),
    "new" => array('New'),
    "expiring" => array('Current'),
    "grace" => array('Grace'),
    "past" => array('Expired', 'Cancelled', 'Deceased'),
  );

  function __construct(ContainerInterface $container, \civicrm_api3 $api) {
    $this->setContainer($container);
    $this->api = $api;
  }

  public function indexAction() {
    $this->isTest = $this->getRequest()->get('sid') == 'test_mode';

    try {
      $this->getArguments();
    } catch (\Exception $e) {
      $this->createRequestFailure();
      return $this->renderJson($this->createErrorDocument($e->getMessage()));
    }

    // Log request
    $this->createRequestLog($this->args);
    $this->updateSidSummary($this->args['sid'], $this->args['ver'], $this->args['uf']);

    // Lookup requester
    $this->getOrgTokens();

    // Construct response

    $document = array(
      // Expire in 1 day in normal mode, or 1 second in test mode
      'ttl' => $this->isTest ? 1 : 24 * 60 * 60,
      'retry' => 1.5 * 60 * 60, // 1.5 hours
      'messages' => array(),
    );

    list($lang) = explode('_', $this->args['lang']);

    $fileName = $this->getContent();

    if ($fileName) {
      // Iterate through each line in the file
      foreach ($this->getAssocCSV($fileName) as $row) {
        if (!$this->checkFilters($row)) {
          continue;
        }
        $row['content'] = empty($row[$lang]) ? $row['en'] : $row[$lang];
        $data = $this->formatContent($row);
        $item = array('markup' => $this->renderView($this->tplFile, $data));
        // Send clientside filters
        foreach (array('perms', 'components') as $field) {
          if ($row[$field]) {
            $item[$field] = explode(',', str_replace(', ', ',', $row[$field]));
          }
        }
        $document['messages'][] = $item;
      }
    }

    return $this->renderJson($document);
  }

  /**
   * Check if a message is relevant based on filters
   *
   * @param $row
   * @return bool
   */
  public function checkFilters($row) {
    // Skip disabled messages
    if ($row['live'] === 'yes') {
      // Server-side filters
      if (($row['reg'] === 'yes' && empty($this->tokens)) || ($row['reg'] === 'no' && !empty($this->tokens))) {
        return FALSE;
      }
      if ($row['mem'] === 'never') {
        if (!empty($this->tokens['membership_id'])) {
          return FALSE;
        }
      }
      elseif ($row['mem']) {
        if (empty($this->tokens['membership_id']) || !in_array($this->tokens['membership_status'], $this->statusRules[$row['mem']])) {
          return FALSE;
        }
        if ($row['mem'] === 'expiring' && $this->tokens['membership_end_date'] > date('Y-m-d', strtotime('now + 1 month'))) {
          return FALSE;
        }
      }
      if ($row['age']) {
        list ($op, $unit) = explode(' ', $row['age'], 2);
        $diff = strtotime("now - $unit");
        $summary = $this->getSidSummary($this->args['sid']);
        if (eval("return {$summary['created']} $op $diff;")) {
          return FALSE;
        }
      }
      if ($row['ver']) {
        $matches = array();
        preg_match('/([^\d]*)(.*)/', $row['ver'], $matches);
        list (, $op, $filterVersion) = $matches;
        // If omitted, operator defaults to '=='
        $op = trim($op) ? trim($op) : '==';
        // Trim client version to sigFigs given so we accurately compare 4.x with 4.x.x
        $sigFigs = substr_count($filterVersion, '.') + 1;
        $clientVersion = implode('.', array_slice(explode('.', $this->args['ver']), 0, $sigFigs));
        if (!version_compare($clientVersion, $filterVersion, $op)) {
          return FALSE;
        }
      }
      if ($row['cms']) {
        if (strpos(strtolower($this->args['uf']), strtolower($row['cms'])) !== 0) {
          return FALSE;
        }
      }
      if (!empty($row['type']) && !empty($this->args['optout'])) {
        $optOut = array_map('trim', explode(',', $this->args['optout']));
        // We correct for a missing trailing "s" so the spreadsheet is more forgiving
        // Typing "offers" or "offer" into the "type" column will both work.
        if (in_array($row['type'], $optOut) || in_array($row['type'] . 's', $optOut)) {
          return FALSE;
        }
      }
    }
    else {
      // Skip non-live messages except for test messages in test mode
      if (!($row['live'] === 'test' && $this->isTest)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Format content for template rendering
   *
   * @param $row
   * @return array
   */
  public function formatContent($row) {
    // Add link
    if ($row['url']) {
      $row['content'] = str_replace('[[', '<a target="_blank" href="' . $row['url'] . '">', $row['content']);
      $row['content'] = str_replace(']]', '</a>', $row['content']);
    }

    // Subtitute server-side tokens now that the link is part of the content
    $vars = array();
    foreach ($this->tokens as $k => $v) {
      $vars['%%' . $k . '%%'] = $v;
      $vars['{{' . $k . '}}'] = urlencode($v);
    }
    $row['content'] = strtr($row['content'], $vars);

    list(, $title, $body) = explode('**', $row['content']);

    return array('title' => $title, 'body' => $body);
  }

  public function getOrgTokens() {
    $params = array(
      'sequential' => 1,
      'custom_193' => $this->args['sid'],
      'return' => 'display_name',
      'api.Membership.get' => array(
        'membership_type_id' => array('IN' => array(4, 5, 6, 7, 8, 9)),
        'options' => array('sort' => 'start_date DESC', 'limit' => 1),
      ),
    );
    if ($this->api->Contact->get($params)) {
      foreach ($this->api->values as $contact) {
        $this->tokens['display_name'] = $contact['display_name'];
        $this->tokens['contact_id'] = $contact['id'];
        if (!empty($contact['api.Membership.get']) && isset($contact['api.Membership.get']['id'])) {
          $membership = $contact['api.Membership.get']['values'][0];
          $this->tokens['membership_id'] = $membership['id'];
          $this->tokens['membership_start_date'] = $membership['start_date'];
          $this->tokens['membership_end_date'] = $membership['end_date'];
          $this->tokens['membership_status_id'] = $membership['status_id'];
          $this->tokens['membership_status'] = $this->membershipStatuses[$membership['status_id']];
        }
        break;
      }
    }
  }

  /**
   * From client's perspective, this is an invalid document. It will be
   * discarded (and eventually client will retry).
   *
   * @param $message
   * @return array
   */
  public function createErrorDocument($message) {
    return array(
      'error' => $message,
    );
  }

  public function createRequestFailure() {
    $log = new \CRM\CommunityMessagesBundle\Entity\RequestFailure();
    $log->setTs(time());
    $log->setIp($this->getRequest()->getClientIp());
    $log->setQueryString($this->getRequest()->getQueryString());
    $em = $this->getDoctrine()->getManager();
    $em->persist($log);
    $em->flush();
    return $log;
  }

  public function createRequestLog($params) {
    $log = new \CRM\CommunityMessagesBundle\Entity\RequestLog();
    $log->setTs(time());
    $log->setIp($this->getRequest()->getClientIp());
    $log->setProt((int) $params['prot']);
    $log->setSid($params['sid']);
    $log->setUf($params['uf']);
    $log->setVer($params['ver']);
    $em = $this->getDoctrine()->getManager();
    $em->persist($log);
    $em->flush();
    return $log;
  }

  public function updateSidSummary($sid, $ver, $uf) {
    $cxn = $this->getDoctrine()->getConnection();
    $cxn->executeUpdate('
      INSERT INTO SidSummary (sid, requests, created, modified, firstVer, firstUf, lastVer, lastUf)
      VALUES (:sid, 1, :now, :now, :ver, :uf, :ver, :uf)
      ON DUPLICATE KEY UPDATE
        modified = :now,
        lastVer = :ver,
        lastUf = :uf,
        requests = requests + 1
    ', array(
      'sid' => $sid,
      'ver' => $ver,
      'uf' => $uf,
      'now' => time(),
    ));
  }

  /**
   * @param $sid
   * @return array
   */
  public function getSidSummary($sid) {
    $cxn = $this->getDoctrine()->getConnection();
    $result = $cxn->executeQuery('SELECT * FROM SidSummary WHERE sid = :sid', array('sid' => $sid));
    foreach ($result as $row) {
      return $row;
    }
  }

  public function renderJson($document) {
    $response = new \Symfony\Component\HttpFoundation\Response(json_encode($document));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

  /**
   * Transforms a csv into an associative array, with the header row as keys
   *
   * @param string $fileName
   * @return array
   */
  public function getAssocCSV ($fileName) {
    $file = fopen($fileName, 'r');
    $data = array();
    while ($row = fgetcsv($file)) {
      if (!isset($head)) {
        $head = array_map('trim', $row);
      }
      else {
        $data[] = array_combine($head, array_map('trim', $row));
      }
    }
    fclose($file);
    return $data;
  }

  /**
   * Collect and validate arguments
   *
   * @throws \Exception
   */
  public function getArguments() {
    $validations = array(
      'prot' => '/^1$/',
      'sid' => '/^[a-zA-Z0-9]{32}$/',
      'uf' => '/^(Backdrop|Drupal|Drupal6|Drupal8|WordPress|Joomla|UnitTests)$/',
      'ver' => '/^([0-9\.]|alpha|beta|dev|rc){2,12}$/',
      'lang' => '/^[a-z]+_[A-Z]+$/',
    );
    $defaults = array(
      'lang' => 'en_US',
      'optout' => '',
    );

    foreach ($validations as $key => $regex) {
      if (!$this->isTest && !preg_match($regex, $this->getRequest()->get($key))) {
        if (isset($defaults[$key])) {
          $this->args[$key] = $defaults[$key];
        }
        else {
          throw new \Exception("Error in $key");
        }
      }
      else {
        $this->args[$key] = $this->getRequest()->get($key);
      }
    }
  }

  /**
   * Downloads content file and caches it for an hour
   * @return string
   */
  private function getContent() {
    $fs = $this->get('filesystem');
    $dir = $this->container->getParameter('kernel.cache_dir') . '/community_msg';
    $fileName = $dir . '/content.csv';
    // Test mode forces immediate refresh
    $cacheTime = $this->isTest ? time() : strtotime('now - 1 hour');

    if (!$fs->exists($dir)) {
      $fs->mkdir($dir);
    }

    if (!$fs->exists($fileName) || filemtime($fileName) < $cacheTime) {
      file_put_contents($fileName, fopen(self::CONTENT_SOURCE, 'r'));
    }

    return $fileName;
  }
}
