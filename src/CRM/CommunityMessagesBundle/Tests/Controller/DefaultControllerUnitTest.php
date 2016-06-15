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
      array('live' => 'yes', 'cms' => 'drupal'),
      array('uf' => 'Drupal8'),
    );
    $rows[] = array(
      FALSE,
      array('live' => 'yes', 'cms' => 'Wordpress'),
      array('uf' => 'Joomla'),
    );
    return $rows;
  }

  /**
   * @dataProvider getRows
   */
  public function testFilters($expectedResult, $row, $args = array(), $tokens = array()) {
    $mockContainer = $this->getMock("Symfony\Component\DependencyInjection\ContainerInterface");
    $mockApi = $this->getMockBuilder('\civicrm_api3')->disableOriginalConstructor()->getMock();
    $controller = new DefaultController($mockContainer, $mockApi);
    // Row defaults
    $row += array_fill_keys(array('reg', 'mem', 'ver', 'age', 'cms'), '');
    $controller->args = $args;
    $controller->tokens = $tokens;
    $result = $controller->checkFilters($row);
    $this->assertEquals($expectedResult, $result);
  }
}