<?php declare( strict_types = 1 );

namespace Notes;

abstract class NamedControllable extends Controllable {
	/**
	 *  Create new runnable with controller and unique name
	 *  
	 *  @param \Notes\Controller	$ctrl	Event controller
	 *  @param string		$name	Current controllable's name
	 */
	public function __construct( 
		\Notes\Controller	$ctrl, 
		string			$name 
	) {
		$this->name		= $name;
		parent::__construct( $ctrl );
	}
}
