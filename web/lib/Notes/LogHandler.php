<?php declare( strict_types = 1 );

namespace Notes;

class LogHandler extends Handler {
	
	public function __construct( \Notes\Controller $ctrl ) {
		parent::__construct( $ctrl, -1 );
		
		// Make sure session is active
		$this->getControllerParam( '\\\Notes\\SHandler' )->sessionCheck();
		
		// Application-level logging
		\set_error_handler( 
			[ $this, 'errorLog' ], 
			\E_USER_ERROR | \E_USER_WARNING | 
				\E_USER_NOTICE | \E_USER_DEPRECATED
		);
		
		// Event-level logging
		$this->controller->listen( 'save_log', $this );
		$this->controller->run( 
			'log_handler_loaded', [ 'handler' => $this ] 
		);
	}
	
	public function __destruct() {
		\restore_error_handler();
		parent::__destruct();
	}
	
	/**
	 *  Find list of logs from starting and ending ranges
	 *  
	 *  @param array	$range	Starting and ending date, if given
	 *  @param string	$label	Optional log label type
	 *  @param string	$search	Optional content search
	 *  @return array
	 */
	public function getLogs( 
		array $range, 
		?string	$label		= null,
		?string $search 	= null 
	) : array {
		// TODO: Log search
		return [];
	}
	
	/**
	 *  Create and save new log
	 *  
	 *  @param string	$label	Log type description
	 *  @param string	$msg	Searchable log body
	 *  @return bool
	 */
	public function createLog( string $label, string $msg ) : bool {
		$log		= new \Notes\Log( $this->controller );
		$log->label	= $label;
		
		$log->setBody( \Notes\Util::entities( 
			\Notes\Util::unifySpaces( $msg ) 
		) );
		
		return $log->save();
	}
	
	/**
	 *  Handle application-level error/notice logging
	 *  
	 *  @param int		$eno	Error logging level
	 *  @param string	$emsg	Log message
	 */
	public function errorLog( $eno, $emsg ) {
		$label = 
		match( $eno ) {
			\E_USER_ERROR		=> 'error',
			\E_USER_WARNING		=> 'warning',
			\E_USER_NOTICE		=> 'notice',
			\E_USER_DEPRECATED	=> 'deprecated',
			default			=> 'unkown'
		};
		
		if ( $this->createLog( $label, $emsg ) ) {
			return true;
		}
		$emsg	= 
		\Notes\Util::entities( 
			\Notes\Util::unifySpaces( $emsg ) 
		);
		
		$this->error( 
			'Failed errorLog() ' . 
			'Label: ' . $label . ' Messgae: ' . $msg 
		);
		return false;
	}
	
	/**
	 *  Handle event-level loging
	 *  
	 *  @param array	$param	Passed event parameters
	 */
	protected function saveLog( array $params ) {
		$params['label']	??= 'unkown';
		$params['message']	??= '';
		
		$create = 
		$this->createLog( $params['label'], $params['message'] );
		
		if ( $create ) {
			$this->controller->run( 
				'log_save_success', $params 
			);
			return;
		}
		
		$this->error( 
			'Failed event-level log save ' . 
			'Label: ' . $params['label'] . 
			' Messgae: ' . $params['message']
		);
		
		$this->controller->run( 'log_save_failed', $params );
	}
	
	public function notify( \SplSubject $event, ?array $params = null ) {
		$params	??= $event->getParams();
		
		switch ( $event->getName() ) {
			case 'save_log':
				$this->saveLog( $params );
				break;
		}
	}
}

