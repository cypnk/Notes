<?php declare( strict_types = 1 );

namespace Notes;

class RouteHandler extends Handler {
	
	public function __construct( \Notes\Controller $ctrl ) {
		parent::__construct( $ctrl );
		$this->getControllerParam( '\\\Notes\\SHandler' )->sessionCheck();
		$this->controller->listen( 'register_routes', $this );
	}
	
	public function notify( \SplSubject $event, ?array $params = null ) {
		$params	??= $event->getParams();
		
		switch ( $event->getName() ) {
			case 'web_start':
				break;
				
			case 'register_routes':
				break;
		}
	}
}

