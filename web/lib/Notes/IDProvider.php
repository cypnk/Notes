<?php declare( strict_types = 1 );

namespace Notes;

class IDProvider extends Provider {
	
	protected readonly \Notes\UserAuth $auth;
	
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
	
	/**
	 *  Initialize authentication helper with basic settings
	 */
	protected function initUserAuth( \Notes\UserAuth $auth ) : \Notes\User {
		$res	= 
		$this->getControllerParam( '\\\Notes\\Data' )->getResults( 
			"SELECT ", [ ':id' => $auth->user_id ], \DATA, 
			'class|\\Notes\\User'
		);
		if ( empty( $res ) ) {
			return null;
		}
		$user			= $data[0];
		
		$user->is_locked	= $auth->is_locked;
		$user->is_approved	= $auth->is_approved;
		$user->hash		= $auth->hash;
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
