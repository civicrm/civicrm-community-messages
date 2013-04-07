<?php

namespace CRM\CommunityMessagesBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase {

  public function goodRequests() {
    return array(
      array('/alert?prot=1&ver=4.2.1&uf=Drupal&sid=abcd1234abcd1234abcd1234abcd1234'),
      array('/alert?prot=1&ver=4.3.beta5&uf=Drupal6&sid=abcd1234abcd1234abcd1234abcd1234'),
    );
  }

  /**
   * @dataProvider goodRequests
   */
  public function testGoodRequests($goodRequest) {
    $client = static::createClient();
    $client->request('GET', $goodRequest);

    $req = $client->getResponse();
    $this->assertNotEmpty($req->getContent());

    $doc = json_decode($req->getContent(), TRUE);
    $this->assertNotEmpty($doc);

    $this->assertTrue(is_array($doc['messages']));
  }

  public function badRequests() {
    return array(
      array('/alert'),
      array('/alert?prot=2&ver=4.3.1&uf=Drupal&sid=abcd1234abcd1234abcd1234abcd1234'), // bad prot
      array('/alert?prot=1&ver=4.3.zeta&uf=Drupal&sid=abcd1234abcd1234abcd1234abcd1234'), // bad ver
      array('/alert?prot=1&ver=4.3.1&uf=Drupal5&sid=abcd1234abcd1234abcd1234abcd1234'), // bad cms
      array('/alert?prot=1&ver=4.3.1&uf=Drupal&sid=abcd1234abcd1234abcd1234abcd123'), // short sid
      array('/alert?prot=1&ver=4.3.1&uf=Drupal&sid=abcd1234abcd1234abcd1234abcd1234a'), // long sid
    );
  }

  /**
   * @dataProvider badRequests
   */
  public function testBadRequests($badRequest) {
    $client = static::createClient();
    $client->request('GET', $badRequest);

    $req = $client->getResponse();
    $this->assertNotEmpty($req->getContent());

    $doc = json_decode($req->getContent(), TRUE);
    $this->assertNotEmpty($doc);

    $this->assertNotEmpty($doc['error']);
  }
}
