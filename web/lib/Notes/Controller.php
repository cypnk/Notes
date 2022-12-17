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
		if ( empty( $_params ) ) {
			// Default parameters
			$this->params['Config']		= new Config( $this );
			$this->params['Data']		= new Data( $this );
			$this->params['Session']	= new SHandler( $this );
		} else {
			$this->addParams( $_params );	
		}
		
		if ( isset( $this->params['Data'] ) ) {
			Entity::setData( $this->params['Data'] );
		}
	}
	
	/**
	 *  Added shared parameters by class name
	 *  
	 *  @param array	$_params	Loading parameters
	 */
	public function addParams( array $_params ) {
		foreach ( $_params as $p ) {
			// Only handle strings
			if ( !\is_string( $p ) ) {
				continue;
			}
			
			$k = \strrchr( \rtrim( $p, '\\' ), '\\' );
			// Not a class
			if ( false === $k ) { 
				continue; 
			}
			
			$k = \substr( $k, 1 );
			if ( \array_key_exists( $k, $this->params ) ) {
				continue;
			}
			
			$this->params[$k]	= match( true ) {
				// Type controllable
				\is_subclass_of( 
					'\\Notes\\Controllable', $p 
				)			=> new $p( $this ),
				
				// Other type of class
				\class_exists( $p )	=> new $p(),
				
				// Something else
				default			=> $p
			}
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
	 *  Current configuration return helper
	 */
	public function getConfig() {
		return $this->params['Config'] ?? null;
	}
	
	/**
	 *  Current data handler return helper
	 */
	public function getData() {
		return $this->params['Data'] ?? null;
	}
	
	/**
	 *  Current session handler return helper
	 */
	public function getSession() {
		return $this->params['Session'] ?? null;
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
	 *  
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
	 *  
	 *  
	 *  @param string	$name		Description for $name
	 *  @param array	$params		Description for $params
	 */
	public function run( string $name, ?array $params = null ) {
		if ( \array_key_exists( $name, $this->events ) ) {
			$this->events[$name]->notify( $params );
		}
	}
}
