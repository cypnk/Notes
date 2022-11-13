<?php declare( strict_types = 1 );

namespace Notes;

class Handler implements \SplObserver extends Controllable {
	
	protected int $priority;
	
	public function __construct( Controller $ctrl, ?int $priority = null ) {
		if ( null != $priority ) {
			$this->priority = $priority;
		}
		parent::__construct( $ctrl );
	}
	
	public function getPriority() : int {
		return $this->priority ?? 0;
	}
	
	/**
	 *  @brief Brief description
	 *  
	 *  @param SplSubject	$event Description for $event
	 *  @param array	$params Description for $params
	 */
	public function notify( \SplSubject $event, ?array $params = null ) {}
}
