<?php

class CloudScrape {

    /**
     * @var CloudScrapeClient
     */
    private static $client;


    public static function init($apiKey, $accountId, $useQueue = false) {
        self::$client = new CloudScrapeClient($apiKey, $accountId, $useQueue);
    }

    /**
     * @return CloudScrapeClient
     * @throws Exception if CloudScrape::init was not called
     */
    public static function defaultClient() {
        self::checkState();

        return self::$client;
    }

    /**
     * @return CloudScrapeExecutions
     * @throws Exception if CloudScrape::init was not called
     */
    public static function executions() {
        self::checkState();

        return self::$client->executions();
    }

    /**
     * @return CloudScrapeRuns
     * @throws Exception if CloudScrape::init was not called
     */
    public static function runs() {
        self::checkState();

        return self::$client->runs();
    }

    /**
     * @return CloudScrapeRobots
     * @throws Exception if CloudScrape::init was not called
     */
    public static function robots() {
        self::checkState();

        return self::$client->robots();
    }

    private static function checkState(){
        if (!self::$client) {
            throw new Exception('You must call init first before using the API');
        }
    }
}
