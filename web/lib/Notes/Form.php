<?php declare( strict_types = 1 );

namespace Notes;

abstract class Form extends Controllable {
	
	/**
	 *  Current form definition type
	 *  @var \Notes\FormType
	 */
	public readonly \Notes\FormType $form_type;
	
	
	abstract public function render( array $data ) : string {}
	
	/**
	 *  Initiate field token or reset existing
	 *  
	 *  @param \Notes\Config	$config		Current handler configuration
	 *  @param bool			$reset		Reset token
	 *  @return string
	 */ 
	public static function tokenKey( 
		\Notes\Config	$config, 
		bool		$reset		= false 
	) : string {
		$config->getControllerParam( '\\Notes\\SHandler' )
			->sessionCheck();
		
		if ( empty( $_SESSION['TOKEN_KEY'] ) || $reset ) {
			$_SESSION['TOKEN_KEY'] = \Notes\Util::genId( 16 );
		}
		
		return $_SESSION['TOKEN_KEY'];
	}
	
	/**
	 *  Generate a hash for meta data sent to HTML forms
	 *  
	 *  This function helps prevent tampering of metadata sent separately
	 *  to the user via other hidden fields
	 *  
	 *  @example genMetaKey( [ 'id=12','name=DoNotChange' ] ); 
	 *  
	 *  @param \Notes\Config	$config		Current handler configuration
	 *  @param array		$args		Form field names sent to generate key
	 *  @param bool			$reset		Reset any prior token key if true
	 *  @return string
	 */
	public static function genMetaKey( 
		\Notes\Config	$config,
		array		$args, 
		bool		$reset		= false 
	) : string {
		static $gen	= [];
		$data		= \implode( ',', $args );
		
		if ( \array_key_exists( $data, $gen ) && !$reset ) {
			return $gen[$data];
		}
		
		$ha		= $config->hashAlgo( 'nonce_hash', 'tiger160,4' );
		$gen[$data]	= 
		\base64_encode( 
			\hash( $ha, $data . static::tokenKey( $config, $reset ), true ) 
		);
		
		return $gen[$data];
	}
	
	/**
	 *  Verify meta data key
	 *  
	 *  @param \Notes\Config	$config		Current handler configuration
	 *  @param string		$key		Token key name
	 *  @param array		$args		Original form field names sent to generate key
	 *  @return bool				True if token matched
	 */
	public static function verifyMetaKey( 
		\Notes\Config 	$config, 
		string		$key, 
		array		$args 
	) : bool {
		if ( empty( $key ) ) {
			return false;
		}
		
		$info	= \base64_decode( $key, true );
		if ( false === $info ) {
			return false;
		}
		
		$data	= \implode( ',', $args );
		$ha	= $config->hashAlgo( 'nonce_hash', 'tiger160,4' );
	
		return 
		\hash_equals( 
			$info, 
			\hash( $ha, $data . static::tokenKey( $config ), true ) 
		);
	}
	
	/**
	 *  Create a unique nonce and token pair for form validation and meta key
	 *  
	 *  @param \Notes\Config	$config		Current handler configuration
	 *  @param string		$name		Form label for this pair
	 *  @param array		$fields		If set, append form anti-tampering token
	 *  @param bool			$reset		Reset any prior anti-tampering token key if true
	 *  @return array
	 */
	public static function genNoncePair( 
		\Notes\Config	$config,
		string		$name, 
		array		$fields		= [], 
		bool		$reset		= false 
	) : array {
		$tb	= $config->setting( 'token_bytes', 'int' );
		$ha	= $config->hashAlgo( 'nonce_hash', 'tiger160,4' );
		
		$nonce	= \Notes\Util::genId( \Notes\Util::intRange( $tb, 8, 64 ) );
		$time	= time();
		$data	= $name . $time;
		$token	= "$time:" . \hash( $ha, $data . $nonce );
		return [ 
			'token' => \base64_encode( $token ), 
			'nonce' => $nonce,
			'meta'	=> 
				empty( $fields ) ? 
				'' : static::genMetaKey( $config, $fields, $reset )
		];
	}
	
