<?php
/**
 * @package Moar\Metrics
 */

namespace Moar\Metrics;

use Moar\Metrics\Track;


/**
 * Lightweight class for a scope based timer.
 *
 * Starts Moar\Metrics\Track on construction and stops timer on destruction.
 * Useful when tracking method execution duration where the method has
 * multiple exit points or does significant processing in the return
 * statement.
 *
 * @package Moar\Metrics
 * @copyright 2013 Bryan Davis and contributors. All Rights Reserved.
 */
final class ScopeTimer {

  /**
   * Timer name.
   * @var string
   */
  private $name;

  /**
   * Is this timer running?
   * @var bool
   */
  private $running;


  /**
   * Constructor.
   * @param string $name Timer name
   */
  public function __construct ($name) {
    $this->running = true;
    Track::start($name);
    $this->name = $name;
  }


  /**
   * Stop the timer.
   * @return void
   */
  public function stop () {
    if ($this->running) {
      Track::stop($this->name);
      $this->running = false;
    }
  } //end stop


  /**
   * Destructor.
   * Stops timer on destruct.
   */
  public function __destruct () {
    $this->stop();
  }

} //end ScopeTimer
