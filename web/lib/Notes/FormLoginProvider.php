<?php declare( strict_types = 1 );

namespace Notes;

class FormLoginProvider extends IDProvider {
	
	public function __construct( \Notes\Controller $ctrl ) {
		parent::__construct( $ctrl );
		
		$this->name		= "FormLoginProvider";
		$this->auth_type	= \Notes\AuthType::Form;
	}
	
	/**
	 *  Login user by credentials
	 *  
	 *  @param string		$username	Login name to search
	 *  @param string		$password	User provided password
	 *  @param \Notes\AuthStatus	$status		Authentication success etc...
	 *  @param bool			$upstatus	Update auth status
	 *  @return \Notes\User
	 */
	public function login(
		string			$username,
		string			$password,
		\Notes\AuthStatus	&$status, 
		bool			$upstatus		= true
	) : ?\Notes\User {
		$ua	= new \Notes\UserAuth( $this->controller );
		$auth	= $ua->findUserByUsername( $username );
		
		$data	= [ 'username' => $username, 'password' => $password ];
		$this->controller->run( 
			'user_login_start', [ 
				'provider'	=> $this, 
				'data'		=> $data
			]
		);
		
		// No user found?
		if ( empty( $auth ) ) {
			return static::sendNoUser( $auth, $this, $data );
		}
		
		// Verify credentials
		if ( !\Notes\User::verifyPassword( $password, $auth->password ) ) {
			return static::sendFailed( $status, $this, $data );
		}
		
		
		$status = \Notes\AuthStatus::Success;
		$user	= $this->initUserAuth( $auth );
		
		// Refresh password if needed
		$this->refreshPassword( $data[1] );
		
		// Also update status?
		if ( $upstatus )
		    	$this->authStatus( $status, $user );
		}
		return $user;
	}
	
}
