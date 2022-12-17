<?php declare( strict_types = 1 );

namespace Notes;

class Handler implements \SplObserver extends Controllable {
	
	/**
	 *  Handler execution priority
	 *  @var int
	 */
	protected int $priority;
	
	/**
	 *  This handler doesn't allow priority change if true
	 *  @var bool
	 */
	protected bool $fixed_priority	= false;
	
	/**
	 *  Stored handler output per event
	 *  @var array
	 */
	protected array $output	= [];
	
	
	public function __construct( Controller $ctrl, ?int $priority = null ) {
		if ( null != $priority ) {
			$this->priority = $priority;
		}
		parent::__construct( $ctrl );
	}
	
	public function getPriority() : int {
		return $this->priority ?? 0;
	}
	
	public function setPriority( int $p ) : bool {
		if ( $this->fixed_priority ) {
			return false;
		}
		$this->priority = $p;
		return true;
	}
	
	public function getOutput( string $name ) : array {
		return $this->output[$name] ?? [];
	}
	
	/**
	 *  @brief Brief description
	 *  
	 *  @param SplSubject	$event Description for $event
	 *  @param array	$params Description for $params
	 */
	public function notify( \SplSubject $event, ?array $params = null ) {}
}