	/**
	 *  Verify form submission by checking sent token and nonce pair
	 *  
	 *  @param \Notes\Config	$config		Current handler configuration
	 *  @param string		$name	Form label to validate
	 *  @params string		$token	Sent token
	 *  @params string		$nonce	Sent nonce
	 *  @param bool			$chk	Check for form expiration if true
	 *  @return int
	 */
	public static function verifyNoncePair(
		\Notes\Config	$config,
		string		$name, 
		string		$token, 
		string		$nonce,
		bool		$chk
	) : \Notes\FormStatus {
		
		$ln	= \strlen( $nonce );
		$lt	= \strlen( $token );
		
		// Sanity check
		if ( 
			$ln > 100 || 
			$ln <= 10 || 
			$lt > 350 || 
			$lt <= 10
		) {
			return \Notes\FormStatus::Invalid;
		}
		
		// Open token
		$token	= \base64_decode( $token, true );
		if ( false === $token ) {
			return \Notes\FormStatus::Invalid;
		}
		
		// Token parameters are intact?
		if ( false === \strpos( $token, ':' ) ) {
			return \Notes\FormStatus::Invalid;
		}
		
		$parts	= \explode( ':', $token );
		$parts	= \array_filter( $parts );
		if ( \count( $parts ) !== 2 ) {
			return \Notes\FormStatus::Invalid;
		}
		
		if ( $chk ) {
			// Check for flooding
			$time	= time() - ( int ) $parts[0];
			$fdelay	= $config->setting( 'form_delay', 'int' );
			if ( $time < $fdelay ) {
				return \Notes\FormStatus::Flood;
			}
			
			// Check for form expiration
			$fexp	= $config->setting( 'form_expire', 'int' );
			if ( $time > $fexp ) {
				return \Notes\FormStatus::Expired;
			}
		}
		
		$ha	= $config->hashAlgo( 'nonce_hash', 'tiger160,4' );
		$data	= $name . $parts[0];
		$check	= \hash( $ha, $data . $nonce );
		
		return \hash_equals( $parts[1], $check ) ? 
			\Notes\FormStatus::Valid : 
			\Notes\FormStatus::Invalid;
	}
	
	/**
	 *  Validate sent token/nonce pairs in sent form data
	 *  
	 *  @param \Notes\Config	$config		Current handler configuration
	 *  @param string		$name		Form label to validate
	 *  @param bool			$get		Validate get request if true
	 *  @param bool			$chk		Check for form expiration if true
	 *  @param array		$fields		If set, verify form anti-tampering token
	 *  @return \Notes\FormStatus
	 */
	public static function validateForm(
		\Notes\Config	$config,
		string		$name, 
		bool		$get		= true,
		bool		$chk		= true,
		array		$fields		= []
	) : \Notes\FormStatus {
		$filter = [
			'token'	=> \FILTER_SANITIZE_FULL_SPECIAL_CHARS,
			'nonce'	=> \FILTER_SANITIZE_FULL_SPECIAL_CHARS,
			'meta'	=> \FILTER_SANITIZE_FULL_SPECIAL_CHARS
		];
		
		$data	= $get ? 
		\filter_input_array( \INPUT_GET, $filter ) : 
		\filter_input_array( \INPUT_POST, $filter );
		
		if ( empty( $data['token'] ) || empty( $data['nonce'] ) ) {
			return \Notes\FormStatus::Invalid;
		}
		
		if ( empty( $fields ) ) {
			return 
			static::verifyNoncePair( $config, $name, $data['token'], $data['nonce'], $chk );
		}
		
		// If fields were set, meta key was generated
		// Check if it's still there
		if ( !static::verifyMetaKey( $config, $data['meta'] ?? '', $fields ) ) {
			return \Notes\FormStatus::Invalid;
		}
		
		return 
		static::verifyNoncePair( $config, $name, $data['token'], $data['nonce'], $chk );
	}

}

