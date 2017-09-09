<?php
/**
 * The MIT License (MIT)
 * Copyright (c) 2017 Serhii Popov
 * This source file is subject to The MIT License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @category Popov
 * @package Popov_<package>
 * @author Serhii Popov <popow.sergiy@gmail.com>
 * @license https://opensource.org/licenses/MIT The MIT License (MIT)
 */
namespace Popov\Cyta;

use SimpleXMLElement;

class Client
{
    const VERSION = '1.0';

    protected $username;

    protected $secretKey;

    protected $language = 'el';

    protected $apiUrl = 'https://www.cyta.com.cy/cytamobilevodafone/dev/websmsapi/sendsms.aspx';

    public function __construct($username, $secretKey)
    {
        $this->username = $username;
        $this->secretKey = $secretKey;
    }

    /**
     * Send SMS message
     *
     *
     * @param string|array $to
     * @param string $message
     * @param string $language
     * @return string
     */
    public function send($to, $message, $language = null)
    {
        if (!is_array($to)) {
            $to = [$to];
        }
        $xml = $this->create($to, $message, $language ?: $this->language)->asXML();


        //$url = "https://www.cyta.com.cy/cytamobilevodafone/dev/websmsapi/sendsms.aspx";
        //$xml = file_get_contents("body.xml");

        $headers = [
            'POST HTTP/1.1',
            'Content-type: application/xml; charset="utf-8"',
            'Content-length: ' . strlen($xml),
            'Connection: close',
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS,  $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $data = curl_exec($ch);

        curl_close($ch);

        return $this->handleResponse($data);
    }

    protected function create(array $to, $message, $language)
    {
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><websmsapi></websmsapi>');

        $root->addChild('version', self::VERSION);
        $root->addChild('username', $this->username);
        $root->addChild('secretkey', $this->secretKey);

        $recipientsNode = $root->addChild('recipients');
        $recipientsNode->addChild('count', count($to));

        $mobilesNode = $recipientsNode->addChild('mobiles');
        foreach ($to as $number) {
            $mobilesNode->addChild('m', $number);
        }
        $root->addChild('message', $message);
        $root->addChild('language', $language);

        //Header('Content-type: text/xml');
        //$apiXml->asXML();
        return $root;
    }

    protected function handleResponse($response)
    {
        $root = new SimpleXMLElement($response);
        if ('0' === (string) $root->status) {
            return true;
        }

        throw new \Exception('Something went wrong, message has not sent! Status code: ' . (string) $root->status);
    }
}