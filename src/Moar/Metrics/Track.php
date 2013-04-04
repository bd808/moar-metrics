<?php
/**
 * @package Moar\Metrics
 */

namespace Moar\Metrics;


/**
 * Utility class to collect counter and elapsed time metrics for logging or
 * other reporting.
 *
 * Collected metrics can be extracted for use in an application or exported to
 * a logfile for datamining and analysis. Convenience methods are provided for
 * common senarios like timing the duration of a single method call.
 *
 * Read
 * http://codeascraft.etsy.com/2011/02/15/measure-anything-measure-everything/
 * for an idea of the power that you can get from instrumenting your
 * application.
 *
 * @package Moar\Metrics
 * @copyright 2013 Bryan Davis and contributors. All Rights Reserved.
 */
final class Track {

  /**
   * Counter metrics.
   * @var array
   */
  private static $counters = array();

  /**
   * Elapsed time metrics.
   * @var array
   */
  private static $times = array();

  /**
   * Running timers.
   * @var array
   */
  private static $running = array();


  /**
   * Increment one or more counters.
   *
   * @param mixed $metric Metric(s) to alter
   * @return void
   */
  public static function inc ($metric) {
    self::count($metric, 1);
  } //end inc


  /**
   * Decrement one or more counters.
   *
   * @param mixed $metric Metric(s) to alter
   * @return void
   */
  public static function dec ($metric) {
    self::count($metric, -1);
  } //end dec


  /**
   * Alter one or more counters.
   *
   * @param mixed $metric Metric(s) to alter
   * @param int   $delta Delta to add to counter
   * @return void
   */
  public static function count ($metric, $delta = 1) {
    if (is_array($metric)) {
      foreach ($metric as $m) {
        self::count($m, $delta);
      }

    } else {
      if (!isset(self::$counters[$metric])) {
        self::$counters[$metric] = 0;
      }
      self::$counters[$metric] += $delta;
    }
  } //end count


  /**
   * Start one or more timers.
   *
   * If a time with the same name is already running no change will be made.
   *
   * @param mixed $timer Timer(s) to start
   * @param int   $epoch Timestamp in milliseconds
   * @return bool True if started, false otherwise
   */
  public static function start ($timer, $epoch = null) {
    $ret = false;
    if (null === $epoch) {
      $epoch = self::currentTimeMillis();
    }
    if (is_array($timer)) {
      foreach ($timer as $t) {
        $ret |= self::start($t, $epoch);
      }

    } else {
      if (!isset(self::$running[$timer])) {
        self::$running[$timer] = $epoch;
        $ret = true;
      }
    }

    return $ret;
  } //end start


  /**
   * Stop one or more timers.
   *
   * @param mixed $timer Timer(s) to stop
   * @param int   $epoch Timestamp in milliseconds
   * @return bool True if stopped, false otherwise
   */
  public static function stop ($timer, $epoch = null) {
    $ret = false;
    if (null === $epoch) {
      $epoch = self::currentTimeMillis();
    }
    if (is_array($timer)) {
      foreach ($timer as $t) {
        $ret |= self::stop($t, $epoch);
      }

    } else {
      if (isset(self::$running[$timer])) {
        $elapsed = $epoch - self::$running[$timer];
        unset(self::$running[$timer]);
        self::time($timer, $elapsed);
        $ret = true;
      }
    }

    return $ret;
  } //end stop


  /**
   * Cancel one or more timers.
   *
   * @param mixed $timer Timer(s) to cancel
   * @return bool True if canceled, false otherwise
   */
  public static function cancel ($timer) {
    $ret = false;
    if (is_array($timer)) {
      foreach ($timer as $t) {
        $ret |= self::cancel($t);
      }

    } else {
      if (isset(self::$running[$timer])) {
        unset(self::$running[$timer]);
        $ret = true;
      }
    }

    return $ret;
  } //end cancel


  /**
   * Record a timing event.
   *
   * @param mixed $timer Timer to add to.
   * @param int   $elapsed Milliseconds to add to timer.
   * @return void
   */
  public static function time ($timer, $elapsed) {
    if (is_array($timer)) {
      foreach ($timer as $t) {
        self::time($t, $elapsed);
      }

    } else {
      if (!isset(self::$times[$timer])) {
        // first sample for this timer
        self::$times[$timer] = $elapsed;

      } else if (is_array(self::$times[$timer])) {
        // add to existing sample collection
        self::$times[$timer][] = $elapsed;

      } else {
        // make a collection of the existing and new sample
        self::$times[$timer] = array(self::$times[$timer], $elapsed);
      }
    }
  } //end time


  /**
   * Find out how long a timer has been running.
   *
   * This method allows non-destructive sampling of a running timer to find
   * it's current duration.
   *
   * @param string $timer Timer to check.
   * @param int    $now   Millisecond epoch to check against
   * @return int Current elapsed time of timer or 0 if not running
   */
  public function split ($timer, $now = null) {
    if (isset(self::$running[$timer])) {
      if (null === $now) {
        $now = self::currentTimeMillis();
      }
      return $now - self::$running[$timer];
    } else {
      return 0;
    }
  } //end split


  /**
   * Get a list of all running timers.
   *
   * @return array List of running timers
   */
  public static function running () {
    return array_keys(self::$running);
  }


