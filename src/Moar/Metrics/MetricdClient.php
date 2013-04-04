<?php
/**
 * @package Moar\Metrics
 */

namespace Moar\Metrics;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;


/**
 * Client for sending data collected by Moar\Metrics\Track to a Kount MetricD
 * aggregation server.
 *
 * MetricD accepts UDP datagrams with a json payload. This payload includes
 * some metadata about the client that is sending the information and
 * collections of counter and timer metrics.
 *
 * Example Payload:
 * <pre>
 *  {
 *    "meta": {
 *      "host": "dev31.boi.keynetics.com",
 *      "app": "kaptcha",
 *      "merc": 999999
 *    },
 *    "metrics": {
 *      "fizz": "3|c",
 *      "buzz": "5|c",
 *      "foo": "9|ms",
 *      "bar": "1;2;3|ms"
 *    }
 *  }
 * </pre>
 *
 * @package Moar\Metrics
 * @copyright 2013 Bryan Davis and contributors. All Rights Reserved.
 */
class MetricdClient implements LoggerAwareInterface {

  /**
   * Logger.
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * Metricd host to send data to.
   * @var string
   */
  protected $host = '127.0.0.1';

  /**
   * Metricd port to send data to.
   * @var int
   */
  protected $port = 8125;

  /**
   * Client hostname.
   * @var string
   */
  protected $hostname;

  /**
   * Client application name.
   * @var string
   */
  protected $app;

  /**
   * Constructor.
   *
   * @param string $host MetricD hostname/ip address. Null for default
   *    `127.0.0.1`.
   * @param int $port MetricD port number. Null for default `8125`.
   * @param LoggerInterface $logger Logger instance.
   */
  public function __construct ($host = null, $port = null, $logger = null) {
    if (null !== $logger) {
      $this->setLogger($logger);
    }
    if (null !== $host) {
      $this->setHost($host);
    }
    if (null !== $port) {
      $this->setPort($port);
    }

    $this->setHostname(php_uname('n'));
  } //end __construct


  /**
   * Sets a logger instance on the object
   *
   * @param LoggerInterface $logger
   * @return void
   */
  public function setLogger (LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * Set the MetricD host.
   * @param string $host Hostname or ip address
   * @return MetricdClient Self, for message chaining.
   */
  public function setHost ($host) {
    $this->host = $host;
    return $this;
  }

  /**
   * Set the MetricD port.
   * @param int $port Port number
   * @return MetricdClient Self, for message chaining.
   */
  public function setPort ($port) {
    $this->port = $port;
    return $this;
  }

  /**
   * Host on who's behalf metrics are being submitted.
   * @param string $hostname Fully qualified domain name of host
   * @return MetricdClient Self, for message chaining.
   */
  public function setHostname ($hostname) {
    $this->hostname = $hostname;
    return $this;
  }

  /**
   * Application that is submitting metrics.
   * @param string $app Application name
   * @return MetricdClient Self, for message chaining.
   */
  public function setApp ($app) {
    $this->app = $app;
    return $this;
  }

  /**
   * Send metrics to metricd host.
   *
   * @param array $metrics Metrics to send.
   * @param array $extraMeta Additional meta information to send along with
   *    metrics.
   * @return MetricdClient Self, for method chaining.
   */
  public function send ($metrics, $extraMeta = null) {
    try {
      $meta = array(
          'host' => $this->hostname,
          'app' => $this->app,
        );

      /* TODO: Port MDC context aware logger to Psr\Log
      // extend meta with logging MDC
      $meta = array_merge($this->logger->getContext()->getContext(), $meta);
      */

      if (null !== $extraMeta && is_array($extraMeta)) {
        // extend meta with extra meta
        $meta = array_merge($meta, $extraMeta);
      }

      $payload = array(
          'meta' => $meta,
          'metrics' => $metrics,
        );

      // lame, but json_encode can trigger warnings that there
      // is no real way to quash except for using the mute ban hammer.
      // You'd think that their "return false + json_last_error()" mechanism
      // would be enough.
      $packet = @json_encode($payload);

      // create udp socket
      $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
      // send payload as UDP datagram
      socket_sendto($socket,
          $packet, mb_strlen($packet, 'latin1'),
          MSG_EOR, $this->host, $this->port);

    } catch (Exception $e) {
      if (null !== $this->logger) {
        $this->logger->error(
            "Failure sending metrics to {$this->host}:{$this->port}: {$e}");
      }
    } //end try
    return $this;
  } //end send

} //end MetricdClient
