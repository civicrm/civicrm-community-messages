<?php

namespace CRM\CommunityMessagesBundle\Tests\Controller;

use CRM\CommunityMessagesBundle\Controller\DefaultController;

require_once 'src/civicrm/api3.php';

class DefaultControllerUnitTest extends \PHPUnit_Framework_TestCase {

  public function getRows() {
    $rows = array();
    $rows[] = array(
      TRUE,
      array('live' => 'yes'),
    );
    $rows[] = array(
      FALSE,
      array('live' => 'nonsense'),
    );
    $rows[] = array(
      FALSE,
      array('live' => 'yes', 'reg' => 'yes'),
    );
    $rows[] = array(
      FALSE,
      array('live' => 'yes', 'mem' => 'never'),
      array(),
      array('membership_id' => 2),
    );
    $rows[] = array(
      FALSE,
      array('live' => 'yes', 'ver' => '< 4.5'),
      array('ver' => '4.6.5'),
    );
    $rows[] = array(
      TRUE,
      array('live' => 'yes', 'ver' => '>= 4.6'),
      array('ver' => '4.6.0'),
    );
    $rows[] = array(
      TRUE,
      array('live' => 'yes', 'ver' => '>=4.6'),
      array('ver' => '4.6.1'),
    );
    $rows[] = array(
      TRUE,
      array('live' => 'yes', 'ver' => '= 4.6'),
      array('ver' => '4.6.11'),
    );
    $rows[] = array(
      TRUE,
      array('live' => 'yes', 'ver' => '4.6'),
      array('ver' => '4.6.11'),
    );
    $rows[] = array(
      TRUE,
      array('live' => 'yes', 'ver' => '== 4.6.0'),
      array('ver' => '4.6.0.alpha1'),
    );
    $rows[] = array(
      TRUE,
      array('live' => 'yes', 'cms' => 'drupal'),
      array('uf' => 'Drupal8'),
    );
    $rows[] = array(
      FALSE,
      array('live' => 'yes', 'cms' => 'Wordpress'),
      array('uf' => 'Joomla'),
    );
    $rows[] = array(
      TRUE,
      array('live' => 'yes', 'type' => 'offers'),
    );
    $rows[] = array(
      FALSE,
      array('live' => 'yes', 'type' => 'offers'),
      array('optout' => 'offers,events'),
    );
    $rows[] = array(
      FALSE,
      // "offer" and "offers" should both work - it should ignore the trailing "s"
      array('live' => 'yes', 'type' => 'offer'),
      array('optout' => 'offers'),
    );
    $rows[] = array(
      TRUE,
      array('live' => 'yes', 'type' => 'events'),
      array('optout' => 'offers,asks'),
    );
    $rows[] = array(
      TRUE,
      array('live' => 'yes', 'age' => '>1 week'),
      array(),
      array(),
      array('created' => strtotime('now - 2 weeks')),
    );
    $rows[] = array(
      TRUE,
      array('live' => 'yes', 'age' => '> 1 month'),
      array(),
      array(),
      array('created' => strtotime('now - 2 months')),
    );
    $rows[] = array(
      FALSE,
      array('live' => 'yes', 'age' => '< 1 week'),
      array(),
      array(),
      array('created' => strtotime('now - 2 weeks')),
    );
    $rows[] = array(
      TRUE,
      array('live' => 'yes', 'country' => 'GB'),
      array('co' => '1226'),
    );
    $rows[] = array(
      FALSE,
      array('live' => 'yes', 'country' => 'GB, UM, US'),
      array('co' => '1225'),
    );
    $rows[] = array(
      TRUE,
      array('live' => 'yes', 'country' => 'Europe, Americas'),
      array('co' => '1226'),
    );
    return $rows;
  }

  /**
   * @dataProvider getRows
   */
  public function testFilters($expectedResult, $row, $args = array(), $tokens = array(), $sidSummary = array()) {
    $mockContainer = $this->getMock("Symfony\Component\DependencyInjection\ContainerInterface");
    $mockApi = $this->getMockBuilder('\civicrm_api3')->disableOriginalConstructor()->getMock();
    $controller = new DefaultController($mockContainer, $mockApi);
    // Row defaults
    $row += array_fill_keys(array('reg', 'mem', 'ver', 'age', 'cms', 'type', 'country'), '');
    $controller->args = $args + array('sid' => 123);
    $controller->tokens = $tokens;
    $controller->sidSummary = $sidSummary + array('sid' => 123);
    $result = $controller->checkFilters($row);
    $this->assertEquals($expectedResult, $result);
  }

}
