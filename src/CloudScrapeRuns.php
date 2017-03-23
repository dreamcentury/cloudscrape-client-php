<?php

class CloudScrapeRuns {
	/**
	 * @var CloudScrapeClient
	 */
	private $client;
	
	function __construct( CloudScrapeClient $client ) {
		$this->client = $client;
	}
	
	/**
	 * @param string                         $robotId
	 * @param CloudScrapeRunDTO|array|object $run
	 *
	 * @return CloudScrapeRunDTO
	 */
	public function create( $robotId, $run ) {
		if ( $robotId && is_object( $run ) ) {
			$run->robotId = $robotId;
		}
		if ( $robotId && is_array( $run ) ) {
			$run[ 'robotId' ] = $robotId;
		}
		
		return $this->client->requestJson( "runs", 'POST', $run );
	}
	
	/**
	 * @param string            $robotId
	 * @param CloudScrapeRunDTO $run
	 *
	 * @return CloudScrapeRunDTO
	 */
	private function _update( $robotId, $run ) {
		if ( $robotId && is_object( $run ) ) {
			$run->robotId = $robotId;
		}
		if ( $robotId && is_array( $run ) ) {
			$run[ 'robotId' ] = $robotId;
		}
		
		return $this->client->requestJson( "runs", 'PUT', $run );
	}
	
	/**
	 * @param string $runId
	 * @param array  $updates
	 *
	 * @return CloudScrapeRunDTO
	 */
	public function update( $runId, $updates ) {
		$run = $this->get( $runId );
		foreach ( $updates as $key => $value ) {
			$run->$key = $value;
		}
		
		return $this->_update( null, $run );
	}
	
	/**
	 * @param string $robotId
	 * @param int    $offset
	 * @param int    $limit
	 *
	 * @return CloudScrapeRunListDTO
	 */
	public function getRuns( $robotId, $offset = 0, $limit = 30 ) {
		return $this->client->requestJson( "runs?" . http_build_query( [
				"robotId" => $robotId,
				"offset"  => $offset,
				"limit"   => $limit,
			] ) );
	}
	
	/**
	 * @param string $runId
	 *
	 * @return CloudScrapeRunDTO
	 */
	public function get( $runId ) {
		return $this->client->requestJson( "runs/$runId" );
	}
	
	/**
	 * Permanently delete run
	 *
	 * @param string $runId
	 *
	 * @return bool
	 */
	public function remove( $runId ) {
		return $this->client->requestBoolean( "runs/$runId", 'DELETE' );
	}
	
	/**
	 * Start new execution of the run
	 *
	 * @param string $runId
	 *
	 * @return CloudScrapeExecutionDTO
	 */
	public function execute( $runId ) {
		return $this->client->requestJson( "runs/$runId/execute", 'POST' );
	}
	
	/**
	 * Start new execution of the run, and wait for it to finish before returning the result.
	 * The execution and result will be automatically deleted from CloudScrape completion
	 * - both successful and failed.
	 *
	 * @param string $runId
	 *
	 * @return CloudScrapeResultDTO
	 */
	public function executeSync( $runId ) {
		return $this->client->requestJson( "runs/$runId/execute/wait", 'POST' );
	}
	
	/**
	 * Starts new execution of run with given inputs
	 *
	 * @param string $runId
	 * @param array|object $inputs
	 *
	 * @return CloudScrapeExecutionDTO
	 */
	public function executeWithInput( $runId, $inputs ) {
		return $this->client->requestJson( "runs/$runId/execute/inputs", 'POST', $inputs );
	}
	
	/**
	 * Starts new execution of run with given inputs, and wait for it to finish before returning the result.
	 * The inputs, execution and result will be automatically deleted from CloudScrape upon completion
	 * - both successful and failed.
	 *
	 * @param string $runId
	 * @param array[]|object[]  $inputs array of input objects
	 *
	 * @return CloudScrapeResultDTO
	 */
	public function executeBulkSync( $runId, $inputs, $connect = false, $deleteAfter = true ) {
		$query = http_build_query( array(
			"connect"     => $connect ? 'true' : 'false',
			"deleteAfter" => $deleteAfter ? 'true' : 'false',
		) );
		
		return $this->client->requestJson( "runs/$runId/execute/bulk/wait?$query", 'POST', $inputs );
	}
	
	/**
	 * Starts new execution of run with given inputs
	 *
	 * @param string $runId
	 * @param array[]|object[] $inputs
	 *
	 * @return CloudScrapeExecutionDTO
	 */
	public function executeBulk( $runId, $inputs ) {
		return $this->client->requestJson( "runs/$runId/execute/bulk", 'POST', $inputs );
	}
	
	/**
	 * Starts new execution of run with given inputs, and wait for it to finish before returning the result.
	 * The inputs, execution and result will be automatically deleted from CloudScrape upon completion
	 * - both successful and failed.
	 *
	 * @param string       $runId
	 * @param object|array $inputs
	 * @param string       $connect
	 * @param string       $deleteAfter
	 *
	 * @return CloudScrapeResultDTO
	 */
	public function executeWithInputSync( $runId, $inputs, $connect = false, $deleteAfter = true ) {
		$query = http_build_query( array(
			"connect"     => $connect ? 'true' : 'false',
			"deleteAfter" => $deleteAfter ? 'true' : 'false',
		) );
		
		return $this->client->requestJson( "runs/$runId/execute/inputs/wait?$query", 'POST', $inputs );
	}
	
	/**
	 * Get the result from the latest execution of the given run.
	 *
	 * @param string $runId
	 *
	 * @return CloudScrapeResultDTO
	 */
	public function getLatestResult( $runId ) {
		return $this->client->requestJson( "runs/$runId/latest/result" );
	}
	
	/**
	 * Get executions for the given run.
	 *
	 * @param string $runId
	 * @param int    $offset
	 * @param int    $limit
	 *
	 * @return CloudScrapeExecutionListDTO
	 */
	public function getExecutions( $runId, $offset = 0, $limit = 30 ) {
		return $this->client->requestJson( "runs/$runId/executions?offset=$offset&limit=$limit" );
	}
}

