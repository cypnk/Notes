<?php declare( strict_types = 1 );

namespace Notes;

class IDProvider extends Provider {
	
	protected readonly \Notes\UserAuth $_auth;
	
	protected readonly \Notes\AuthType $_auth_type;
	
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
		// Intercept any setting warnings/notices
		\set_error_handler( function( $eno, $emsg, $efile, $eline ) {
			logException( new \ErrorException( $emsg, 0, $eno, $efile, $eline ) );
		}, \E_WARNING | \E_USER_NOTICE | \E_USER_WARNING );
		
		switch( $name ) {
			case 'auth':
				$this->setAuth( $value );
				break;
			
			case 'auth_type':
				$this->setAuthType( $value );
				break;
		}
		
		\restore_error_handler();
		parent::__set( $name, $value );
	}
	
	public function __get( $name ) {
		switch ( $name ) {
			case 'auth_type':
				return 
				$this->_auth_type ?? \Notes\AuthType::Unkown;
		}
		return parent::__get( $name );	
	}
	
	protected function setAuth( $auth ) {
		if ( isset( $this->_auth ) ) {
			\trigger_error( 'Attempt to override previously set auth', \E_USER_WARNING );
			return;
		}
		
		if ( $auth \instanceof \Notes\UserAuth ) {
			$this->_auth = $auth;
			return;
		}
		
		\trigger_error( 'Attempt to set auth not of type Notes\\UserAuth', \E_USER_WARNING );
	}
	
	protected function setAuthType( $value ) {
		if ( isset( $this->_auth_type ) ) {
			\trigger_error( 'Attempt to override previously set authentication type', \E_USER_WARNING );
			return;
		}
		if ( $value instanceof \Notes\AuthType ) {
			$this->_auth_type = $value;
			return;
		}
		\trigger_error( 'Attempt to set authentication not of type Notes\\AuthType', \E_USER_WARNING );
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
		
		// Override user hash with current auth hash
		$user->hash		= $auth->hash;
		
		// Set current authentication
		$this->auth	 	= $auth;
		
		return $user;
	}
	
	/**
	 *  Get current user authentication, if set
	 *  
	 *  @return mixed
	 */
	public function getAuth() {
		return $this->_auth ?? null;
	}
	
	/**
	 *  Reset authenticated user data types for processing
	 *  
	 *  @param \Notes\User	$user	Stored user in database
	 *  @param string	$ahash	Current authorization hash
	 *  @return array
	 */
	public static function formatAuthUser( \Notes\User $user, string $ahash ) : array {
		$user->is_approved	??= false;
		$user->is_locked	??= false;
		$user->settings		??= [];
		
		return [
			'id'		=> ( int ) ( $user['id'] ?? 0 ), 
			'status'	=> ( int ) ( $user['status'] ?? 0 ), 
			'name'		=> $user->username ?? '', 
			'auth'		=> $ahash,
			'is_approved'	=> $user->is_approved ? true : false,
			'is_locked'	=> $user->is_locked ? true : false, 
			'hash'		=> $user->hash ?? '',
			'settings'	=> 
				\is_array( $user->settings ) ? $user->settings : []
		];
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
	 *  
	 *  @param \Notes\AuthStatus	$status 	Current authentication status
	 *  @param \Notes\Provider	$provider	Authentication provider
	 *  @param array		$data		Raw data sent by the user
	 */
	public static function sendFailed( 
		\Notes\AuthStatus	&$status, 
		\Notes\Provider		$provider,
		array			$data		= []
	) {
		$status = \Notes\AuthStatus::Failed;
		$provider->getController()->run( 
			'user_login_failed', [ 
				'provider'	=> $provider, 
				'data'		=> $data,
				'status'	=> $status
			]
		);
		return null;
	}
	
	/**
	 *  Set no user found status and return null
	 *  
	 *  @param \Notes\AuthStatus	$status 	Current authentication status
	 *  @param \Notes\Provider	$provider	Authentication provider
	 *  @param array		$data		Raw data sent by the user
	 */
	public static function sendNoUser( 	
		\Notes\AuthStatus	&$status,
		\Notes\Provider		$provider,
		array			$data		= []
	) {
		$status = AuthStatus::NoUser;
		$provider->getController()->run( 
			'user_login_nouser', [ 
				'provider'	=> $provider, 
				'data'		=> $data,
				'status'	=> $status
			]
		);
		return null;
	}
	
	/**
	 *  Refresh and re-save password if current auth credentials are stale
	 *  
	 *  @param string	$password	Current cleartext password
	 */
	protected function refreshPassword( string $password ) {
		if ( !isset( $this->_auth ) ) {
			return;	
		}
		
		if ( \Notes\User::passNeedsRehash( $this->_auth->password ) ) {
			\Notes\User::savePassword( $this->_auth->user_id, $password );
			$this->controller->run( 
				'user_password_rehashed', [ 'provider'	=> $this ]
			);
		}
	}
	
	/**
	 *  Reset current login session
	 */
	public function logout() {
		if ( !isset( $this->_auth ) ) {
			return;	
		}
		
		$sess	= $this->getControllerParam( '\\Notes\\SHandler' );
		$sess->deleteCookie( 'auth' );
		$sess->sessionCheck( true );
		
		$this->_auth->resetLookup();
	}
	
	/**
	 *  Update user authentication activity status
	 *  
	 *  @param \Notes\AuthStatus	$status		Current authentication status
	 *  @param mixed		$user		Of type \Notes\User on validity or null
	 */
	protected function authStatus( \Notes\AuthStatus $status, ?\Notes\User $user ) {
		// Can't update user status
		if ( !isset( $this->_auth ) || empty( $user ) ) {
			return;	
		}
		
		// Don't do anything on duplicate errors
		if ( $status == \Notes\AuthStatus::Duplicate ) {
			return;	
		}
		
		// Everything else
		match( $status ) {
			// Set activity
			\Notes\AuthStatus::Active	=> ( function() {
				$this->_auth->updateUserActivity( 'active' );
				$this->controller->run( 
					'user_active', [ 
						'provider'	=> $this, 
						'user'		=> $user,
						'status'	=> $status
					]
				);
			} )(),
			
			// Login success
			\Notes\AuthStatus::Success	=> ( function() {
				$this->_auth->updateUserActivity( 'login' );
				$this->controller->run( 
					'user_login', [ 
						'provider'	=> $this, 
						'user'		=> $user,
						'status'	=> $status
					]
				);
			} )(),
			
			// Active login failed
			\Notes\AuthStatus::Failed, 
			\Notes\AuthStatus::EmailError,
			\Notes\AuthStatus::PinError	=> ( function() {
				$this->_auth->updateUserActivity( 'failedlogin' );
				$this->controller->run( 
					'user_login_failed', [ 
						'provider'	=> $this, 
						'user'		=> $user,
						'status'	=> $status
					]
				);
			} )(),
			
			// Change approval status
			\Notes\AuthStatus::Unapproved	=> ( function() {
				$this->_auth->updateUserActivity( 'unapprove' );
				$this->controller->run( 
					'user_unapproved', [ 
						'provider'	=> $this, 
						'user'		=> $user,
						'status'	=> $status
					]
				);
			} )(),
			
			// Lock on provider errors
			\Notes\AuthStatus::LockedOut,
			\Notes\AuthStatus::AuthProviderError,
			\Notes\AuthStatus::PermProviderError	=> ( function() {
				$this->_auth->updateUserActivity( 'lock' );
				$this->controller->run( 
					'user_locked', [ 
						'provider'	=> $this, 
						'user'		=> $user,
						'status'	=> $status
					]
				);
			} )(),
			
			// Reset lookup on check errors
			\Notes\AuthStatus::RealmError,
			\Notes\AuthStatus::ScopeError,
			\Notes\AuthStatus::RoleError,
			\Notes\AuthStatus::SessionError,
			\Notes\AuthStatus::UserError,
			\Notes\AuthStatus::CookieError	=> ( function() {
				$this->_auth->resetLookup();
				$this->controller->run( 
					'user_auth_error', [ 
						'provider'	=> $this, 
						'user'		=> $user,
						'status'	=> $status
					]
				);
			} )()
		};
	}
}
