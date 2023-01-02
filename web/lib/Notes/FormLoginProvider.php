<?php declare( strict_types = 1 );

namespace Notes;

class FormLoginProvider extends IDProvider {
	
	public function __construct( \Notes\Controller $ctrl ) {
		parent::__construct( $ctrl );
		
		$this->name = "FormLoginProvider";
	}
	
	/**
	 *  Login user by credentials
	 *  
	 *  @param string		$username	Login name to search
	 *  @param string		$password	User provided password
	 *  @param \Notes\AuthStatus	$status		Authentication success etc...
	 *  @return \Notes\User
	 */
	public function login(
		string			$username,
		string			$password,
		\Notes\AuthStatus	&$status
	) : ?\Notes\User {
		$ua	= new \Notes\UserAuth( $this->controller );
		$auth	= $ua->findUserByUsername( $username );
		
		// No user found?
		if ( empty( $auth ) ) {
			static::sendNoUser( $auth );
			return null;
		}
		
		// Verify credentials
		if ( !\Notes\User::verifyPassword( $password, $auth->password ) ) {
			return static::sendFailed( $status );
		}
		
		
		$status = AuthStatus::Success;
		$user	= $this->initUserAuth( $auth );
		
		// Refresh password if needed
		$this->refreshPassword( $data[1] );
		$this->auth->updateUserActivity( 'login' );
		return $user;
	}
	
}
