<?php

class CloudScrapeClient {

    private $endPoint = 'https://app.dexi.io/api/';
    private $userAgent = 'DEXIIO-PHP-CLIENT/1.0';
    private $apiKey;
    private $accountId;
    private $accessKey;

    private $requestTimeout = 3600;
	
	private $useQueue = false;
	private $queue_log_enabled = false;
	private $queue_lock_name = "CLOUDSCRAPE_CLIENT_REQUEST_QUEUE_LOCK_NAME";
	private $queue_timeout_ms = 10000;
	private $queue_limit_count = 1;
	private $queue_limit_time_ms = 1000;

    /**
     * @var CloudScrapeExecutions
     */
    private $executions;

    /**
     * @var CloudScrapeRuns
     */
    private $runs;

    /**
     * @var CloudScrapeRobots
     */
    private $robots;

    function __construct($apiKey, $accountId, $useQueue = false) {
        $this->apiKey = $apiKey;
        $this->accountId = $accountId;
        $this->accessKey = md5($accountId . $apiKey);
        
        $this->setQueueUse( $useQueue );

        $this->executions = new CloudScrapeExecutions($this);
        $this->runs = new CloudScrapeRuns($this);
        $this->robots = new CloudScrapeRobots($this);
    }
	
    
	/**
	 * Sets the use of Queue
	 * @param bool $use
	 */
    public function setQueueUse( bool $use ){
    	$this->useQueue = (bool) $use;
	}
	
	/**
	 * Sets the name of the Queue lock
	 * @param string $name
	 */
	public function setQueueLockName( string $name ){
    	$this->queue_lock_name = $name;
	}
	
	/**
	 * Sets the timeout of the Queue
	 * @param int $timeout
	 */
	public function setQueueTimeout( int $timeout ){
		$this->queue_timeout_ms = $timeout;
	}
	
	/**
	 * Sets the limitcount of the Queue
	 * @param int $count
	 */
	public function setQueueLimitCount( int $count ){
    	$this->queue_limit_count = $count;
	}
	
	/**
	 * Sets the limitcount of the Queue
	 * @param int $time
	 */
	public function setQueueLimitTime( int $time ){
    	$this->queue_limit_time_ms = $time;
	}

	
    /**
     * Get current request timeout
     * @return int
     */
    public function getRequestTimeout()
    {
        return $this->requestTimeout;
    }

    /**
     * Set request timeout. Defaults to 1 hour.
     *
     * Note: If you are using the sync methods and some requests are running for very long you need to increase this value.
     *
     * @param int $requestTimeout
     */
    public function setRequestTimeout($requestTimeout)
    {
        $this->requestTimeout = $requestTimeout;
    }



    /**
     * Get endpoint / base url of requests
     * @return string
     */
    public function getEndPoint()
    {
        return $this->endPoint;
    }

    /**
     * Set end point / base url of requests
     * @param string $endPoint
     */
    public function setEndPoint($endPoint)
    {
        $this->endPoint = $endPoint;
    }

    /**
     * Get user agent of requests
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * Set user agent of requests
     * @param string $userAgent
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
    }


    /**
     *
     * Make a call to the CloudScrape API
     * @param string $url
     * @param string $method
     * @param mixed $body Will be converted into json
     * @return object
     * @throws CloudScrapeRequestException
     */
    public function request($url, $method = 'GET', $body = null) {
        $content = $body ? json_encode($body) : null;

        $headers = array();
        $headers[] = "X-DexiIO-Access: $this->accessKey";
        $headers[] = "X-DexiIO-Account: $this->accountId";
        $headers[] = "User-Agent: $this->userAgent";
        $headers[] = "Accept: application/json";
        $headers[] = "Content-Type: application/json";

        if ($content) {
            $headers[] = "Content-Length: " . strlen($content);
        }

        $requestDetails = array(
            'method' => $method,
            'header' => join("\r\n",$headers),
            'content' => $content,
            'timeout' => $this->requestTimeout
        );

        $context  = stream_context_create(array(
            'https' => $requestDetails,
            'http' => $requestDetails
        ));
	
        if( $this->useQueue ){
        	$Queue = new \PhpSimpleQueue\FileQueue( $this->queue_lock_name, $this->queue_log_enabled, $this->queue_limit_count, $this->queue_limit_time_ms );
        	$Queue->enterInQueue( $this->queue_timeout_ms, function() use( $url, $context ){
				return $this->processRequest( $url, $context );
			}, $output );
        	return $output;
		}
		else{
			return $this->processRequest( $url, $context );
		}
    }
    
    private function processRequest( $url, $context ){
		$outRaw = @file_get_contents($this->endPoint . $url, false, $context);
		$out = $this->parseHeaders($http_response_header);
	
		$out->content = $outRaw;
	
		if ($out->statusCode < 100 || $out->statusCode > 399) {
			throw new CloudScrapeRequestException("CloudScrape request failed: $out->statusCode $out->reason", $url, $out);
		}
	
		return $out;
	}

    /**
     * @param string $url
     * @param string $method
     * @param mixed $body
     * @return mixed
     * @throws CloudScrapeRequestException
     */
    public function requestJson($url, $method = 'GET', $body = null) {
        $response = $this->request($url, $method, $body);
        return json_decode($response->content);
    }

    /**
     * @param string $url
     * @param string $method
     * @param mixed $body
     * @return bool
     * @throws CloudScrapeRequestException
     */
    public function requestBoolean($url, $method = 'GET', $body = null) {
        $this->request($url, $method, $body);
        return true;
    }

    private function parseHeaders($http_response_header) {
        $status = 0;
        $reason = '';
        $outHeaders = array();

        if ($http_response_header &&
            count($http_response_header) > 0) {
            $httpHeader = array_shift($http_response_header);
            if (preg_match('/([0-9]{3})\s+([A-Z_]+)/i', $httpHeader, $matches)) {
                $status = intval($matches[1]);
                $reason = $matches[2];
            }

            foreach($http_response_header as $header) {
                $parts = explode(':',$header,2);
                if (count($parts) < 2) {
                    continue;
                }

                $outHeaders[trim($parts[0])] = $parts[1];
            }
        }

        return (object)array(
            'statusCode' => $status,
            'reason' => $reason,
            'headers' => $outHeaders
        );
    }

    /**
     * Interact with executions.
     * @return CloudScrapeExecutions
     */
    public function executions() {
        return $this->executions;
    }

    /**
     * Interact with runs
     * @return CloudScrapeRuns
     */
    public function runs() {
        return $this->runs;
    }

    /**
     * Interact with robots
     * @return CloudScrapeRobots
     */
    public function robots() {
        return $this->robots;
    }

}
