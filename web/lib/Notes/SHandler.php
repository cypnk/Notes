<?php declare( strict_types = 1 );

namespace Notes;

class SHandler extends Controllable {
	
	/**
	 *  Session settings
	 */
	
	/**
	 *  Staleness check
	 */
	const SESSION_EXP	= 300;
	
	/**
	 *  ID random bytes
	 */
	const SESSION_BYTES	= 12;
	
	
	/**
	 *  Cookie defaults
	 */
	
	/**
	 *  Base expiration
	 */
	const COOKIE_EXP	= 86400;
	
	/**
	 *  Base domain path
	 */
	const COOKIE_PATH	= '/';
	
	/**
	 *  Restrict cookies to same-site origin
	 *  I.E. No third party can snoop
	 */
	const COOKIE_RESTRICT	= 1;
	
	/**
	 *  Use prefixed cookies for additional privacy
	 *  Leaving this on is highly recommended, 
	 *  	but it may break some analytics/ad services
	 */
	const COOKIE_PREFIXED	= 1;
	
	public function __construct( Controller $ctrl ) {
		parent::__construct( $ctrl );
		
		if ( \headers_sent() ) {
			$this->errors[] = 
			'Session handler created after headers sent';
			return;
		}
		
		\session_set_save_handler(
			[ $this, 'sessionOpen' ], 
			[ $this, 'sessionClose' ], 
			[ $this, 'sessionRead' ], 
			[ $this, 'sessionWrite' ], 
			[ $this, 'sessionDestroy' ], 
			[ $this, 'sessionGC' ], 
			[ $this, 'sessionCreateID' ]
		);
	}
	
	public function __destruct() {
		if ( \session_status() === \PHP_SESSION_ACTIVE ) {
			\session_write_close();
		}
		parent::__destruct();
	}
	
	/**
	 *  Does nothing
	 */
	public function sessionOpen( $path, $name ) { return true; }
	public function sessionClose() { return true; }
	
	/**
	 *  Does nothing
	 */
	public function sessionOpen( $path, $name ) { return true; }
	public function sessionClose() { return true; }
	
	/**
	 *  Create session ID in the database and return it
	 *  
	 *  @return string
	 */
	public function sessionCreateID() {
		static $sql	= 
		"INSERT OR IGNORE INTO sessions ( session_id )
			VALUES ( :id );";
		
		$config	= $this->getControllerParam( '\\\Notes\\Config' );
		$db	= $this->getControllerParam( '\\\Notes\\Data' );
		$bt	= $config->setting( 'session_bytes', 'int' );
		$id	= \Notes\Util::genId( $bt );
		if ( $db->dataExec( $sql, [ ':id' => $id ], 'success', \SESSIONS ) ) {
			return $id;
		}
		
		// Something went wrong with the database
		$this->errors[] = 'Error writing to session ID to database';
		die();
	}
	
	/**
	 *  Delete session
	 *  
	 *  @return bool
	 */
	public function sessionDestroy( $id ) {
		$sql	= "DELETE FROM sessions WHERE session_id = :id;";
		$db	= $this->getControllerParam( '\\\Notes\\Data' );
		if ( $db->dataExec( 
			$sql, [ ':id' => $id ], 'success', \SESSIONS 
		) ) {
			return true;
		}
		return false;
	}
	
