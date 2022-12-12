<?php declare( strict_types = 1 );

namespace Notes;

class User extends Entity {
	
	/**
	 *  Automatically approve newly registered users
	 */
	const AUTO_APPROVE_REG	= 1;
	
	public int $is_approved;
	
	public int $is_locked;
	
	public int $user_id;
	
	public string $mobile_pin;
	
	public string $info;
	
	public string $auth;
	
	public string $hash;
	
	protected int $_provider_id;
	
	public string $last_ip;
	
	public string $last_ua;
	
	public string $last_active;
	
	public string $last_login;
	
	public string $last_lockout;
	
	public string $last_pass_change;
	
	public string $last_session_id;
	
	protected int $_failed_attempts;
	
	public string $failed_last_start;
	
	public string $failed_last_attempt;
	
	public string $expires;
	
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'failed_attempts':
				$this->_failed_attempts	= ( int ) $value;
				return;
			
			case 'provider_id':
				$this->_provider_id = ( int ) $value;
				value;
		}
		
		parent::__set( $name, $value );
	}
	
	public function __get( $name ) {
		switch ( $name ) {
			case 'failed_attempts':
				return $this->_failed_attempts ?? 0;
			
			case 'provider_id':
				return $this->_provider_id ?? 0;
		}
		
		parent::__get( $name );
	}
	
	/**
	 *  Get profile details by id
	 *  
	 *  @param int		$id		User's id
	 *  @return mixed
	 */
	public static function findUserById( int $id ) {
		$sql		= 
		"SELECT * FROM login_view WHERE id = :id LIMIT 1;";
		$res	= 
		static::$data->getResults( 
			$sql, [ ':id' => $id ], \DATA, 
			'class|\\Notes\\User'
		);
		if ( empty( $res ) ) {
			return null;
		}
		return $res[0];
	}
	
	/**
	 *  Get login details by username
	 *  
	 *  @param string	$username	User's login name as entered
	 *  @return mixed
	 */
	public static function findUserByUsername( string $username ) {
		$sql	= 
		"SELECT * FROM login_pass WHERE username = :user LIMIT 1;";
		
		$data	= 
		static::$data->getResults( 
			$sql, [ ':user' => $username ], \DATA,
			'class|\\Notes\\User'
		);
		
		if ( empty( $data ) ) {
			return [];
		}
		return $data[0];
	}
	
	/**
	 *  Update the last activity IP of the given user
	 *  Most of these actions use triggers in the database
	 *  
	 *  @param int			$id	User unique identifier
	 *  @param string		$mode	Activity type
	 *  @return bool
	 */
	public static function updateUserActivity(
		int	$id, 
		string	$mode	= ''
	) : bool {
		$now	= \Notes\Util::utc();
		
		$ctrl	= static::$data->getController();
		$config = $ctrl->getConfig();
		$req	= $config->getRequest();
		
		// Start session first
		$ctrl->getSession()->sessionCheck();
		
		switch ( $mode ) {
			case 'active':
				$sql	= 
				"UPDATE auth_activity SET 
					last_ip		= :ip, 
					last_ua		= :ua, 
					last_session_id = :sess 
					WHERE user_id = :id;";
				
				$params = [
					':ip'	=> $req->getIP(), 
					':ua'	=> $req->getUA(), 
					':sess'	=> \session_id(), 
					':id'	=> $id
				];
				break;
				
			case 'login':
				$sql	= 
				"UPDATE auth_activity SET 
					last_ip		= :ip, 
					last_ua		= :ua, 
					last_login	= :login, 
					last_session_id = :sess 
					WHERE user_id = :id;";
				
				$params = [
					':ip'		=> $req->getIP(), 
					':ua'		=> $req->getUA(),
					':login'	=> $now,
					':sess'		=> \session_id(),
					':id'		=> $id
				];
				break;
			
			case 'passchange':
				// Change table itself instead of the view
				$sql	= 
				"UPDATE user_auth SET 
					last_ip			= :ip, 
					last_ua			= :ua, 
					last_active		= :active,
					last_pass_change	= :change, 
					last_session_id		= :sess 
					WHERE user_id = :id;";
				
				$params = [
					':ip'		=> $req->getIP(), 
					':ua'		=> $req->getUA(),
					':active'	=> $now,
					':change'	=> $now,
					':sess'		=> \session_id(),
					':id'		=> $id
				];
				break;
			
			case 'failedlogin':
				$sql	= 
				"UPDATE auth_activity SET 
					last_ip			= :ip, 
					last_ua			= :ua, 
					last_session_id		= :sess, 
					failed_last_attempt	= :fdate
					WHERE user_id = :id;";
					
				$params = [
					':ip'		=> $req->getIP(), 
					':ua'		=> $req->getUA(),
					':sess'		=> \session_id(),
					':fdate'	=> $now,
					':id'		=> $id
				];
				break;
			
			case 'lock':
				$sql	= 
				"UPDATE auth_activity SET 
					is_locked = 1 WHERE id = :id;";
				$params	= [ ':id' => $id ];
				break;
				
			case 'unlock':
				$sql	= 
				"UPDATE user_auth SET 
					is_locked = 0 WHERE id = :id;";
				$params	= [ ':id' => $id ];
				break;
			
			case 'approve':
				$sql	= 
				"UPDATE user_auth SET 
					is_approved = 1 WHERE id = :id;";
				$params	= [ ':id' => $id ];
				break;
				
			case 'unapprove':
				$sql	= 
				"UPDATE user_auth SET 
					is_approved = 0 WHERE id = :id;";
				$params	= [ ':id' => $id ];
				break;
				
			default:
				// First run? Create or replace auth basics
				
				// Auto approve new auth?
				$ap = 
				$config->setting( 
					'auto_approve_reg', 
					static::AUTO_APPROVE_REG, 
					'bool' 
				);
				
				return 
				static::$data->setInsert( 
					"REPLACE INTO user_auth ( 
						user_id, last_ip, last_ua, 
						last_session_id, 
						is_approved
					) VALUES( :id, :ip, :ua, :sess, :ap );", 
					[
						':id'	=> $id, 
						':ip'	=> $req->getIP(), 
						':ua'	=> $req->getUA(),
						':sess'	=> \session_id(),
						':ap'	=> $ap ? 1 : 0
					], 
					\DATA 
				) ? true : false;
		}
		
		return 
		static::$data->setUpdate( $sql, $params, \DATA );
	}
	
	/**
	 *  Reset cookie lookup token and return new lookup
	 * 
	 *  @param int		$id		Logged in user's ID
	 *  @return string
	 */
	public static function resetLookup( int $id ) : string {
		$db	= static::$data->getDb( \DATA );
		$stm	= 
		$db->prepare( 
			"UPDATE logout_view SET lookup = '' 
				WHERE user_id = :id;" 
		);
		
		if ( $stm->execute( [ ':id' => $id ] ) ) {
			$stm->closeCursor();
			
			// SQLite should have generated a new random lookup
			$rst = 
			$db->prepare( 
				"SELECT lookup FROM logins WHERE 
					user_id = :id;"
			);
			
			if ( $rst->execute( [ ':id' => $id ] ) ) {
				$col = $rst->fetchColumn();
				$rst->closeCursor();
				return $col;
			}
		}
		$stm->closeCursor();
		return '';
	}
	
	/**
	 *  Set a new password for the user
	 *  
	 *  @param int		$id		User ID to change password
	 *  @param string	$param		Raw password as entered
	 *  @return bool
	 */
	public static function savePassword( 
		int	$id,
		string	$password 
	) : bool {
		return
		static::$data->setUpdate( 
			"UPDATE users SET password = :password 
			WHERE id = :id", 
			[ 
				':password'	=> 
					static::hashPassword( $password ), 
				':id'		=> $id 
			], \DATA 
		);
	}
	
	public function save() : bool {
		return true;
	}
	
}


