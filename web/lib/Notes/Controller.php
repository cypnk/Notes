<?php declare( strict_types = 1 );

namespace Notes;

class Controller {
	
	protected array	$events		= [];
	
	protected readonly Config $config;
	
	protected readonly Data $data;
	
	public function __construct() {
		$this->config	= new Config( $this );
		$this->data	= new Data( $this );
		
		Entity::setData( $this->data );
	}
	
	/**
	 *  Current configuration return helper
	 */
	public function getConfig() {
		return $this->config;
	}
	
	/**
	 *  Current data handler return helper
	 */
	public function getData() {
		return $this->data;
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
