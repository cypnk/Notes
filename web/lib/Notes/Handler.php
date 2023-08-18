<?php declare( strict_types = 1 );

namespace Notes;

abstract class Handler extends NamedControllable implements \SplObserver {
	
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
	
	/**
	 *  Create handler with given controller and optional start priority
	 *  
	 *  @param \Notes\Controller	$ctrl	Main event controller
	 *  @param string		$name	Current handler's name
	 *  @param int			$_pri	Optional execution priority
	 */
	public function __construct( 
		Controller	$ctrl, 
		?string		$name	= null, 
		?int		$_pri	= null 
	) {
		$this->priority = $_pri ?? 0;
		
		parent::__construct( $ctrl, $name ?? __CLASS__ );
	}
	
	/**
	 *  Get current handler's priority, if set, defaults to 0
	 *  
	 *  @return int
	 */
	public function getPriority() : int {
		return $this->priority;
	}
	
	/**
	 *  Set current handler's priority, if not fixed, returns true on success
	 *  
	 *  @param int		$priority New handler priority
	 *  @return bool
	 */
	public function setPriority( int $priority = 0 ) : bool {
		if ( $this->fixed_priority ) {
			return false;
		}
		$this->priority = $priority;
		return true;
	}
	
	/**
	 *  Get preset or post-execution handler output
	 *  
	 *  @return array
	 */
	public function getOutput( string $name ) : array {
		return $this->output[$name] ?? [];
	}
	
	/**
	 *  Accept notification from event
	 *  
	 *  @param SplSubject	$event Description for $event
	 *  @param array	$params Description for $params
	 */
	abstract public function update( \SplSubject $event, ?array $params = null ) : void;
}

