<?php declare( strict_types = 1 );

namespace Notes;

class IDProvider extends Provider {
	
	protected readonly \Notes\UserAuth $_auth;
	
	protected static $insert_sql		= 
	"INSERT INTO id_providers ( sort_order, settings, label )
		VALUES( :so, :settings, :label );";
	
	protected static $update_sql		= 
	"UPDATE id_providers SET sort_order = :so, settings = :settings 
		WHERE id = :id";
	
	protected static array $settings_base	= [
		'setting_id'	=> '',
		'realm'		=> 'http://localhost',
		'scope'		=> 'local',
		'report'	=> '',
		'authority'	=> ''
	];
	
	public function __set( $name, $value ) {
		switch( $name ) {
			case 'auth':
				$this->setAuth( $value );
				break;
		}
		
		parent::__set( $name, $value );
	}
	
	protected function setAuth( $auth ) {
		// Intercept any setting warnings/notices
		\set_error_handler( function( $eno, $emsg, $efile, $eline ) {
			logException( new \ErrorException( $emsg, 0, $eno, $efile, $eline ) );
		}, \E_WARNING | \E_USER_NOTICE | \E_USER_WARNING );
		
		if ( isset( $this->_auth ) ) {
			\trigger_error( 'Attempt to override previously set auth', \E_USER_WARNING );
			\restore_error_handler();
			return;
		}
		
		if ( $auth \instanceof \Notes\UserAuth ) {
			$this->_auth = $auth;
			return;
		}
		
		\trigger_error( 'Attempt to set auth not of type Notes\\UserAuth', \E_USER_WARNING );
		\restore_error_handler();
	}
	
	/**
	 *  Initialize authentication helper with basic settings
	 */
	protected function initUserAuth( \Notes\UserAuth $auth ) : \Notes\User {
		$res	= 
		$this->getControllerParam( '\\\Notes\\Data' )->getResults( 
			"SELECT * FROM user_view", [ ':id' => $auth->user_id ], \DATA, 
			'class|\\Notes\\User'
		);
		if ( empty( $res ) ) {
			return null;
		}
		
		$user			= $data[0];
		
		// Override hash
		$user->hash		= $auth->hash;
		
		// Set current authentication
		$this->auth	 	= $auth;
		
		return $user;
	}
	
	/**
	 *  Create hash for user with given credentials and current realm
	 *  
	 *  @param string	$username	Client entered username
	 *  @param string	$password	Raw password
	 *  @param string	$realm		Current authentication realm
	 */
	public static function userHash( 
		string	$username, 
		string	$password, 
		string	$realm 
	) : string {
		return 
		\hash( 'sha256', \strtr( 
			'{username}:{realm}:{password}', [
				'{username}'	=> $username,
				'{realm}'	=> $realm,
				'{password}'	=> $password
			] 
		) );
	}
	
	/**
	 *  Set login failure status and return null
	 */
	public static function sendFailed( \Notes\AuthStatus &$status ) {
		$status = \Notes\AuthStatus::Failed;
		return null;
	}
	
	/**
	 *  Set no user found status and return null
	 */
	public static function sendNoUser( \Notes\AuthStatus &$status ) {
		$status = AuthStatus::NoUser;
		return null;
	}
}
