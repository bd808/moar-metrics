<?php
/**
 * @package Moar\Metrics
 */

namespace Moar\Metrics;

/**
 * @package Moar\Metrics
 * @copyright 2013 Bryan Davis and contributors. All Rights Reserved.
 */
class TrackTest extends \PHPUnit_Framework_TestCase {

  public function test_basic_operations () {
    Track::start('test.elapsed');
    Track::inc('foo.samples');
    Track::inc('bar.samples');
    Track::count('foo.samples', 1);
    Track::dec('foo.samples');
    $r = Track::report();

    $this->assertArrayHasKey('foo.samples', $r);
    $this->assertEquals('1|c', $r['foo.samples']);

    $this->assertArrayHasKey('bar.samples', $r);
    $this->assertEquals('1|c', $r['bar.samples']);

    $this->assertArrayHasKey('test.elapsed', $r);
    $this->assertRegExp('/\d+\|ms/', $r['test.elapsed']);
  }

} //end TrackTest
