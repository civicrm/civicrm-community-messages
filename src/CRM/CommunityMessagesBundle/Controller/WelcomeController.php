<?php

namespace CRM\CommunityMessagesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class WelcomeController extends Controller {
  public function indexAction() {
    // Parse arguments
    $validations = array(
      'prot' => '/^1$/',
      'sid' => '/^[a-zA-Z0-9]{32}$/',
      'uf' => '/^(Drupal|Drupal6|WordPress|Joomla|UnitTests)$/',
      'ver' => '/^([0-9\.]|alpha|beta|dev|rc){2,12}$/',
    );

    $params = array();
    foreach ($validations as $key => $regex) {
      if (!preg_match($regex, $this->getRequest()->get($key))) {
        $this->createRequestFailure();
        return $this->renderHtml($this->createErrorDocument("Error in $key"));
      }
      $params[$key] = $this->getRequest()->get($key);
    }

    // Log request
    $this->createRequestLog($params);
    $this->updateSidSummary($params['sid'], $params['ver'], $params['uf']);

    // Construct response
    $document = $this->gettingStarted($params);
    return $this->renderHtml($document);
  }

  public function createErrorDocument($message) {
    // From client's perspective, this is an invalid document. It will be
    // discarded (and eventually client will retry).
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

  public function renderHtml($document) {
    $response = new \Symfony\Component\HttpFoundation\Response($document);
    $response->headers->set('Content-Type', 'text/html');
    return $response;

  }
  
  public function gettingStarted($params) {
    $assets = $this->getRequest()->getUriForPath('/bundles/crmcommunitymessages');
    $sections = array(
      'Configure and extend' => array(
          'book' => '<a href="http://civicrm.org/documentation?src=gs" target="_blank">Documentation</a>',
          'align-left' => '<a href="{crmurl.configbackend}">Configuration checklist</a>',
          'puzzle-piece' => '<a href="http://civicrm.org/extensions?src=gs" target="_blank">Extensions directory</a>',
         ),
      'Get help' => array(
          'question-mark' => '<a href="http://civicrm.org/ask-a-question?src=gs" target="_blank">Ask a question</a>',
          'microphone' => '<a href="http://civicrm.org/upcoming-events?src=gs" target="_blank">Upcoming training and events</a>',
          'people' => '<a href="http://civicrm.org/experts?src=gs " target="_blank">Get expert help</a>',
        ),
      'Join the community' => array(
          'flag' => '<a href="http://civicrm.org/register-your-site?src=gs&sid='. $params['sid'] .'" target="_blank">Register your site</a>',
          'heart' => '<a href="http://civicrm.org/become-a-member?src=gs&sid='. $params['sid'] .'" target="_blank">Become a member</a>',
        ),
    );
    
    $activeSites = $this->getSiteStats();
    // Header
    $output = '<div class="crm-block crm-content-block">';
    $activeSites = number_format($activeSites);
    $output .= "<div id=\"help\">New to the CiviCRM community? Welcome! You've joined <b>$activeSites</b> other organizations that actively use CiviCRM to do a world of good. Here are a few resources to help you get started.</div>";

    // Sections
    foreach ($sections as $title => $items) {
      $output .= "<h3>$title</h3><table><tbody>";
      foreach ($items as $icon => $html) {
        $output .= "<tr><td width=8>".$this->iconHtml($assets, $icon, 8)."</td><td>$html</td></tr>";
      }
      $output .= "</tbody></table>";
    }
    $output .= "</div>";
    return $output;
  }

  public function iconHtml($assets, $icon, $size) {
    $source = "{$assets}/images/open-iconic/{$icon}.png";
    return "<img src=\"$source\" alt=\"$icon\" />";
  }
  
  public function getSiteStats() {
    $activeSites = 10000;
    $stats = file_get_contents('http://stats.civicrm.org/json/active-sites-stats.json');
    if(!empty($stats)) {
      $stats = reset(json_decode($stats, true));
      if (!empty($stats) && !empty($stats['active_sites'])) {
        $activeSites = $stats['active_sites'];
      }
    }
    return $activeSites;
  }
}
