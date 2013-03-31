<?php

namespace CRM\CommunityMessagesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SidSummary
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class SidSummary {
  /**
   * @var integer
   *
   * @ORM\Column(name="sid", type="string", length=32)
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="NONE")
   */
  private $sid;

  /**
   * @var integer
   *
   * @ORM\Column(name="created", type="integer")
   */
  private $created;

  /**
   * @var integer
   *
   * @ORM\Column(name="modified", type="integer")
   */
  private $modified;

  /**
   * @var integer
   *
   * @ORM\Column(name="requests", type="integer")
   */
  private $requests;

  /**
   * @var string
   *
   * @ORM\Column(name="firstVer", type="string", length=32)
   */
  private $firstVer;

  /**
   * @var string
   *
   * @ORM\Column(name="lastVer", type="string", length=32)
   */
  private $lastVer;

  /**
   * @var string
   *
   * @ORM\Column(name="firstUf", type="string", length=16)
   */
  private $firstUf;

  /**
   * @var string
   *
   * @ORM\Column(name="lastUf", type="string", length=16)
   */
  private $lastUf;

  /**
   * Set sid
   *
   * @param string $sid
   * @return SidSummary
   */
  public function setSid($sid) {
    $this->sid = $sid;
    return $this;
  }

  /**
   * Get sid
   *
   * @return string
   */
  public function getSid() {
    return $this->sid;
  }

  /**
   * Set created
   *
   * @param integer $created
   * @return SidSummary
   */
  public function setCreated($created) {
    $this->created = $created;

    return $this;
  }

  /**
   * Get ts
   *
   * @return integer
   */
  public function getCreated() {
    return $this->created;
  }

  /**
   * Set modified
   *
   * @param integer $modified
   * @return SidSummary
   */
  public function setModified($modified) {
    $this->modified = $modified;

    return $this;
  }

  /**
   * Get ts
   *
   * @return integer
   */
  public function getModified() {
    return $this->modified;
  }

  /**
   * @param int $requests
   * @return SidSummary
   */
  public function setRequests($requests) {
    $this->requests = $requests;

    return $this;
  }

  /**
   * @return int
   */
  public function getRequests() {
    return $this->requests;
  }


  /**
   * Set ver
   *
   * @param string $ver
   * @return SidSummary
   */
  public function setFirstVer($ver) {
    $this->firstVer = $ver;

    return $this;
  }

  /**
   * Get ver
   *
   * @return string
   */
  public function getFirstVer() {
    return $this->firstVer;
  }

  /**
   * Set ver
   *
   * @param string $ver
   * @return SidSummary
   */
  public function setLastVer($ver) {
    $this->lastVer = $ver;

    return $this;
  }

  /**
   * Get ver
   *
   * @return string
   */
  public function getLastVer() {
    return $this->lastVer;
  }

  /**
   * Set uf
   *
   * @param string $uf
   * @return SidSummary
   */
  public function setFirstUf($uf) {
    $this->firstUf = $uf;

    return $this;
  }

  /**
   * Get uf
   *
   * @return string
   */
  public function getFirstUf() {
    return $this->firstUf;
  }

  /**
   * Set uf
   *
   * @param string $uf
   * @return SidSummary
   */
  public function setLastUf($uf) {
    $this->lastUf = $uf;

    return $this;
  }

  /**
   * Get uf
   *
   * @return string
   */
  public function getLastUf() {
    return $this->lastUf;
  }

}
