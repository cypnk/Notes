<?php declare( strict_types = 1 );

namespace Notes;

class DigestLoginProvider extends IDProvider {
	
	protected readonly \Notes\UserAuth $auth;
	
	public function __construct( \Notes\Controller $ctrl ) {
		parent::__construct( $ctrl );
		
		$this->name = "DigestLoginProvider";
	}
	
	/**
	 *  Create hash for user with given credentials and current realm
	 */
	public function userHash( string $username, string $password ) {
		return 
		\hash( 'sha256', \strtr( 
			'{username}:{realm}:{password}', [
				'{username}'	=> $username,
				'{realm}'	=> $this->realm,
				'{password}'	=> $password
			] 
		) );
	}
	
	/**
	 *  Initialize authentication helper with basic settings
	 */
	public function initUserAuth( \Notes\User $user ) {
		$auth			= new \Notes\UserAuth( $this->controller );
		$auth->user_id		= $user->id;
		$auth->is_locked	= $user->is_locked;
		$auth->is_approved	= $user->is_approved;
		$auth->hash		= $user->hash;
		
		$this->auth	 	= $auth;
	}
	
	/**
	 *  User login by digest challenge response
	 *  
	 *  @param string		$name		Lookup username
	 *  @param string		$response	Digest response hash
	 *  @param string		$cnonce		Client generated nonce
	 *  @param string		$qop		Quality of protection
	 *  @param string		$nc		Request count
	 *  @param string		$method		Response method (GET/POST)
	 *  @param string		$uri		Current authentication realm URI
	 *  @param string		$nonce		One time use token
	 *  @param \Notes\AuthStatus	$status		Authentication success etc...
	 */
	public function login(
		string			$name, 
		string			$response, 
		string			$cnonce,
		string			$method, 
		string			$uri,
		string			$nonce
		\Notes\AuthStatus	&$status
	) {
		$user = $this->auth->findUserByName( 'name', $name );
		
		// No user found?
		if ( empty( $user ) ) {
			$status = AuthStatus::NoUser;
			return null;
		}
		
		// Aut with method and URI
		$ha2	= 
		\hash( 'sha256', \strtr( 
			'{method}:{uri}', [
				'{method}'	=> \strtoupper( $method ),
				'{uri}'		=> \Notes\Util::slashPath( $uri )
			] 
		) );
		
		$test	= 
		\hash( 'sha256', \strtr(
			'{ha1}:{nonce}:{nc}:{cnonce}:{qop}:{ha2}', [
				'{ha1}'		=> $user->hash,
				'{ha2}'		=> $ha2,
				'{nc}'		=> $nc,
				'{qop}'		=> $qop,
				'{cnonce}'	=> $cnonce,
				'{nonce}'	=> $nonce
			]
		) );
		
		if ( \hash_equals( $response, $test ) ) {
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

