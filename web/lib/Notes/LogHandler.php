<?php declare( strict_types = 1 );

namespace Notes;

class LogHandler extends Controllable {
	
	public function __construct( \Notes\Controller $ctrl ) {
		parent::__construct( $ctrl );
		
		// Make sure session is active
		$this->controller->getSession()->sessionCheck();
		
		// Application-level logging
		\set_error_handler( 
			[ $this, 'errorLog' ], 
			\E_USER_ERROR | \E_USER_WARNING | 
				\E_USER_NOTICE | \E_USER_DEPRECATED
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
	public function creatLog( string $label, string $msg ) : bool {
		$log		= new \Notes\Log( $this->controller );
		$log->label	= $label;
		$log->body	= 
		\Notes\Util::entities( 
			\Notes\Util::unifySpaces( $msg ) 
		);
		
		return $log->save();
	}
	
	public function errorLog( $eno, $emsg ) {
		$label = 
		match( $eno ) {
			\E_USER_ERROR		=> 'error',
			\E_USER_WARNING		=> 'warning',
			\E_USER_NOTICE		=> 'notice',
			\E_USER_DEPRECATED	=> 'deprecated',
			default			=> 'unkown'
		};
		
		if ( $this->creatLog( $label, $emsg ) ) {
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
}

