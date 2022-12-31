<?php declare( strict_types = 1 );

namespace Notes;

class User extends Entity {
	
	/**
	 *  Maximum username length
	 */
	const MAX_USER		= 180;
	
	protected readonly string $_username;
	
	protected readonly string $_password;
	
	protected readonly string $_user_clean;
	
	protected string $_bio;
	
	protected string $_display;
	
	public string $check_password;
	
	protected string $_new_password;
	
	protected string $_hash;
	
	protected int $_is_approved;
	
	protected int $_is_locked;
	
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'display':
				$this->_display = 
				\Notes\Util::title( ( string ) $value );
				return;
				
			case 'bio':
				$this->_bio = 
				\Notes\Util::entities(
					\Notes\Util::pacify( ( string ) $value )
				);
				return;
				
			case 'new_password':
				if ( 
					!\is_string( $value )	&& 
					!\is_numeric( $value ) 
				) {
					return;
				}
				
				$this->_new_password	= 
					static::hashPassword( ( string ) $value );
				return;
				
			case 'password':
				if ( isset( $this->_password ) ) {
					$this->error( 'Attempted direct password change' );
					return;
				}
				$this->_password	= ( string ) $value;
				return;
				
			case 'username':
				if ( isset( $this->_username ) ) {
					$this->error( 'Attempted username change' );
					return;
				}
				$this->_username	= 
				static::maxUsername( ( string ) $value );
				
				$this->_user_clean	= 
				static::cleanUsername( $this->_username );
				return;
			
			case 'hash':
				$this->_hash		= ( string ) $value;
				return;
				
			case 'is_approved':
				$this->_is_approved	= ( int ) $value;
				return;
				
			case 'is_locked':
				$this->_is_locked	= ( int ) $value;
				return;
		}
		
		parent::__set( $name, $value );
	}
	
	public function __get( $name ) {
		switch ( $name ) {
			case 'username':
				return $this->_username ?? '';
			
			case 'display':
				return $this->_display ?? '';
			
			case 'bio':
				return $this->_bio ?? '';
			
			case 'user_clean':
				return $this->_user_clean ?? '';
			
			case 'password':
				return $this->_password ?? '';
				
			case 'pass_validity':
				if ( !isset( $this->_password ) ) {
					return false;
				}
				
				return 
				static::verifyPassword(
					$this->check_password ?? '',
					$this->_password
				);
			
			case 'hash':
				return $this->_hash ?? '';
				
			case 'is_approved':
				return $this->_is_approved ?? 0;
				
			case 'is_locked':
				return $this->_is_locked ?? 0;
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
	
	/**
	 *  Process username
	 */
	public static function maxUsername( string $name ) : string {
		static $max_user;
		if ( !isset( $max_user ) ) {
			$max_user =
			static::$data
				->getController()
				->getConfig()
				->setting( 
					'max_user', 
					static::MAX_USER, 
					'int' 
				);
		}
		
		return 
		\Notes\Util::title( $name, $max_user );
	}
	
	/**
	 *  Helper to turn full username to index-friendly term
	 *  
	 *  @param string	$name		Entered username 
	 *  @return string
	 */
	public static function cleanUsername( string $name ) {
		return 
		\Notes\Util::unifySpaces( \Notes\Util::lowercase( 
			\Notes\Util::bland(
				\Notes\Util::normal( $name ), true 
			) 
		) );
	}
	
	public function save() : bool {
		$us	= isset( $this->id ) ? true : false;
		
		// Unsaved user and no username?
		if ( !$us && empty( $this->_username ) ) {
			$this->error( 'Attempted user save without username' );
			return false;
		}
		
		// Unsaved user and no password?
		if ( !$us && empty( $this->_password ) ) {
			$this->error( 'Attempted user save without password' );
			return false;
		}
		
		$params	= [
			':status'	=> $this->status,
			':bio'		=> $this->bio ?? '',
			':settings'	=> 
				static::formatSettings( $this->settings )
		];
		
		$db	= $this->getControllerParam( '\\\Notes\\Data' );
		if ( $us ) {
			$sql = 
			"UPDATE users SET status = :status, 
				bio = :bio, settings = :settings";
				
			// Changing password too?
			if ( isset( $this->_new_password ) ) {
				$params[':pass'] = $this->_new_password;
				$sql .= ', password = :pass';
			}
			
			$params[':id'] = $this->id;
			$sql .= ' WHERE id = :id;';
			
			return $db->setUpdate( $sql, $params, \DATA );
		}
		
		// Set new password
		$this->new_password	= $this->_password;
		
		$params[':name']	= $this->_username;
		$params[':clean']	= $this->_user_clean;
		$params[':pass']	= $this->_new_password;
		
		$id	= 
		$db->setInsert(
			"INESRT INTO users
				( status, bio, settings, username, user_clean, password ) 
			VALUES ( :status, :bio, :settings, :name, :clean, :pass );",
			$params,
			\DATA
		);
		
		if ( empty( $id ) ) {
			return false;
		}
		$this->id = $id;
		return true;
	}
}

