<?php declare( strict_types = 1 );

namespace Notes;

class CacheHandler extends Handler {
	
	/**
	 *  Default cache timeout
	 */
	const CACHE_TTL	= 7200;
	
	/**
	 *  Currently loaded configuration updated date
	 *  @var string
	 */
	private readonly string $cache_updated;
	
	public function __construct( Controller	$ctrl, ?int $_pri = null ) {
		parent::__construct( $ctrl, $_pri );
		$this->controller->listen( 'find_cache', $this );
		$this->controller->listen( 'save_cache', $this );
		$this->controller->listen( 'load_cache', $this );
		$this->controller->listen( 'delete_cache', $this );
	}
	
	/**
	 *  Get specific cache by identifier
	 *  
	 *  @return mixed
	 */
	public function getCache( string $key ) {
		$res = $this->getCacheSet( [ $key ] );
		
		return empty( $res ) ? null : $res[0];
	}
	
	/**
	 *  Get collection of saved caches by key collection
	 *  
	 *  @param array	$keys	List of unique identifiers
	 *  @return
	 */
	public function getCacheSet( array $keys ) : array {
		// Format to current config
		$keys	= \array_map( [ $this, 'genCacheKey' ], $keys );
		
		// Nothing to find
		if ( empty( $keys ) ) {
			return [];
		}
		
		$db	= $this->getControllerParam( '\\\Notes\\Data' );
		$params	= [];
		$in	= $db->getInParam( $keys, $params );
		
		return 
		$db->getResults( 
			"SELECT * FROM cache_view WHERE cache_id {$in};", 
			$params, 
			\CACHE,
			'controllable|\\Notes\\Cache'
		);
	}
	
	/**
	 *  Save key-value set of cache data and optional TTL
	 *  
	 *  @param array	$data	Cache info in [id,content,TTL] format
	 *  @return array
	 */
	public function saveCacheData( array $data ) : array {
		$k = [];
		foreach ( $data as $c ) {
			$i		= new \Notes\Cache();
			$i->cache_id	= $this->genCacheKey( $c[0] );
			$i->content	= $c[1] ?? '';
			$i->ttl		= 
				( int ) ( $c[2] ?? static::CACHE_TTL );
			
			$i->save();
			$k[]		= $i;
		}
		
		return $k;
	}
	
	/**
	 *  Save given set of cache objects and return id with save status
	 *  
	 *  @param array	$caches		\Notes\Cache objects
	 *  @return array
	 */
	public function saveCacheSet( array $caches ) : array {
		$a = [];
		foreach ( $caches as $c ) {
			$a[] = [$c->cache_id, $c->save()];
		}
		
		return $a;
	}
	
	/**
	 *  Generate cache key for the given identifier
	 *  This function lets caches be invalidated if config has been modified
	 *  
	 *  @param string	$key	Original, identifier as cache key
	 *  @return string
	 */
	public function genCacheKey( string $key ) {
		if ( !isset( $this->cache_updated ) ) {
			$this->cache_updated	= 
			$this->getControllerParam( '\\\Notes\\Config' )->updated ?? '';
		}
		
		return 
		\hash( 'sha256', $this->cache_updated . trim( $key ) );
	}
	
	/**
	 *  Handle event notification
	 * 
	 *  @param \Notes\Event	$event		Cache triggering event
	 *  @param array	$params		Optional passed parameters
	 */
	public function notify( \SplSubject $event, ?array $params = null ) {
		$params	??= $event->getParams();
		
		switch ( $event->getName() ) {
			case 'find_cache':
				$this->checkCache( $params );
				break;
				
			case 'load_cache':
				$this->loadCache( $params );
				break;
				
			case 'save_cache':
				$this->saveCache( $params );
				break;
				
			case 'delete_cache':
				$this->deleteCache( $params );
				break;
		}
	}
	
	/**
	 *  Prepare base cache parameters
	 *  
	 *  @param array	$params		Passed parameters
	 */
	protected function prepareParams( array $params ) : array {
		if ( empty( $params ) ) {
			$this->output		= $params;
			return [];
		}
		
		// Default to cache invalid
		$params['cache_valid']	= false;
		
		// Key stored in event parameters or empty key
		$params['cache_id']	= 
		empty( $params['cache_key'] ) ? 
			'' : $this->genCacheKey( $params['cache_key'] );
		
		// Set TTL or default
		$params['cache_ttl']	??= static::CACHE_TTL;
		$params['cache_ttl']	= ( int ) $params['cache_ttl'];
		
		return $params;
	}
	
	/**
	 *  Create and save cache based on key stored in event
	 *  
	 *  @param array	$params		Passed parameters
	 */
	protected function saveCache( array $params ) {
		
		// No cache key to save
		if ( empty( $params['cache_key'] ) ) {
			$this->output		= $params;
			return;
		}
		
		$saved	= 
		$this->saveCacheData( [ [
			$params['cache_key'],
			$params['cache_data'] ?? '',
			$params['cache_ttl']
		] ] );
		
		// Couldn't save?
		if ( empty( $saved ) ) {
			return;
		}
		
		// Positive TTL?
		if ( $params['cache_ttl'] > 0 ) {
			$params['cache_valid'] = true;
		}
		
		$params['cache']	= $saved[0];
		
		$this->output = $params;
	}
	
	/**
	 *  Check if cache exists based on key stored in event
	 *  
	 *  @param array	$params		Passed parameters
	 */
	protected function checkCache( array $params ) {
		// No cache ID to find
		if ( empty( $params['cache_id'] ) ) {
			$this->output		= $params;
			return;
		}
		
		$db	= $this->getControllerParam( '\\\Notes\\Data' );
		$find	= 
		$db->getResults( 
			"SELECT cache_id, expires 
				FROM caches WHERE cache_id = :id LIMIT 1;", 
			[ ':id' => $params['cache_id'] ], 
			\CACHE
		);
		
		// No cache found
		if ( empty( $find ) ) {
			$this->output		= $params;
			return;
		}
		
		// Find expiration
		$row	= $find[0];
		$exp	= \strtotime( $row['expires'] );
		
		// Formatting went wrong?
		if ( false === $exp ) {
			$this->output		= $params;
			return;
		}
		
		// Cache is still valid
		if ( $exp >= \time() ) {
			$params['cache_valid']	= true;
		}
		$this->output		= $params;
	}
	
	/**
	 *  Load cache data based on key stored in event
	 *  
	 *  @param array	$params		Passed parameters
	 */
	protected function loadCache( array $params ) {
		// No cache key to load
		if ( empty( $params['cache_key'] ) ) {
			$this->output		= $params;
			return;
		}
		
		$params['cache'] = 
			$this->getCache( $params['cache_key'] );
		
		// No cache found
		if ( empty( $params['cache'] ) ) {
			$this->output		= $params;
			return;
		}
		
		// Check if cache is still valid
		if ( $params['cache']->expires > \time() ) {
			$params['cache_valid'] = true;
		}
		$this->output		= $params;
	}
	
	/**
	 *  Delete cache based on key stored in event
	 *  
	 *  @param array	$params		Passed parameters
	 */
	protected function deleteCache( array $params ) {
		// No cache ID to delete
		if ( empty( $params['cache_id'] ) ) {
			$this->output		= $params;
			return;
		}
		
		$db	= $this->getControllerParam( '\\\Notes\\Data' );
		$db->dataExec( 
			"DELETE FROM caches WHERE cache_id = :id LIMIT 1;", 
			[ ':id' => $params['cache_id'] ], 
			\CACHE
		);
		
		$this->output	= $params;
	}
}


