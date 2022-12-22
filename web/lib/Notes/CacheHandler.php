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
		
		$db	= $this->controller->getData();
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
			$i->content	= $c[1];
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
			$this->controller->getConfig()->updated ?? '';
		}
		
		return 
		\hash( 'sha256', $this->cache_updated . trim( $key ) );
	}
	
	public function notify( \SplSubject $event, ?array $params = null ) {
		
		switch ( $event->getName() ) {
			case 'find_cache':
				$this->checkCache( $event );
				break;
				
			case 'load_cache':
				$this->loadCache( $event );
				break;
				
			case 'save_cache':
				$this->saveCache( $event );
				break;
				
			case 'delete_cache':
				$this->deleteCache( $event );
				break;
		}
	}
	
	protected function saveCache( \Notes\Event $event ) {
		// TODO: Handle cache save
	}
	
	/**
	 *  Check if cache exists based on key stored in event
	 *  
	 *  @param \Notes\Event		$event		Cache triggering event
	 */
	protected function checkCache( \Notes\Event $event ) {
		$params	= $event->getParams();
		
		// Default to cache not found
		$params['cache_valid'] = false;
		
		$key	= $params['cache_key'] ?? '';
		
		// No key stored in event parameters
		if ( empty( $key ) ) {
			$event->params = $params;
			return;	
		}
		
		$db	= $this->controller->getData();
		$find	= 
		$db->getResults( 
			"SELECT cache_id, content, expires 
				FROM caches WHERE cache_id = :id LIMIT 1;", 
			[ ':id' => $this->genCacheKey( $key ) ], 
			\CACHE
		);
		
		// No cache found
		if ( empty( $find ) ) {
			$event->params = $params;
			return;
		}
		
		// Find expiration
		$row	= $find[0];
		$exp	= \strtotime( $row['expires'] );
		
		// Formatting went wrong?
		if ( false === $exp ) {
			$event->params = $params;
			return;
		}
		
		// Cache is still valid
		if ( $exp >= \time() ) {
			$params['cache_valid'] = true;
			$event->params = $params;
		}
	}
	
	/**
	 *  Load cache data based on key stored in event
	 *  
	 *  @param \Notes\Event		$event		Cache triggering event
	 */
	protected function loadCache( \Notes\Event $event, bool $load = true ) {
		// TODO: Handle cache retrieval
	}
	
	protected function deleteCache( \Notes\Event $event ) {
		// TODO: Remove from cache store
	}
}


