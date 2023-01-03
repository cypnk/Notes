<?php declare( strict_types = 1 );

namespace Notes;

enum RepsonseStatus {
	
	// Content success
	case ClientOK		= 200;
	case Created		= 201;
	case Accepted		= 202;
	case NoContent		= 204;
	case ResetContent	= 205;
	case PartialContent	= 206;
	
	// Redirection and location
	case MultipleChoices	= 300;
	case MovedPermanently	= 301;
	case Found		= 302;
	case SeeOther		= 303;
	case NotModified	= 304;
	
	// Client errors
	case BadRequest		= 400;
	case Unauthorized	= 401;
	case Forbidden		= 403;
	case NotFound		= 404;
	case NotAcceptable	= 406;
	case ProxyAuthRequired	= 407;
	case Conflict		= 409;
	case Gone		= 410;
	case LengthRequired	= 411;
	case PreconditionFailed	= 412;
	case PayloadTooLarge	= 413;
	case RequestUriTooLong	= 414;
	case UnsupportedType	= 415;
	
	// Errors
	case InternalError	= 500;
	case NotImplemented	= 501;
	
	// Special cases
	case Options		= 4000;
	case MethodNotAllowed	= 405;
	case RangeError		= 416;
	case Locked		= 423;
	case TooEarly		= 425;
	case TooMany		= 429;
	case TooLarge		= 431;
	case ClientClosed	= 499;
	
	case Unavailable	= 503;
	
	// Authentication
	case Auth		= 1001;
	case AuthBasic		= 1002;
	case AuthDigest		= 1003;
	case AuthDigestStale	= 1004;
	
	/**
	 *  Currently allowed methods
	 */
	public const Methods	= 'GET, POST, HEAD, OPTIONS';
	
	/**
	 *  Basic auth header
	 */
	public const AuthBasicHeader	= 
	'WWW-Authenticate: Basic charset="UTF-8"';
	
	/**
	 *  Digest auth header
	 */
	public const AuthDigestHeader	= 
	'WWW-Authenticate: Digest charset="UTF-8" ' . 
		'realm="{realm}" qop="auth, auth-int", ' . 
		'algorithm=SHA-256, nonce="{nonce}", ' . 
		'opaque="{opaque}"';
	
	/**
	 *  Send the filename, if applicable, of the HTTP status code client view
	 *  
	 *  @return string
	 */
	public function errorFile() : string {
		return 
		match( $this ) {
			ResponseStatus::InternalError, 
			ResponseStatus::NotImplemented, 
			ResponseStatus::Unavailable		=> '50x.html',
			
			ResponseStatus::BadRequest,
			ResponseStatus::Unauthorized,
			ResponseStatus::Forbidden,
			ResponseStatus::NotFound,
			ResponseStatus::Gone,
			ResponseStatus::MethodNotAllowed,
			ResponseStatus::TooMany		=> $this->value . '.html',
			
			default				=> ''
		};
	}
	
	/**
	 *  Check if the current status is an internal error (50x) type
	 *  
	 *  @return string
	 */
	public function internalError() : bool {
		return 
		match( $this ) {
			ResponseStatus::InternalError, 
			ResponseStatus::NotImplemented, 
			ResponseStatus::Unavailable		=> true,
			
			default				=> false
		};
	}
	
	/**
	 *  Send the selected HTTP status code or zero on error
	 *  
	 *  @param string	$prot		HTTP Protocol
	 *  @param string	$realm		HTTP Auth realm
	 *  @param string	$opaque		Optional 
	 *  @return int
	 */
	public function sendHeader( 
		string		$prot	= 'HTTP/1.1',
		?string		$realm	= null,
		?string		$opqaue	= null
	) : int {
		
		// Current value
		$value	= $this->value ?? 0;
		
		// Error suffix
		$err	= '. Message: {msg} File: {file} Line: {line}';
		
		// Intercept any warnings/errors
		\set_error_handler( function( 
			$eno, $emsg, $efile, $eline 
		) use ( $value, $err ) {
			$str = 'Unable to set response status header {value}' . $err;
			
			logException( 
				new \ErrorException( 
					$emsg, 0, $eno, $efile, $eline 
				), \strtr( $emsg ?? $str, [ 
					'{value}'	=> $value,
					'{file}'	=> 'ResponseStatus'
				] )
			);
			
		}, E_WARNING | \E_USER_WARNING );
		
		if ( \headers_sent() ) {
			\trigger_error( 
				\strtr( 'Headers already sent and {value} not set' . $err, [ 
					'{value}'	=> $value,
					'{file}'	=> 'ResponseStatus'
				] ), 
				\E_USER_WARNING 
			);
			\restore_error_handler();
			return $value;
		}
		
		$code = 
		match( $this ) {
			// Options request
			ResponseStatus::Options			=> ( function() {
				\http_response_code( 205 );
				\header( 'Allow: ' . self::Methods, true );
			} )(),
			
			ResponseStatus::MethodNotAllowed	=> ( function() {
				\http_response_code( 405 );
				\header( 'Allow: ' . self::Methods, true );
			} )(),
			
			ResponseStatus::RangeError		=> ( function() {
				\header( "$prot 416 Range Not Satisfiable", true );
			} )(),
			
			ResponseStatus::Locked			=> ( function() {
				\header( "$prot 423 Resource Locked", true );
			} )(),
			
			ResponseStatus::TooEarly		=> ( function() {
				\header( "$prot 425 Too Early", true );
			} )(),

			ResponseStatus::TooMany			=> ( function() {
				\header( "$prot 429 Too Many Requests", true );
			} )(),
			
			ResponseStatus::TooLarge		=> ( function() {
				\header( "$prot 431 Request Header Fields Too Large", true );
			} )(),

			ResponseStatus::Unavailable		=> ( function() {
				\header( "$prot 503 Service Unavailable", true );
			} )(),
			
			// Both cases should reset
			ResponseStatus::ClientClosed,
			ResponseStatus::ResetContent		=> ( function() {
				\http_response_code( 205 );
			} )(),
			
			// Authentication
			ResponseStatus::Auth,
			ResponseStatus::AuthBasic		=> ( function() {
				\http_response_code( 401 );
				\header( self::AuthBasicHeader, true );
			} )(),
			
			ResponseStatus::AuthDigest, 
			ResponseStatus::AuthDigestStale		=> ( function() {
				\http_response_code( 401 );
				
				$dh	= 
				( $this == ResponseStatus::AuthDigestStale ) ? 
					self::AuthDigestHeader . ', stale=true' : 
					self::AuthDigestHeader;
				
				\header( \strtr( $dh, [ 
					'{realm}'	=> $realm ?? \bin2hex( \random_bytes( 12 ) ),
					'{opaque}'	=> $opqaue ?? \bin2hex( \random_bytes( 12 ) ),
					'{nonce}'	=> \bin2hex( \random_bytes( 12 ) )
				] ), true );
			} )(),
			
			// Everything else
			default					=> ( function() {
				\http_response_code( $this );
			} )()
		};
		
		\restore_error_handler();
		
		return ( int ) \http_response_code();
	}
}

