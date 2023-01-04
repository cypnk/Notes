<?php declare( strict_types = 1 );

namespace Notes;

enum AuthType {
	case Form;
	case Basic;
	case Digest;
	case External;
	
	case Unknown;
	
	/**
	 *  Check authentication scheme from sent header against supported types
	 */
	public static function mode( string $auth, string $method ) : static {
		$lauth = \strtolower( $auth );
		
		return
		match( true ) {
			\str_starts_with( $lauth, 'basic' )	=> AuthType::Basic,
			\str_starts_with( $lauth, 'digest' )	=> AuthType::Digest,
			\str_starts_with( $lauth, 'bearer' )	=> AuthType::External,
			
			// Try post
			( 0 == \strcasecmp( 'post', $method ) )	=> AuthType::Form,
			
			// Fallback
			default					=> AuthType::Unknown
		};
	}
}

