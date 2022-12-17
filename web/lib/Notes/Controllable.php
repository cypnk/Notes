<?php declare( strict_types = 1 );

namespace Notes;

abstract class Controllable {
	
	/**
	 *  Current event name
	 *  @var string
	 */
	protected readonly string $name;
	
	/**
	 *  Main event controller
	 *  @var \PubCabin\Controller
	 */
	protected readonly object $controller;
	
	/**
	 *  Event parameters on execution
	 */
	protected array $params		= [];
	
	/**
	 *  Error storage
	 *  @var array
	 */
	protected array $errors		= [];
	
	/**
	 *  Notification storage
	 *  @var array
	 */
	protected array $notices	= [];
	
	/**
	 *  Create new runnable with controller
	 *  
	 *  @param \PubCabin\Controller	$ctrl	Event controller
	 */
	public function __construct( 
		\PubCabin\Controller	$ctrl 
	) {
		$this->controller	= $ctrl;
	}
	
	public function __destruct() {
		foreach ( $this->errors as $e ) {
			\messages( 'error', \get_called_class() . ' ' . $e );
		}
		
		foreach ( $this->notices as $m ) {
			\messages( 'notice', \get_called_class() . ' ' . $m );
		}
	}
	
	/**
	 *  Current controllable's name (read-only)
	 *  
	 *  @return string
	 */
	public function getName() : string {
		return $this->name ?? '';
	}
	
	/** 
	 *  Currently set event controller
	 *  
	 *  @return \Notes\Controller
	 */
	public function getController() {
		return $this->controller;
	}
	
	/**
	 *  Event parameters
	 *  
	 *  @return array
	 */
	public function getParams() : array {
		return $this->params;
	}
	
	/**
	 *  Get running errors
	 *  
	 *  @return array
	 */
	public function getErrors() : array {
		return $this->errors;
	}
	
	/**
	 *  Get running notices
	 *  
	 *  @return array
	 */
	public function getNotices() : array {
		return $this->notices;
	}
}
