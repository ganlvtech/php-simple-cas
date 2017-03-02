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
    private $server = null;
    /**
     * @var string ticket
     */
    private $ticket = '';
    /**
     * @var string CAS response
     */
    private $text_response = '';

    /**
     * PhpCas constructor.
     *
     * @param string|array $server CAS server | [login_url, logout_url, service_validate_url]
     */
    public function __construct($server)
    {
        if (is_array($server)) {
            $this->server = $server;
        } else {
            $this->server = array(
                'login_url' => $server . 'login',
                'logout_url' => $server . 'logout',
                'service_validate_url' => $server . 'serviceValidate',
            );
        }
    }

    /**
     * Get username. If failed, redirect to login url.
     *
     * @param string|null $service service name
     * @param string $key key of ticket in $_GET
     *
     * @return string username
     */
    public function getUserOrRedirect($service = null, $key = 'ticket', $timeout = 10)
    {
        $ticket = $this->getTicket($key);
        if (!$ticket || !$user = $this->getUser($service, $ticket, $timeout)) {
            $this->login($service);
            return false;
        }
        return $user;
    }

    /**
     *
     * @param string $key key of ticket in $_GET
     *
     * @return string|bool return ticket for success, false for failed.
     */
    public function getTicket($key = 'ticket')
    {
        if ($this->ticket) {
            return $this->ticket;
        } elseif (isset($_GET[$key])) {
            $this->ticket = $_GET[$key];
            return $this->ticket;
        }
        return false;
    }

    /**
     *
     * @return string
     */
    public function getTextResponse()
    {
        return $this->text_response;
    }

    /**
     * Send GET request
     *
     * @param string $url URL
     * @param int $timeout Timeout(seconds)
     *
     * @return string Text response
     */
    public static function file_get_contents($url, $timeout)
    {
        $opts = array(
            'http' => array(
                'timeout' => $timeout,
            ),
        );
        return file_get_contents($url, false, stream_context_create($opts));
    }

    /**
     * Get username
     *
     * @param string|null $service service name
     * @param string|null $ticket ticket
     * @param int $timeout Timeout(seconds)
     *
     * @return bool|string username
     */
    public function getUser($service = null, $ticket = null, $timeout = 10)
    {
        $service = $service ?: self::getDefaultService();
        $this->ticket = $ticket ?: $this->getTicket();
        $this->text_response = self::file_get_contents($this->server['service_validate_url'] . '?' . http_build_query(array(
                'service' => $service,
                'ticket' => $this->ticket,
            )), $timeout);
        if (1 !== preg_match('@<cas:user>(.*)</cas:user>@', $this->text_response, $matches)) {
            return false;
        }
        return $matches[1];
    }

    /**
     * Get the service name of this request
     *
     * @return string default service name
     */
    public static function getDefaultService()
    {
        return (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'] . strtok($_SERVER['REQUEST_URI'], '?');
    }

    /**
     * Redirect to CAS login url
     *
     * @param string|null $service service name
     */
    public function login($service = null)
    {
        $service = $service ?: self::getDefaultService();
        self::redirect($this->server['login_url'] . '?' . http_build_query(array(
                'service' => $service,
            )));
    }

    /**
     * output 302 redirect header and exit
     *
     * @param string $url url
     */
    public static function redirect($url = '')
    {
        header('Location: ' . $url, true, 302);
        exit;
    }

    /**
     * Redirect to CAS logout url
     */
    public function logout()
    {
        self::redirect($this->server['logout_url']);
    }
}
