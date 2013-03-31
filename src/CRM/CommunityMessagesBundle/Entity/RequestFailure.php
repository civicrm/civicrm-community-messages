<?php

namespace CRM\CommunityMessagesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RequestFailure
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class RequestFailure {
  /**
   * @var integer
   *
   * @ORM\Column(name="id", type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  private $id;

  /**
   * @var integer
   *
   * @ORM\Column(name="ts", type="integer")
   */
  private $ts;

  /**
   * @var string
   *
   * IPv4 ==> (4*4)-1 chars
   * IPv6 ==> (8*5)-1 chars
   *
   * There's some suggestion that IPv4 and IPv6 addresses
   * can be combined. Ceiling: 54 chars
   *
   * @ORM\Column(name="ip", type="string", length=64)
   */
  private $ip;

  /**
   * @var string
   *
   * @ORM\Column(name="queryString", type="string", length=256)
   */
  private $queryString;

  /**
   * Get id
   *
   * @return integer
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Set ts
   *
   * @param integer $ts
   * @return RequestFailure
   */
  public function setTs($ts) {
    $this->ts = $ts;

    return $this;
  }

  /**
   * Get ts
   *
   * @return integer
   */
  public function getTs() {
    return $this->ts;
  }

  /**
   * @param string $ip
   * @return RequestFailure
   */
  public function setIp($ip) {
    $this->ip = $ip;

    return $this;
  }

  /**
   * @return string
   */
  public function getIp() {
    return $this->ip;
  }

  /**
   * Set uri
   *
   * @param string $queryString
   * @return RequestFailure
   */
  public function setQueryString($queryString) {
    $this->queryString = substr($queryString, 0, 256);

    return $this;
  }

  /**
   * Get sid
   *
   * @return string
   */
  public function getQueryString() {
    return $this->queryString;
  }

}
