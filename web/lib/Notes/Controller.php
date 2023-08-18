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
		if ( !empty( $_params ) ) {
			$this->addParams( $_params );
		}
		
		$this->loadWebStartHandlers();
	}
	
	/**
	 *  Added shared parameters by class name
	 *  
	 *  @param array	$params		Loading parameters
	 */
	public function addParams( array $params ) {
		
		set_error_handler( function( 
			$eno, $emsg, $efile, $eline 
		) {
			$str	= 
			'Error adding controller parameters ' .  
				'Message: {msg} File: {file} Line: {line}';
			
			logException( 
				new \ErrorException( 
					$emsg, 0, $eno, $efile, $eline 
				), $str 
			);
		}, E_WARNING | E_ERROR );
		
		foreach ( $params as $p ) {
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
			
			$this->_params[$k]	= match( true ) {
				// Type controllable
				\is_subclass_of( 
					$p, '\\Notes\\Controllable'
				)			=> new $p( $this ),
				
				// Other type of class
				\class_exists( $p )	=> new $p(),
				
				// String only
				default			=> $p
			};
		}
		
		\restore_error_handler();
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
		$this->addParams( [ $hd ] );
		foreach ( $hd as $h ) {
			$this->listen( 'web_start', $this->getParam( $h ) );
		}
	}
	
	/**
	 *  Current init parameter return helper
	 *  
	 *  @param mixed	$name		Property label(s)
	 *  @return mixed
	 */
	public function getParam( $name ) {
		switch( true ) {
			case \is_array( $name ) : {
				$this->addParams( $name );
				
				return 
				\array_intersect_key(
					$this->_params,
					\array_flip( $name )
				);
			}
			
			case \is_string( $name ) : {
				$this->addParams( [ $name ] );
				return $this->_params[$name] ?? null;
			}
			
			default:
				return null;
		}
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
	 *  Add runnable event to current list
	 *  
	 *  @param string	$name	Unique event name
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
	
	/**
	 *  Return compiled output from event execution, if it extists
	 *  
	 *  @param string	$name		Unique event name
	 *  @return array
	 */
	public function output( string $name ) : array {
		return ( \array_key_exists( $name, $this->events ) ) ?
			$this->events[$name]->output() : [];
	}
}
