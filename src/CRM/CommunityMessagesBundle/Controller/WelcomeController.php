<?php
// Testing URL:
// https://alert.civicrm.org/welcome?prot=1&ver=4.6.6&uf=UnitTests&sid=12345678901234567890123456789012&lang=en_US&co=1013
// Change parameters as needed

namespace CRM\CommunityMessagesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class WelcomeController extends Controller {

  protected $requestData;

  public function indexAction() {
    // Parse arguments
    $validations = array(
      'prot' => '/^1$/',
      'sid' => '/^(test_mode|[a-zA-Z0-9]{32})$/',
      'uf' => '/^(Backdrop|Drupal|Drupal6|Drupal8|WordPress|Joomla|UnitTests)$/',
      'ver' => '/^([0-9\.]|alpha|beta|dev|rc){2,12}$/',
      'lang' => '/^([a-z]{2}_[A-Z]{2})/',
    );

    $params = array();
    foreach ($validations as $key => $regex) {
      if (!preg_match($regex, $this->getRequest()->get($key))) {
        $this->createRequestFailure();
        return $this->renderHtml($this->createErrorDocument("Error in $key"));
      }
      $params[$key] = $this->getRequest()->get($key);
    }

    $this->requestData = $params;

    // Log request
    $this->createRequestLog($params);
    $this->updateSidSummary($params['sid'], $params['ver'], $params['uf']);

    // Setup translation
    $this->initGettext($params);

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

  public function initGettext($params) {
    if (empty($params['lang']) || $params['lang'] == 'en_US') {
      return;
    }

    setlocale(LC_ALL, $params['lang'] . '.utf8');
    bindtextdomain("alerts", $_SERVER["DOCUMENT_ROOT"] . "/../l10n/");
    bind_textdomain_codeset('alerts', 'UTF-8');
    textdomain("alerts");
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
    $sections = array(
      $this->ts('Configure and extend') => array(
        'Admin Console' => array(
          'icon' => 'fa-list-ol',
          'img' => 'align-left',
          'html' => '<a href="{crmurl.configbackend}">' . $this->ts('Configuration checklist') . '</a>',
        ),
        'Extensions Dir' => array(
          'icon' => 'fa-puzzle-piece',
          'img' => 'puzzle-piece',
          'html' => '<a href="https://civicrm.org/extensions?src=gs" target="_blank">' . $this->ts('Enhance CiviCRM with extensions') . '</a>',
        ),
        'CiviConnect' => array(
          'icon' => 'fa-plug',
          'img' => 'fullscreen-exit',
          'html' => '<a href="{crmurl.civiconnect}" target="_blank">Manage connected services</a>',
        ),
        'Documentation' => array(
          'icon' => 'fa-book',
          'img' => 'book',
          'html' => '<a href="https://civicrm.org/documentation?src=gs" target="_blank">' . $this->ts('Review CiviCRM documentation') . '</a>',
        ),
      ),
      $this->ts('Get support') => array(
        'Chat' => array(
          'icon' => 'fa-comments-o',
          'img' => 'chat',
          'html' => '<a href="https://chat.civicrm.org?src=gs" target="_blank">' . $this->ts('Jump in and chat with the community') . '</a>',
        ),
        'StackExchange' => array(
          'icon' => 'fa-question',
          'img' => 'question-mark',
          'html' => '<a href="http://civicrm.stackexchange.com" target="_blank">' . $this->ts('Ask a question on Stack Exchange') . '</a>',
        ),
        'Trainings' => array(
          'icon' => 'fa-microphone',
          'img' => 'microphone',
          'html' => '<a href="https://civicrm.org/upcoming-events?src=gs" target="_blank">' . $this->ts('Find upcoming trainings') . '</a>',
        ),
        'Experts' => array(
          'icon' => 'fa-user-md',
          'img' => 'people',
          'html' => '<a href="https://civicrm.org/experts?src=gs " target="_blank">' . $this->ts('Get support from the CiviCRM experts') . '</a>',
        ),
      ),
      $this->ts('Get involved') => array(
        'Register' => array(
          'icon' => 'fa-sign-in',
          'img' => 'monitor',
          'html' => $this->ts('<a %1>Register with</a> or <a %2>log into</a> CiviCRM.org', [
            1 => 'href="https://civicrm.org/user/register?src=gs" target="_blank"',
            2 => 'href="https://civicrm.org/user?src=gs" target="_blank"',
          ]),
        ),
        'Preferences' => array(
          'icon' => 'fa-check-square-o',
          'img' => 'magnifying-glass',
          'html' => '<a href="https://civicrm.org/update-my-mailing-preferences" target="_blank">' . $this->ts('Manage your preferences with CiviCRM') . '</a>',
        ),
        'Register your site' => array(
          'icon' => 'fa-flag',
          'img' => 'flag',
          'html' => '<a href="https://civicrm.org/register-your-site?src=gs&sid=' . $params['sid'] . '" target="_blank">' . $this->ts('Register your site with CiviCRM') . '</a>',
        ),
        'Meetups' => array(
          'icon' => 'fa-users',
          'img' => 'map',
          'html' => '<a href="https://civicrm.org/meet-ups?src=gs" target="_blank">' . $this->ts('Find a meetup in your area') . '</a>',
        ),
        'Become a member' => array(
          'icon' => 'fa-user-plus',
          'img' => 'person',
          'html' => $this->ts('<a %1>Become a member</a> (<a %2>review member benefits</a>)', [
            1 => 'href="https://civicrm.org/become-a-member?src=gs&sid=' . $params['sid'] . '" target="_blank"',
            2 => 'href="http://civicrm.org/member-benefits?src=gs" target="_blank"',
          ]),
        ),
        'Events' => array(
          'icon' => 'fa-calendar',
          'img' => 'clock',
          'html' => '<a href="https://civicrm.org/events?src=gs" target="_blank">' . $this->ts('View all CiviCRM events') . '</a>',
        ),
      ),
    );
    // Disabling CiviConnect until we find a way to create the token
    // in CRM_Dashlet_Page_GettingStarted, lines 49-51
    // and only display entry if token can be replaced
    unset($sections['Configure and extend']['CiviConnect']);

    // Header
    $support_url = 'https://civicrm.org/get-involved?src=gs';
    $donate_url = 'https://civicrm.org/civicrm/contribute/transact?reset=1&id=47&src=gs';

    if (!empty($params['lang']) && $params['lang'] != 'en_US') {
      $donate_url .= '&lcMessages=' . $params['lang'];
    }

    $output = '<div class="crm-block crm-content-block">';
    $output .= "<div id=\"help\">";
    $output .= $this->ts("Created by contributors around the world, CiviCRM is, at its heart, built to impact the world. As open source software, CiviCRM is made freely available for use and improvement, serving organizations whose missions collectively impact and improve the world in which we live. Please <a %1>donate to support the project</a> and see <a %2>other ways to get involved</a>!", [
      '1' => "href='$donate_url' target='_blank'",
      '2' => "href='$support_url' target='_blank'",
    ]);
    $output .= "</div>";

    // Sections
    foreach ($sections as $title => $items) {
      $output .= "<h3>$title</h3><table><tbody>";
      foreach ($items as $item) {
        $output .= "<tr><td width=8>" . $this->iconHtml($item) . "</td><td>$item[html]</td></tr>";
      }
      $output .= "</tbody></table>";
    }
    $output .= "</div>";
    return $output;
  }

  /**
   * @param $item
   * @return string
   */
  public function iconHtml($item) {
    if (version_compare($this->requestData['ver'], 4.7, '<')) {
      $source = $this->getRequest()->getUriForPath("/bundles/crmcommunitymessages/images/open-iconic/{$item['img']}.png");
      return "<img src=\"$source\" alt=\"{$item['img']}\" />";
    }
    else {
      return '<i class="crm-i ' . $item['icon'] . '"></i>';
    }
  }

  public function getSiteStats() {
    $activeSites = 10000;
    $stats = file_get_contents('http://stats.civicrm.org/json/active-sites-stats.json');
    if (!empty($stats)) {
      $stats = reset(json_decode($stats, TRUE));
      if (!empty($stats) && !empty($stats['active_sites'])) {
        $activeSites = $stats['active_sites'];
      }
    }
    return $activeSites;
  }

  /**
   * @param $string
   * @return string
   */
  public function ts($string, $params = []) {
    $string = gettext($string);
    return $this->strarg($string, $params);
  }

  /**
   * Replace arguments in a string with their values. Arguments are represented by % followed by their number.
   *
   * @param string $str
   *   source string.
   *
   * @return string
   *   modified string
   */
  public function strarg($str, $params) {
    if (empty($params)) {
      return $str;
    }

    $tr = [];
    $p = 0;

    foreach ($params as $key => $val) {
      if (is_numeric($key)) {
        $tr['%' . ++$p] = $val;
      }
    }

    return strtr($str, $tr);
  }

}
