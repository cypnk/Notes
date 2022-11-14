<?php declare( strict_types = 1 );

namespace Notes;

class User extends Entity {
	
	const MAX_USER		= 180;
	
	public readonly string $username;
	
	public readonly string $password;
	
	public readonly string $user_clean;
	
	public string $check_password;
	
	protected string $_password;
	
	public function __construct() {
		
	}
	
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'new_password':
				if ( 
					!\is_string( $value )	&& 
					!\is_numeric( $value ) 
				) {
					return;
				}
				
				$this->_password	= 
					static::hashPassword( ( string ) $value );
				return;
			
			case 'username':
				$this->username		= 
				\Notes\Util::title( 
					( string ) $value, 
					static::MAX_USER 
				);
				
				$this->user_clean	= 
				\Notes\Util::slugify( $this->username );
				return;
		}
		
		parent::__set( $name, $value );
	}
	
	public function __get( $name ) {
		switch ( $name ) {
			case 'pass_validity':
				if ( !isset( $this->password ) ) {
					return false;
				}
				
				return 
				static::verifyPassword(
					$this->check_password ?? '',
					$this->password
				);
		}
		
		parent::__get( $name );
	}
	
	/**
	 *  Hash password to storage safe format
	 *  
	 *  @param string	$password	Raw password as entered
	 *  @return string
	 */
	public static function hashPassword( string $password ) : string {
		return 
		\base64_encode(
			\password_hash(
				\base64_encode(
					\hash( 'sha384', $password, true )
				),
				\PASSWORD_DEFAULT
			)
		);
	}
	
	/**
	 *  Check hashed password
	 *  
	 *  @param string	$password	Password exactly as entered
	 *  @param string	$stored		Hashed password in database
	 */
	public static function verifyPassword( 
		string		$password, 
		string		$stored 
	) : bool {
		if ( empty( $stored ) ) {
			return false;
		}
		
		$stored = \base64_decode( $stored, true );
		if ( false === $stored ) {
			return false;
		}
		
		return 
		\password_verify(
			\base64_encode( 
				\hash( 'sha384', $password, true )
			),
			$stored
		);
	}
	
	/**
	 *  Check if user password needs rehashing
	 *  
	 *  @param string	$stored		Already hashed, stored password
	 *  @return bool
	 */
	public static function passNeedsRehash( 
		string		$stored 
	) : bool {
		$stored = \base64_decode( $stored, true );
		if ( false === $stored ) {
			return false;
		}
		
		return 
		\password_needs_rehash( $stored, \PASSWORD_DEFAULT );
	}
	
	public function save() : bool {
		// TODO: Edit
		if ( isset( $this->id ) ) {
			return true;
		}
		
		// TODO: Create
		return true;
	}
}

