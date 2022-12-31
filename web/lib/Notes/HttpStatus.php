<?php declare( strict_types = 1 );

namespace Notes;

enum HttpStatus {
	
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
	case Options;
	case MethodNotAllowed	= 405;
	case RangeError		= 416;
	case Locked		= 423;
	case TooEarly		= 425;
	case TooMany		= 429;
	case TooLarge		= 431;
	case ClientClosed	= 499;
	
	case Unavailable	= 503;
	
	// Authentication
	case Auth;
	case AuthBasic;
	case AuthDigest;
	
	/**
	 *  Currently allowed methods
	 */
	public const Methods	= 'GET, POST, HEAD, OPTIONS';
	
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
		if ( \headers_sent() ) {
			return 0;
		}
		
		return
		match( $this ) {
			// Options request
			static::Options			= ( function() {
				\http_response_code( 205 );
				\header( 'Allow: ' . static::Methods, true );
				return 205;
			} )(),
			
			static::MethodNotAllowed	=> ( function() {
				\http_response_code( 405 );
				\header( 'Allow: ' . static::Methods, true );
				return 405;
			} )(),
			
			static::RangeError		=> ( function() {
				\header( "$prot 416 Range Not Satisfiable", true );
				return 416;
			} )(),
			
			static::Locked			=> ( function() {
				\header( "$prot 423 Resource Locked", true );
				return 423;
			} )(),
			
			static::TooEarly		=> ( function() {
				\header( "$prot 425 Too Early", true );
				return 425;
			} )(),

			static::TooMany			=> ( function() {
				\header( "$prot 429 Too Many Requests", true );
				return 429;
			} )(),
			
			static::TooLarge		=> ( function() {
				\header( "$prot 431 Request Header Fields Too Large", true );
				return 431;
			} )(),

			static::Unavailable		=> ( function() {
				\header( "$prot 503 Service Unavailable", true );
				return 503;
			} )(),
			
			// Both cases should reset
			static::ClientClosed,
			static::ResetContent		=> ( function() {
				\http_response_code( 205 );
				return 205;
			} )(),
			
			// Authentication
			static::Auth,
			static::AuthBasic		=> ( function() {
				\http_response_code( 401 );
				\header( 'WWW-Authenticate: Basic charset="UTF-8"', true );
				return 401;
			} )(),
			
			static::AuthDigest		=> ( function() {
				$dh	= 
				'WWW-Authenticate: Digest charset="UTF-8" realm="{realm}" ' . 
				'qop="auth, auth-int", algorithm=SHA-256, ' . 
				'nonce="{nonce}", opaque="{opaque}"';
				
				\http_response_code( 401 );
				\header( \strtr( $dh, [ 
					'{realm}'	=> $realm ?? \bin2hex( \random_bytes( 12 ) ),
					'{opaque}'	=> $opqaue ?? \bin2hex( \random_bytes( 12 ) ),
					'{nonce}'	=> \bin2hex( \random_bytes( 12 ) )
				] ), true );
				return 401;
			} )(),
			
			// Everything else
			default				=> ( function() {
				\http_response_code( $this );
				return ( int ) \http_response_code();
			} )()
		};
	}
}

