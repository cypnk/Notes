<?php declare( strict_types = 1 );

namespace Notes;

class DigestLoginProvider extends IDProvider {
	
	public function __construct( \Notes\Controller $ctrl ) {
		parent::__construct( $ctrl );
		
		$this->name = "DigestLoginProvider";
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
			return static::sendFailed( $status );
		}
		
		$ua	= new \Notes\UserAuth( $this->controller );
		$auth	= $ua->findUserByUsername( $data[0] );
		
		// No user found?
		if ( empty( $auth ) ) {
			return static::sendNoUser( $status );
		}
		
		// Verify credentials
		if ( !\Notes\User::verifyPassword( $data[1], $auth->password ) ) {
			return static::sendFailed( $status );
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
	 *  @param \Notes\AuthStatus	$status		Authentication success etc...
	 *  @return \Notes\User
	 */
	protected function digestLogin( 
		array			$data
		\Notes\AuthStatus	&$status 
	) : ?\Notes\User {
		
		// Nothing to find or match
		if ( empty( $data['username'] ) || $data['response'] ) {
			return static::sendFailed( $status );
		}
		
		/**
		TODO: Validate against sent URI
		// URI mismatch
		if ( 0 !== \strcasecmp( 
			$data['uri'], 
			\Notes\Util::slashPath( $req->getUri() ) 
		) ) {
			return static::sendFailed( $status );
		}
		*/
		
		$ua	= new \Notes\UserAuth( $this->controller );
		
		// Lookup username
		$auth	= $ua->findUserByUsername( $data['username'] );
		
		// No user found?
		if ( empty( $auth ) ) {
			return static::sendNoUser( $status );
		}
		
		// Digest response hash
		$test	= 
		$this->testHash( $data, $auth->hash, $req->getMethod() );
		
		if ( \hash_equals( $data['response'], $test ) ) {
			$status	= AuthStatus::Success;
			$user	= $this->initUserAuth( $auth );
			$this->auth->updateUserActivity( 'login' );
			return $user;
		}
		
		// Login failiure
		return static::sendFailed( $status );
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
			return static::sendNoUser( $status );
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
			return static::sendFailed( $status );
		}
		
		$data	= 
		\array_map( '\\Notes\\Util::entities', $data );
		
		
		$user =
		\array_key_exists( 'nonce', $data ) ? 
			$this->digestLogin( $data, $status ) :	// Digest auth
			$this->basicLogin( $data, $status );	// Basic auth
		
		// Also update status?
		if ( $upstatus )
		    	$this->authStatus( $status, $user );
		}
		return $user;
	}	
}


