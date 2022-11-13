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
	protected array $params	= [];
	
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
	
	/**
	 *  Current controllable's name (read-only)
	 *  @return string
	 */
	public function getName() : string {
		return $this->name ?? '';
	}
}