  /**
   * Stop all running timers.
   * @return void
   */
  public static function stopAll () {
    self::stop(self::running());
  } //end stopAll


  /**
   * Report on currently collected metrics.
   *
   * Counters will be reported as '<count>|c'. Timers will be reported as
   * '<elapsed>|ms'. If a timer has been started and stopped more than once it
   * will be reported as a semicolon separated list of times (eg '1;2;3|ms').
   *
   * @param bool $stop Stop running timers before reporting?
   * @return array Known metrics and their values
   */
  public static function report ($stop = true) {
    if ($stop) {
      self::stopAll();
    }

    $r = array();

    foreach (self::$counters as $metric => $count) {
      $r[$metric] = "{$count}|c";
    }

    foreach (self::$times as $metric => $times) {
      if (is_array($times)) {
        $r[$metric] = implode(';', $times) . '|ms';
      } else {
        $r[$metric] = "{$times}|ms";
      }
    }

    // throw in max memory utilization
    $r['php.max_mem'] = memory_get_usage(true) . '|bytes';

    ksort($r);
    return $r;
  } //end report


  /**
   * Write report to log file.
   *
   * @param Psr\Log $logger Logger to write to
   * @param string  $msg Message to include with report
   * @param bool    $stop Stop running timers before reporting?
   * @param array   $ctx Logging diagnostic data to attach to log message
   * @return void
   */
  public static function log ($logger, $msg = '', $stop = true, $ctx = null) {
    /* TODO: Port MDC context aware logger to Psr\Log
    $mdc = $logger->getContext();

    if (is_array($ctx)) {
      // decorate log message with any tags provided
      foreach ($ctx as $key => $val) {
        $mdc->put($key, $val);
      }
    }
     */

    $logger->info($msg . json_encode(self::report($stop)));

    /*
    if (is_array($ctx)) {
      // remove decorations
      foreach ($ctx as $key => $val) {
        $mdc->remove($key);
      }
    }
     */
  } //end log

  /**
   * Send current metrics to metricd.
   *
   * @param string $app Application name
   * @param string $host MetricD host
   * @param int $port MetricD port
   * @param bool $stop Stop all timers before sending?
   * @return void
   */
  public static function metricd (
      $app, $host = null, $port = null, $stop = true) {

    $md = new MetricdClient($host, $port);
    $md->setApp($app);
    $report = self::report($stop);

    $md->send($report);
  } //end metricd

  /**
   * Clear all recorded metrics.
   * @return void
   */
  public static function reset () {
    self::$counters = array();
    self::$times = array();
    self::$running = array();
  } //end reset


  /**
   * Create and return a scope timer for the given name.
   *
   * @param string $name Timer name
   * @return ScopeTimer Scope based timer object
   */
  public static function timeScope ($name) {
    return new ScopeTimer($name);
  } //end timeScope


  /**
   * Create and return a scope timer for the given fully qualified PHP method
   * name.
   *
   * This is the prefered method for timing the duration of a given method.
   * The typical use case would be to place a line like:
   *   `$timer = Moar\Metrics\Track::timeMethod(__METHOD__);`
   * as the first line the method to be timed. The timer will automatically
   * stop when the `$timer` variable goes out of scope and gets destroyed by
   * the php runtime engine.
   *
   * The method name (eg Moar\Metrics\Track::timeMethod) will be converted to a
   * standard metric format (eg moar.metrics.track.timemethod).
   *
   * @param string $name Method name as from __METHOD__
   * @param string $suffix Suffix to add to computed timer name
   * @param object $instance Calling class instance (use for subclasses)
   * @return ScopeTimer Scope based timer object
   * @see methodToMetric()
   */
  public static function timeMethod ($name, $suffix = null, $instance = null) {
    return self::timeScope(self::methodToMetric($name, $suffix, $instance));
  } //end timeMethod


  /**
   * Convert a php fully qualified method name into a metric name.
   * Conversion is done by lower casing the entire name and converting
   * underscores and the פעמיים נקודתיים to periods.
   * Eg. Data_Dao::getUserDefinedFieldTypes becomes
   * data.dao.getuserdefinedfieldtypes
   *
   * @param string $name Method name as from __METHOD__
   * @param string $suffix Suffix to add to computed metric name
   * @param object $instance Calling class instance (use for subclasses)
   * @return string Standardized metric name
   */
  public static function methodToMetric (
      $name, $suffix = null, $instance = null) {
    if (null != $instance) {
      // replace class name in provided name with instance's class
      $cn = get_class($instance);
      list($pc, $meth) = explode('::', $name, 2);
      $name = "{$cn}.{$meth}";
    }

    // normalize to lowercase and dot-separated
    $name = mb_strtolower(
        strtr($name, array('::' => '.', '_' => '.', '\\' => '.')));
    if (null != $suffix) {
      $name .= ".{$suffix}";
    }
    return $name;
  } //end methodToMetric


  /**
   * Get current system time in milliseconds.
   *
   * @return int Timestamp in milliseconds
   */
  public static function currentTimeMillis () {
    return (int) (microtime(true) * 1000);
  } //end currentTimeMillis


  /**
   * Construction disallowed for utility class.
   */
  private function __construct () {
    //no-op
  } //end __construct

} //end Track
