<?php declare( strict_types = 1 );

namespace Notes;

class LocalLoginProvider extends IDProvider {
	
	protected readonly \Notes\UserAuth $auth;
	
	public function __construct( \Notes\Controller $ctrl ) {
		parent::__construct( $ctrl );
		
		$this->name = "LocalLoginProvider";
	}
	
	/**
	 *  Reset authenticated user data types for processing
	 *  
	 *  @param array	$user	Stored user in database/session
	 *  @return array
	 */
	public function formatAuthUser( array $user ) : array {
		$user['is_approved']	??= false;
		$user['is_locked']	??= false;
		$user['user_settings']	??= [];
		
		return [
			'id'		=> ( int ) ( $user['id'] ?? 0 ), 
			'status'	=> ( int ) ( $user['status'] ?? 0 ), 
			'name'		=> $user['name'] ?? '', 
			'hash'		=> $user['hash'] ?? '',
			'is_approved'	=> $user['is_approved'] ? true : false,
			'is_locked'	=> $user['is_locked'] ? true : false, 
			'auth'		=> $user['auth'] ?? '',
			'settings'	=> 
			\is_array( $user['user_settings'] ) ? 
				$user['user_settings'] : []
		];
	}
	
	public function logout() {
		$sess	= $this->getControllerParam( '\\Notes\\SHandler' );
		$sess->deleteCookie( 'auth' );
		$sess->sessionCheck( true );
		
		$this->auth->resetLookup();
	}
	
	public function initUserAuth( \Notes\User $user ) {
		// TOOD: Preload with given username
		$this->auth = new \Notes\UserAuth( $this->controller );
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
		$user = $this->auth->findUserByUsername( $username );
		
		// No user found?
		if ( empty( $user ) ) {
			$status = AuthStatus::NoUser;
			return null;
		}
		
		// Verify credentials
		if ( \Notes\User::verifyPassword( $password, $user->password ) ) {
			
			// Refresh password if needed
			if ( \Notes\User::passNeedsRehash( $user->password ) ) {
				\Notes\User::savePassword( $user->id, $password );
			}
			
			$status = AuthStatus::Success;
			$this->initUserAuth( $user );
			$this->auth->updateUserActivity( 'login' );
			return $user;
		}
		
		// Login failiure
		$status = AuthStatus::Failed;
		return null;
	}
	
}
