<?php
// Testing URL:
// https://alert.civicrm.org/welcome?prot=1&ver=4.6.6&uf=UnitTests&sid=12345678901234567890123456789012&en_US&co=1013
// Change parameters as needed

namespace CRM\CommunityMessagesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class WelcomeController extends Controller {
  public function indexAction() {
    // Parse arguments
    $validations = array(
      'prot' => '/^1$/',
      'sid' => '/^[a-zA-Z0-9]{32}$/',
      'uf' => '/^(Backdrop|Drupal|Drupal6|Drupal8|WordPress|Joomla|UnitTests)$/',
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
        'Admin Console' => array(
          'icon' => 'align-left',
          'html' => '<a href="{crmurl.configbackend}">Configuration checklist</a>',
        ),
        'Extensions Dir' => array(
          'icon' => 'puzzle-piece',
          'html' => '<a href="https://civicrm.org/extensions?src=gs" target="_blank">Enhance CiviCRM with extensions</a>',
        ),
        'CiviConnect' => array(
          'icon' => 'fullscreen-exit',
          'html' => '<a href="{crmurl p=\'a/#/cxn\'}" target="_blank">Manage connected services</a>',
        ),
        'Documentation' => array(
          'icon' => 'book',
          'html' => '<a href="https://civicrm.org/documentation?src=gs" target="_blank">Review CiviCRM documentation</a>',
        ),
      ),
      'Get support' => array(
        'Chat' => array(
          'icon' => 'chat',
          'html' => '<a href="https://chat.civicrm.org?src=gs" target="_blank">Jump in and chat with the community</a>',
        ),
        'StackExchange' => array(
          'icon' => 'question-mark',
          'html' => '<a href="http://civicrm.stackexchange.com" target="_blank">Ask a question on Stack Exchange</a>',
        ),
        'Trainings' => array(
          'icon' => 'microphone',
          'html' => '<a href="https://civicrm.org/upcoming-events?src=gs" target="_blank">Find upcoming trainings</a>',
        ),
        'Experts' => array(
          'icon' => 'people',
          'html' => '<a href="https://civicrm.org/experts?src=gs " target="_blank">Get support from the CiviCRM experts</a>',
        ),
      ),
      'Get involved' => array(
        'Register' => array(
          'icon' => 'monitor',
          'html' => '<a href="https://civicrm.org/user/register?src=gs" target="_blank">Register with</a> or <a href="https://civicrm.org/user?src=gs" target="_blank">log into</a> CiviCRM.org',
        ),
        'Preferences' => array(
          'icon' => 'magnifying-glass',
          'html' => '<a href="https://civicrm.org/update-my-mailing-preferences" target="_blank">Manage your preferences with CiviCRM</a>',
        ),
        'Register your site' => array(
          'icon' => 'flag',
          'html' => '<a href="https://civicrm.org/register-your-site?src=gs&sid='. $params['sid'] .'" target="_blank">Register your site with CiviCRM</a>',
        ),
        'Meetups' => array(
          'icon' => 'map',
          'html' => '<a href="https://civicrm.org/meet-ups?src=gs" target="_blank">Find a meetup in your area</a>',
        ),
        'Become a member' => array(
          'icon' => 'person',
          'html' => '<a href="https://civicrm.org/become-a-member?src=gs&sid='. $params['sid'] .'" target="_blank">Become a member</a> (<a href="http://civicrm.org/member-benefits?src=gs" target="_blank">review member benefits</a>)',
        ),
        'Events' => array(
          'icon' => 'clock',
          'html' => '<a href="https://civicrm.org/events?src=gs" target="_blank">View all CiviCRM events</a>',
        ),
      ),
    );
    // CiviConnect was not available prior to v4.6.6
    if (version_compare($params['ver'], '4.6.6')) {
      unset($sections['Configure and extend']['CiviConnect']);
    }
    
    $activeSites = $this->getSiteStats();
    // Header
    $output = '<div class="crm-block crm-content-block">';
    $activeSites = number_format($activeSites);
    $output .= "<div id=\"help\">Used by over <b>$activeSites</b> organizations, CiviCRM is developed and maintained by a growing community of contributors. We welcome your support and encourage you to get involved!</div>";

    // Sections
    foreach ($sections as $title => $items) {
      $output .= "<h3>$title</h3><table><tbody>";
      foreach ($items as $item) {
        $output .= "<tr><td width=8>".$this->iconHtml($assets, $item['icon'], 8)."</td><td>$item[html]</td></tr>";
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
