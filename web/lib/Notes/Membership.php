<?php declare( strict_types = 1 );

namespace Notes;

class Membership extends Handler {
	
	protected readonly \Notes\IDProvider $provider;
	
	public function __construct( \Notes\Controller $ctrl ) {
		parent::__construct( $ctrl );
		$this->getControllerParam( '\\\Notes\\SHandler' )->sessionCheck();
		
		$this->controller->listen( 'login', $this );
		$this->controller->listen( 'register', $this );
		$this->controller->listen( 'profile', $this );
		$this->controller->run( 
			'membership_handler_loaded', [ 'handler' => $this ] 
		);
		
	}
	
	protected function registerRoutes( array $params ) {
		
	}
	
	protected function login( array $params ) {
		
		$this->controller->run( 'login_run', $params );
	}
	
	protected function register( array $params ) {
		
		$this->controller->run( 'register_run', $params );
	}
	
	protected function profile( array $params ) {
		
		$this->controller->run( 'profile_run', $params );
	}
	
	public function notify( \SplSubject $event, ?array $params = null ) {
		$params	??= $event->getParams();
		
		switch ( $event->getName() ) {
				
			case 'web_start':
				$this->controller->listen( 'register_routes', $this );
				$this->registerRoutes( $params );
				break;
				
			case 'login':
				
				break;
				
			case 'register':
				
				break;
				
			case 'profile':
				
				break;
		}
	}
}

