<?php declare( strict_types = 1 );
/**
 *  @file	/web/lib/Notes/HeaderLoginProvider.php
 *  @brief	'WWW-Authenticate' header based login provider
 */

namespace Notes;

class HeaderLoginProvider extends IDProvider {
	
	public function __construct( \Notes\Controller $ctrl ) {
		parent::__construct( $ctrl );
		
		$this->name = "HeaderLoginProvider";
	}
	
	/**
	 *  Generate the suffix component of the hash
	 */
	protected function ha2Hash( array $data, string $method ) : string {
		return 
		\hash( 'sha256', \strtr( 
			'{method}:{uri}', [
				// Request method
				'{method}'	=> \strtoupper( $method ),
				// Current authentication realm URI
				'{uri}'		=> 
				\Notes\Util::slashPath( $data['uri'] ?? '' )
			] 
		) );
	}
	
	/**
	 *  Generate matching hash against client response
	 */
	protected function testHash( 
		array	$data, 
		string	$uhash, 
		string	$method 
	) : string {
		return 
		\hash( 'sha256', \strtr(
			'{ha1}:{nonce}:{nc}:{cnonce}:{qop}:{ha2}', [
				'{ha1}'		=> $uhash,
				
				// Auth hash with method and URI
				'{ha2}'		=> 
					$this->ha2Hash( $data, $method ),
				
				// Request count
				'{nc}'		=> $data['nc'] ?? '',
				
				// Quality of protection
				'{qop}'		=> $data['qop'] ?? '',
				
				// Client generated nonce
				'{cnonce}'	=> $data['cnonce'] ?? '',
				
				// One time use token
				'{nonce}'	=> $data['nonce'] ?? ''
			]
		) );
	}
	
	/**
	 *  Basic HTTP authentication
	 *  
	 *  @param array		$data		Auth header parameters
	 *  @param \Notes\AuthStatus	$status		Authentication success etc...
	 */
	protected function basicLogin( 
		array			$data, 
		\Notes\AuthStatus	&$status 
	) : ?\Notes\User {
		if ( 2 !== count( $data ) ) {
			return static::sendFailed( $status, $this, $data );
		}
		
		$ua	= new \Notes\UserAuth( $this->controller );
		$auth	= $ua->findUserByUsername( $data[0] );
		
		// No user found?
		if ( empty( $auth ) ) {
			return static::sendNoUser( $status, $this, $data );
		}
		
		// Verify credentials
		if ( !\Notes\User::verifyPassword( $data[1], $auth->password ) ) {
			return static::sendFailed( $status, $this, $data );
		}
		
		$status = AuthStatus::Success;
		$user	= $this->initUserAuth( $auth );
		
		// Refresh password if needed
		$this->refreshPassword( $data[1] );
		$this->auth->updateUserActivity( 'login' );
		return $user;
	}
	
	/**
	 *  User login by digest challenge response
	 *  
	 *  @param array		$data		Auth header parameters
	 *  @param \Notes\Request	$request	Current visitor
	 *  @param \Notes\AuthStatus	$status		Authentication success etc...
	 *  @return \Notes\User
	 */
	protected function digestLogin( 
		array			$data,
		\Notes\Request		$request, 
		\Notes\AuthStatus	&$status
	) : ?\Notes\User {
		// Nothing to find or match
		if ( empty( $data['username'] ) || $data['response'] ) {
			return static::sendFailed( $status, $this, $data );
		}
		
		// Check against URI, if given
		if ( empty( $data['uri'] ) ) {
			return static::sendFailed( $status, $this );
		} else {
			
			// URI mismatch?
			if ( 0 !== \strcasecmp( 
				$data['uri'], 
				\Notes\Util::slashPath( $request->getUri() ) 
			) ) {
				return static::sendFailed( $status, $this );
			}
		}
		
		$ua	= new \Notes\UserAuth( $this->controller );
		
		// Lookup username
		$auth	= $ua->findUserByUsername( $data['username'] );
		
		// No user found?
		if ( empty( $auth ) ) {
			return static::sendNoUser( $status, $this, $data );
		}
		
		// Digest response hash
		$test	= 
		$this->testHash( $data, $auth->hash, $request->getMethod() );
		
		// Response matches
		if ( \hash_equals( $data['response'], $test ) ) {
			$status	= \Notes\AuthStatus::Success;
			$user	= $this->initUserAuth( $auth );
			$this->auth->updateUserActivity( 'login' );
			return $user;
		}
		
		// Login failiure
		return static::sendFailed( $status, $this, $data );
	}
	
	/**
	 *  Sort authentication schemes
	 *  
	 *  @param \Notes\AuthStatus	$status		Authentication success etc...
	 *  @param bool			$upstatus	Update auth status
	 *  @return \Notes\User
	 */
	public function login(
		\Notes\AuthStatus	&$status, 
		bool			$upstatus	= true
	) : ?\Notes\User {
		$auth	= $this->authHeader();
		if ( empty( $auth ) ) {
			$this->auth_type = \Notes\AuthType::Unknown;
			return static::sendNoUser( $status, $this );
		}
		
		$data			= [];
		$req			= 
		$this->getControllerParam( '\\Notes\\Config' )
				->getRequest();
		
		// Set authentication scheme
		$this->auth_type	= \Notes\AuthType::mode( $auth, $req->getMethod() );
		$data			= 
		match( $this->auth_type ) {
			\Notes\AuthType::Basic		=> static::paramFilter( $auth, 'basic' ),
			\Notes\AuthType::Digest		=> static::paramFilter( $auth, 'digest' ),
			\Notes\AuthType::External	=> [],	// TODO
			
			default				=> []
		};
		
		$this->controller->run( 
			'user_login_start', [ 
				'provider'	=> $this, 
				'data'		=> $data
			]
		);
		
		// Nothing to use?
		if ( empty( $data ) ) {
			return static::sendFailed( $status, $this );
		}
		
		$user =
		match( $this->auth_type ) {
			\Notes\AuthType::Basic	=> $this->basicLogin( $data, $status ),
			\Notes\AuthType::Digest	=> $this->digestLogin( $data, $req, $status ),
			
			// Unknown/Unsupported auth type
			default		=> null
		};
		
		if ( empty( $user ) ) {
			return static::sendFailed( $status, $this );
		}
		
		// Also update status?
		if ( $upstatus )
		    	$this->authStatus( $status, $user );
		}
		return $user;
	}	
}