	/**
	 *  Session garbage collection
	 *  
	 *  @return bool
	 */
	public function sessionGC( $max ) {
		$sql	= 
		"DELETE FROM sessions WHERE (
			strftime( '%s', 'now' ) - 
			strftime( '%s', updated ) ) > :gc;";
		$db	= $this->getControllerParam( '\\\Notes\\Data' );
		if ( $db->dataExec( $sql, [ ':gc' => $max ], 'success', \SESSIONS ) ) {
			return true;
		}
		return false;
	}
	
	/**
	 *  Read session data by ID
	 *  
	 *  @return string
	 */
	public function sessionRead( $id ) {
		static $sql	= 
		"SELECT session_data FROM sessions 
			WHERE session_id = :id LIMIT 1;";
		
		$db	= $this->getControllerParam( '\\\Notes\\Data' );
		$out	= 
		$db->dataExec( $sql, [ 'id' => $id ], 'column', \SESSIONS );
		
		$this->controller->run( 
			'sessionread', 
			[ 'id' => $id, 'data' => $out ]
		);
		
		return empty( $out ) ? '' : ( string ) $out;
	}
	
	/**
	 *  Store session data
	 *  
	 *  @return bool
	 */
	public function sessionWrite( $id, $data ) {
		static $sql	= 
		"REPLACE INTO sessions ( session_id, session_data )
			VALUES( :id, :data );";
		
		$db	= $this->getControllerParam( '\\\Notes\\Data' );
		if ( $db->dataExec( 
			$sql, [ ':id' => $id, ':data' => $data ], 'success', \SESSIONS 
		) ) {
			$this->controller->run( 
				'sessionwrite', 
				[ 'id' => $id, 'data' => $data ]
			);
			return true;
		}
		
		return false;
	}
	
	/**
	 *  Session functionality
	 */
	
	/**
	 *  Set session cookie parameters
	 *  
	 *  @return bool
	 */
	public function sessionCookieParams() : bool {
		$options		= $this->defaultCookieOptions();
		$config			= $this->getControllerParam( '\\\Notes\\Config' );
		// Override some defaults
		$options['lifetime']	=  
			$config->setting( 'cookie_exp', static::COOKIE_EXP, 'int' );
		unset( $options['expires'] );
	
		$this->controller->run( 'sessioncookieparams', $options );
		return \session_set_cookie_params( $options );
	}
	
	/**
	 *  Initiate a session if it doesn't already exist
	 *  Optionally reset and destroy session data
	 *  
	 *  @param bool		$reset		Reset session ID if true
	 */
	public function session( $reset = false ) {
		if ( \session_status() === \PHP_SESSION_ACTIVE && !$reset ) {
			return;
		}
		
		if ( \session_status() !== \PHP_SESSION_ACTIVE ) {
			\session_cache_limiter( '' );
			
			$this->sessionCookieParams();
			$config = $this->controller->getConfig();
			\session_name( 
				$this->cookiePrefix() . 
				$config->realmName() 
			);
			\session_start();
			
			$this->controller->run(
				'sessioncreated', 
				[ 'id' => \session_id() ] 
			);
		}
		
		if ( $reset ) {
			\session_regenerate_id( true );
			foreach ( \array_keys( $_SESSION ) as $k ) {
				unset( $_SESSION[$k] );
			}
			$this->controller->run(
				'sessiondestroyed', [] 
			);
		}
	}
	
	
	/**
	 *  Session owner and staleness marker
	 *  
	 *  @link https://paragonie.com/blog/2015/04/fast-track-safe-and-secure-php-sessions
	 *  
	 *  @param string	$visit	Previous random visitation identifier
	 */
	public function sessionCanary( string $visit = '' ) {
		$config	= $this->getControllerParam( '\\\Notes\\Config' );
		$bt	= $config->setting( 'session_bytes', static::SESSION_BYTES, 'int' );
		$exp	= $config->setting( 'session_exp', static::SESSION_EXP, 'int' );
	
		$_SESSION['canary'] = 
		[
			'exp'		=> time() + $exp,
			'visit'		=> 
			empty( $visit ) ? \Notes\Util::genId( $bt ) : $visit
		];
	}
	
	/**
	 *  Check session staleness
	 *  
	 *  @param bool		$reset	Reset session and canary if true
	 */
	public function sessionCheck( bool $reset = false ) {
		$this->session( $reset );
		
		if ( empty( $_SESSION['canary'] ) ) {
			$this->sessionCanary();
			return;
		}
		
		if ( time() > ( int ) $_SESSION['canary']['exp'] ) {
			$visit = $_SESSION['canary']['visit'];
			\session_regenerate_id( true );
			$this->sessionCanary( $visit );
		}
	}
	
	/**
	 *  Helpers
	 */
	
	/**
	 *  End current session activity
	 */
	public function cleanSession() {
		if ( \session_status() === \PHP_SESSION_ACTIVE ) {
			\session_unset();
			\session_destroy();
			\session_write_close();
		}
	}
	
	/**
	 *  Samesite cookie origin setting
	 *  
	 *  @return string
	 */
	public function sameSiteCookie() : string {
		$config	= $this->getControllerParam( '\\\Notes\\Config' );
		if ( $config->setting( 
			'cookie_restrict', static::COOKIE_RESTRICT, 'bool' 
		) ) {
			return 'Strict';
		}
		
		return $config->getRequest()->isSecure() ? 'None' : 'Lax';
	}
	
	/**
	 *  Prefixed cookie name helper
	 *  
	 *  @return string
	 */
	public function cookiePrefix() : string {
		static $prefix;
		if ( isset( $prefix ) ) {
			return $prefix;
		}
		
		$config	= $this->getControllerParam( '\\\Notes\\Config' );
		
		if ( !$config->setting( 
			'cookie_prefixed', static::COOKIE_PREFIXED, 'bool' 
		) ) {
			$prefix = '';
			return '';
		}
		
		$secure	= $config->getRequest()->isSecure();
		$cpath	= $config->setting( 'cookie_path', static::COOKIE_PATH );
		
		// Enable locking if connection is secure and path is '/'
		$prefix	= 
		( 0 === \strcmp( $cpath, '/' ) && $secure ) ? 
			'__Host-' : ( $secure ? '__Secure-' : '' );
		
		return $prefix;
	}
	
	/**
	 *  Set the cookie options when defaults are/aren't specified
	 *  
	 *  @param array	$options	Additional cookie options
	 *  @return array
	 */
	public function defaultCookieOptions( array $options = [] ) : array {
		$config	= $this->getControllerParam( '\\\Notes\\Config' );
		$cexp	= $config->setting( 'cookie_exp', static::COOKIE_EXP, 'int' );
		$cpath	= $config->setting( 'cookie_path', static::COOKIE_PATH );
		
		$req	= $config->getRequest();
		$opts	= 
		\array_merge( $options, [
			'expires'	=> 
				( int ) ( $options['expires'] ?? time() + $cexp ),
			'path'		=> $cpath,
			'samesite'	=> $this->sameSiteCookie(),
			'secure'	=> $req->isSecure(),
			'httponly'	=> true
		] );
	
		// Domain shouldn't be used when using '__Host-' prefixed cookies
		$prefix = $this->cookiePrefix();
		if ( empty( $prefix ) || 0 === \strcmp( $prefix, '__Secure-' ) ) {
			$opts['domain']	= $req->getHost();
		}
		$this->controller->run(
			'cookieparams', $opts 
		);
		return $opts;
	}
		
	/**
	 *  Get collective cookie data
	 *  
	 *  @param string	$name		Cookie label name
	 *  @param mixed	$default	Default return if cookie isn't set
	 *  @return mixed
	 */
	public function getCookie( string $name, $default ) {
		$config	= $this->getControllerParam( '\\\Notes\\Config' );
		$realm	= $this->cookiePrefix() . $config->realmName();
		if ( !isset( $_COOKIE[$realm] ) ) {
			return $default;
		}
		
		if ( !is_array( $_COOKIE[$realm]) ) {
			return $default;
		}
		
		return $_COOKIE[$realm][$name] ?? $default;
	}
		
	/**
	 *  Set application cookie
	 *  
	 *  @param int		$name		Cookie data label
	 *  @param mixed	$data		Cookie data
	 *  @param array	$options	Cookie settings and options
	 *  @return bool
	 */
	public function makeCookie( 
		string	$name, 
			$data, 
		array	$options = [] 
	) : bool {
		$options	= $this->defaultCookieOptions( $options );
		$this->controller->run(
			'cookieset', [ 
			'name'		=> $name, 
			'data'		=> $data, 
			'options'	=> $options 
			] 
		);
		
		$config	= $this->getControllerParam( '\\\Notes\\Config' );
		return 
		\setcookie( 
			$this->cookiePrefix() . 
			$config->realmName() . "[$name]", 
			$data, 
			$options 
		);
	}
	
	/**
	 *  Remove preexisting cookie
	 *  
	 *  @param string	$name		Cookie label
	 *  @return bool
	 */
	public function deleteCookie( string $name ) : bool {
		$this->controller->run( 'cookiedelete', [ 'name' => $name ] );
		return $this->makeCookie( $name, '', [ 'expires' => 1 ] );
	}
}

