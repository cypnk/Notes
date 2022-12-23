<?php declare( strict_types = 1 );

namespace Notes;

class Controller {
	
	/**
	 *  Loaded events
	 *  @var array
	 */
	protected array	$events		= [];
	
	/**
	 *  Shared parameters
	 *  @var array
	 */
	protected array $params;
	
	public function __construct( ?array $_params = null ) {
		// Default parameters
		$this->addParams( 
			empty( $_params ) ? [ '\\Notes\\Data' ] : $_params 
		);
		
		$this->loadWebStartHandlers();
	}
	
	/**
	 *  Added shared parameters by class name
	 *  
	 *  @param array	$_params	Loading parameters
	 */
	public function addParams( array $_params ) {
		foreach ( $_params as $p ) {
			// Only handle strings and objects
			if ( !\is_string( $p ) || !\is_object( $p ) ) {
				continue;
			}
			
			// Filter type
			$t = match( \strtolower( \gettype( $p ) ) ) {
				'string'	=> 'string', 
				'object', 'resource', 'resource (closed)' 
						=> 'object', 
				// Skip all else
				default		=> 'skip'
			}
			
			// Shouldn't be handled
			if ( 0 == \strcmp( 'skip', $t ) ) {
				continue;
			}
			
			// Create search key
			$k = ( 0 == \strcmp( 'object', $t ) ) ? 
				\spl_object_hash( $p ) : \rtrim( $p, '\\' );
			
			// Already added?
			if ( \array_key_exists( $k, $this->params ) ) {
				continue;
			}
			
			// Add as-is, if object
			if ( 0 == \strcmp( 'object', $t ) ) {
				$this->params[$k] = $p;
				continue;
			}
			
			$this->params[$k]	= match( true ) {
				// Type controllable
				\is_subclass_of( 
					'\\Notes\\Controllable', $p 
				)			=> new $p( $this ),
				
				// Other type of class
				\class_exists( $p )	=> new $p()
			}
		}
	}
	
	/**
	 *  Load 'web_start' event handlers
	 */
	public function loadWebStartHandlers() {
		$db = $this->getParam( '\\Notes\\Data' );
		$hd = 
		$db->getResults( 
			"SELECT payload FROM handlers 
				WHERE event_name = :event ORDER BY priority DESC;", 
			[ ':event' => 'web_start' ], 
			\DATA,
			'column list'
		);
		
		// No startup handlers?
		if ( empty( $hd ) ) {
			return;	
		}
		
		// Apply load start events
		$this->addParam( $hd );
		foreach ( $hd as $h ) {
			$this->listen( 'web_start', $this->getParam( $h ) );
		}
	}
	
	/**
	 *  Current init parameter return helper
	 *  
	 *  @param string	$name		Property label
	 *  @return mixed
	 */
	public function getParam( string $name ) {
		return $this->params[$name] ?? null;
	}
	
	/**
	 *  Add to list of running events
	 *  
	 *  @param string	$name		New event name
	 *  @param Handler	$handler	
	 */
	public function listen( string $name, Handler $handler ) {
		$this->addEvent( $name );
		$this->events[$name]->attach( $handler );
	}
	
	/**
	 *  Remove handler from given event
	 *  
	 *  @param string	$name		Event name
	 *  @param object	$handler	Event handler
	 *  @reutrn bool
	 */
	public function dismiss( string $name, Handler $handler ) : bool {
		if ( \array_key_exists( $name, $this->events ) ) {
			$this->events[$name]->detach( $handler );
			return true;
		}
		return false;
	}
	
	/**
	 *  Completely remove event from running list of events
	 *  
	 *  @param string	$name	Event name
	 *  @return bool
	 */
	public function removeEvent( string $name ) : bool {
		if ( \array_key_exists( $name, $this->events ) ) {
			unset( $this->events[$name] );
			return true;
		}
		return false;
	}
	
	/**
	 *  Register new event
	 *  
	 *  @param string	$name	Description for $name
	 */
	public function addEvent( string $name ) {
		if ( \array_key_exists( $name, $this->events ) ) {
			return;
		}
		
		$this->events[$name] = new Event( $this, $name );
	}
	
	/**
	 *  Run handlers in given event
	 *  
	 *  @param string	$name		Unique event name
	 *  @param array	$params		Runtime parameters
	 */
	public function run( string $name, ?array $params = null ) {
		if ( \array_key_exists( $name, $this->events ) ) {
			$this->events[$name]->notify( $params );
		}
	}
}
