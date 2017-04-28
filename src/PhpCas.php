<?php
/**
 * A very simple CAS client. Only used to get user.
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2017 Ganlv
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 */

namespace PhpSimpleCas;

/**
 * Class PhpCas
 * A very simple CAS client. Only used to get user.
 *
 * @package PhpCas
 */
class PhpCas
{
    /**
     * @var array Server URLs
     */
    protected $server = null;
    /**
     * @var string ticket
     */
    protected $ticket = '';
    /**
     * @var string CAS response
     */
    protected $text_response = '';

    /**
     * PhpCas constructor.
     *
     * @param string|array $server CAS server | [login_url, logout_url, service_validate_url]
     *
     * @constructor
     */
    public function __construct($server)
    {
        if (is_array($server)) {
            $this->server = $server;
        } elseif (is_string($server)) {
            $server = rtrim($server, '/') . '/';
            $this->server = array(
                'login_url' => $server . 'login',
                'logout_url' => $server . 'logout',
                'service_validate_url' => $server . 'serviceValidate',
            );
        } else {
            throw new \RuntimeException('CAS server must be array or string');
        }
        if (isset($_GET['ticket']) && strpos('ST-', $_GET['ticket']) == 0) {
            $this->setTicket($_GET['ticket']);
            unset($_GET['ticket']);
        }
        if (session_id() == '') {
            session_start();
        }
    }

    /**
     * If not authenticated, redirect to CAS.
     * If authenticated, return true.
     *
     * @return bool always true.
     */
    public function forceAuthentication()
    {
        if (self::isAuthenticated()) {
            return true;
        } elseif ($this->getTicket()) {
            return $this->validateCas();
        }
        session_write_close();
        $this->redirectToCas();
        // never reached
        return true;
    }

    /**
     * If not gatewayed, redirect to CAS with gateway=true.
     *
     * @return bool true - authenticated. false - guess mode.
     */
    public function checkAuthentication()
    {
        if (self::isAuthenticated()) {
            return true;
        } elseif (self::isGatewayed()) {
            return $this->validateCas();
        }
        self::setGatewayed(true);
        session_write_close();
        $this->redirectToCas(true);
        // never reached
        return true;
    }

    /**
     * Get username. return false on failure.
     *
     * @return bool|string username
     */
    public function getUser()
    {
        return isset($_SESSION['phpCAS']['user']) ? $_SESSION['phpCAS']['user'] : false;
    }

    /**
     * Redirect to CAS logout url
     *
     * @return bool always true, but never reach.
     */
    public function logout()
    {
        unset($_SESSION['phpCAS']);
        session_write_close();
        self::http_redirect($this->server['logout_url']);
        // never reached
        return true;
    }

    /**
     * @return bool
     */
    public static function isAuthenticated()
    {
        return !empty($_SESSION['phpCAS']['user']);
    }

    /**
     * @return bool
     */
    public static function isGatewayed()
    {
        return !empty($_SESSION['phpCAS']['gatewayed']);
    }

    /**
     * @param bool $gatewayed
     */
    public static function setGatewayed($gatewayed)
    {
        $_SESSION['phpCAS']['gatewayed'] = $gatewayed;
    }

    /**
     * Redirect to CAS server for login
     *
     * @param bool $gateway
     *
     * @return bool always true, but never reach.
     */
    protected function redirectToCas($gateway = false)
    {
        $query['service'] = self::getDefaultService();
        if ($gateway) {
            $query['gateway'] = 'true';
        }
        self::http_redirect($this->server['login_url'] . '?' . http_build_query($query));
        // never reached
        return true;
    }

    /**
     * @return bool
     */
    protected function validateCas()
    {
        if (!$this->getTicket()) {
            return false;
        }
        $this->text_response = self::file_get_contents($this->server['service_validate_url'] . '?' . http_build_query(array(
                'service' => self::getDefaultService(),
                'ticket' => $this->getTicket(),
            )));
        if (1 !== preg_match('@<cas:user>(.*)</cas:user>@', $this->text_response, $matches)) {
            return false;
        }
        $_SESSION['phpCAS']['user'] = $matches[1];
        return true;
    }

    /**
     * @return string|bool return ticket for success, false for failed.
     */
    public function getTicket()
    {
        return $this->ticket ?: false;
    }

    /**
     * @param string $ticket
     */
    public function setTicket($ticket)
    {
        $this->ticket = $ticket;
    }

    /**
     * @return string
     */
    public function getTextResponse()
    {
        return $this->text_response;
    }

    /**
     * output 302 redirect header and exit
     *
     * @param string $url url
     */
    protected static function http_redirect($url)
    {
        header('Location: ' . $url, true, 302);
        exit;
    }

    /**
     * Send GET request
     *
     * @param string $url URL
     * @param int $timeout Timeout(seconds)
     *
     * @return string Text response
     */
    protected static function file_get_contents($url, $timeout = 10)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if (substr($url, 0, 8) === 'https://') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($ch);
        curl_close($ch);
        if (false === $content) {
            throw new \RuntimeException('\\PhpSimpleCas\\PhpCas::file_get_contents: curl_exec failed. CURL info: ' . curl_getinfo($ch));
        }
        return $content;
    }

    /**
     * reverse parse_url
     *
     * @link http://stackoverflow.com/questions/4354904/php-parse-url-reverse-parsed-url/4355011#4355011
     *
     * @param array $parts
     *
     * @return string
     */
    protected static function build_url($parts)
    {
        return (!empty($parts['scheme']) ? "{$parts['scheme']}:" : '') .
            ((!empty($parts['user']) || !empty($parts['host'])) ? '//' : '') .
            (!empty($parts['user']) ? $parts['user'] : '') .
            (!empty($parts['pass']) ? ":{$parts['pass']}" : '') .
            (!empty($parts['user']) ? '@' : '') .
            (!empty($parts['host']) ? $parts['host'] : '') .
            (!empty($parts['port']) ? ":{$parts['port']}" : '') .
            (!empty($parts['path']) ? $parts['path'] : '') .
            (!empty($parts['query']) ? "?{$parts['query']}" : '') .
            (!empty($parts['fragment']) ? "#{$parts['fragment']}" : '');
    }

    /**
     * Get the service name of this request
     *
     * @return string default service name
     */
    protected static function getDefaultService()
    {
        $service = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        $parts = parse_url($service);

        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        } else {
            $query = array();
        }
        unset($query['ticket']);
        $parts['query'] = http_build_query($query);

        return self::build_url($parts);
    }
}
