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
	public function userHash( string $username, string $password ) : string {
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
	 *  Set login failure status and return null
	 */
	protected function sendFailed( \Notes\AuthStatus &$status ) {
		$status = \Notes\AuthStatus::Failed;
		return null;
	}
	
	/**
	 *  Set no user found status and return null
	 */
	protected function sendNoUser( \Notes\AuthStatus &$status ) {
		$status = AuthStatus::NoUser;
		return null;
	}
	
	/**
	 *  Filtered Authorization header
	 *  
	 *  @return string
	 */
	protected function authHeader() : string {
		// Current request
		$req	= 
		$this
			->getControllerParam( '\\Notes\\Config' )
			->getRequest();
		
		// Find the Authorization header, if set
		$auth	= $req->httpHeaders( true )['authorization'] ?? '';
		
		return \trim( \Notes\Util::unifySpaces( $auth ) );
	}
	
	/**
	 *  Parametized array of authentication data 
	 *  
	 *  @param string	$auth	Raw Authorization header
	 *  @param int		$s	Prefix strip size
	 *  @return array
	 */
	protected function paramFilter( string $auth, int $s ) : array {
		$auth = \substr( $auth, $s );
		if ( empty( $auth ) ) {
			return [];
		}
		
		// Basic auth only?
		if ( false === \strpos( $auth, '=' ) ) {
			$data = \base64_decode( $auth );
			if ( false === $data || empty( $data ) ) {
				return [];
			}
			
			return explode( ':', $data, 2 );
		}
		
		$data = [];
		
		// Unquote, trim, and parse
		\parse_str( \strtr( 
			$auth, [ '"' => '', ' ' => '' ] 
		), $data );
		
		// Fail on duplicates, if any
		foreach ( $data as $v ) {
			if ( \is_array( $v ) ) {
				return [];
			}
		}
		
		return $data;
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
			return $this->sendFailed( $status );
		}
		
		$user = $this->auth->findUserByUsername( $data[0] );
		// No user found?
		if ( empty( $user ) ) {
			return $this->sendNoUser( $status );
		}
		
		// Verify credentials
		if ( !\Notes\User::verifyPassword( $data[1], $user->password ) ) {
			return $this->sendFailed( $status );
		}
		
		// Refresh password if needed
		if ( \Notes\User::passNeedsRehash( $user->password ) ) {
			\Notes\User::savePassword( $user->id, $password );
		}
		
		$status = AuthStatus::Success;
		$this->initUserAuth( $user );
		$this->auth->updateUserActivity( 'login' );
		return $user;
	}
	
	/**
	 *  User login by digest challenge response
	 *  
	 *  @param array		$data		Auth header parameters
	 *  @param \Notes\AuthStatus	$status		Authentication success etc...
	 *  @return \Notes\User
	 */
	protected function digestLogin( 
		array			$data
		\Notes\AuthStatus	&$status 
	) : ?\Notes\User {
		
		// Nothing to find or match
		if ( empty( $data['username'] ) || $data['response'] ) {
			return $this->sendFailed( $status );
		}
		
		/**
		TODO: Validate against sent URI
		// URI mismatch
		if ( 0 !== \strcasecmp( 
			$data['uri'], 
			\Notes\Util::slashPath( $req->getUri() ) 
		) ) {
			return $this->sendFailed( $status );
		}
		*/
		
		// Lookup username
		$user = $this->auth->findUserByName( 'name', $data['username'] );
		
		// No user found?
		if ( empty( $user ) ) {
			return $this->sendNoUser( $status );
		}
		
		// Digest response hash
		$test	= 
		$this->testHash( $data, $user->hash, $req->getMethod() );
		
		if ( \hash_equals( $data['response'], $test ) ) {
			$status = AuthStatus::Success;
			$this->initUserAuth( $user );
			$this->auth->updateUserActivity( 'login' );
			return $user;
		}
		
		// Login failiure
		return $this->sendFailed( $status );
	}
	
	/**
	 *  Sort authentication schemes
	 *  
	 *  @param \Notes\AuthStatus	$status		Authentication success etc...
	 *  @return \Notes\User
	 */
	public function login(
		\Notes\AuthStatus	&$status 
	) : ?\Notes\User {
		$auth	= $this->authHeader();
		if ( empty( $auth ) ) {
			return $this->sendFailed( $status );
		}
		
		$data	= 
		match( true ) {
			// Strip 'Digest ' and filter
			\str_starts_with( $auth, 'Digest ' )	=>  
				$this->paramFilter( $auth, 7 ),
				
			// Strip 'Basic ' and filter
			\str_starts_with( $auth, 'Basic ' )	=> 
				$this->paramFilter( $auth, 6 )
		};
		
		// Nothing to use?
		if ( empty( $data ) ) {
			return $this->sendFailed( $status );
		}
		
		$data	= 
		\array_map( '\\Notes\\Util::entities', $data );
		
		// Digest auth
		if ( \array_key_exists( 'nonce', $data ) ) {
			return $this->digestLogin( $data, $status );
		}
		
		// Basic auth
		return $this->basicLogin( $data, $status );
	}	
}

