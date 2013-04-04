<?php
/**
 * @package Moar\Metrics
 */

namespace Moar\Metrics;


/**
 * @package Moar\Metrics
 * @copyright 2013 Bryan Davis and contributors. All Rights Reserved.
 */
class MetricdClientTest extends \PHPUnit_Framework_TestCase {

  /**
   * Given a metricd client pointed at a non-routable address
   * When send() is called
   * Then no exception is thrown
   */
  public function testSendToBadAddress () {
    $m = new MetricdClient('169.254.0.1');
    $m->send(array('foo' => '1|c'));
    $this->assertTrue(true);
  }

  /**
   * Given a metricd client
   * When send() is called
   *  and NULL is given for the metrics
   * Then no exception is thrown
   */
  public function testSendNullMetrics () {
    $m = new MetricdClient('169.254.0.1');
    $m->send(null);
    $this->assertTrue(true);
  }

  /**
   * Given a metricd client
   * When send() is called
   *  and a string is given for the metrics
   * Then no exception is thrown
   */
  public function testSendStringMetrics () {
    $m = new MetricdClient('169.254.0.1');
    $m->send("wrong");
    $this->assertTrue(true);
  }

  /**
   * Given a metricd client
   * When send() is called
   *  and a string is given for the extraMeta
   * Then no exception is thrown
   */
  public function testSendStringMeta () {
    $m = new MetricdClient('169.254.0.1');
    $m->send(array('foo' => '1|c'), "wrong");
    $this->assertTrue(true);
  }

  /**
   * Given a metricd client
   * When send() is called
   *  and an array with a circular reference is given for the metrics
   * Then no exception is thrown
   */
  public function testSendCircularMetrics () {
    $m = new MetricdClient('169.254.0.1');
    $bad = array('foo' => '1|c');
    $bad['bar'] = &$bad;
    $m->send($bad, "wrong");
    $this->assertTrue(true);
  }

} //end MetricdClient
