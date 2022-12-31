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
	public function findUserById( int $id ) {
		return $this->findUserByParam( 'id', $id );
	}
	
	/**
	 *  Get login details by username
	 *  
	 *  @param string	$username	User's login name as entered
	 *  @return mixed
	 */
	public function findUserByUsername( string $username ) {
		return $this->findUserByParam( 'name', $username );
	}
	
	/**
	 *  Get login details by unique lookup hash
	 *  
	 *  @param string	$lookup		SQLite generated unique key
	 *  @return mixed
	 */
	public function findUserByLookup( string $lookup ) {
		return $this->findUserByParam( 'lookup', $username );
	}
	
	/**
	 *  Get login details by generated hash
	 *  
	 *  @param string	$hash		Preset user hash key
	 *  @return mixed
	 */
	public function findUserByHash( string $hash ) {
		return $this->findUserByParam( 'hash', $hash );
	}
	
	/**
	 *  Generic user selector by parameter
	 *  
	 *  @param string	$mode		Searching login view mode
	 *  @param mixed	$param		Finding parameter
	 *  @return mixed
	 */
	public function findUserByParam( string $mode, $param ) {
		$sql = 
		match( $mode ) {
			'id'	=> 'SELECT * FROM login_view WHERE id = :param LIMIT 1;',
			'name'	=> 'SELECT * FROM login_pass WHERE username = :param LIMIT 1;',
			'lookup'=> 'SELECT * FROM login_view WHERE lookup = :param LIMIT 1;',
			'hash'	=> 'SELECT * FROM login_view WHERE hash = :param LIMIT 1;'
		}
			
		$res	= 
		$this->getControllerParam( '\\\Notes\\Data' )->getResults( 
			$sql, [ ':param' => $param ], \DATA, 
			'class|\\Notes\\User'
		);
		if ( empty( $res ) ) {
			return null;
		}
		return $data[0];
	}
	
	/**
	 *  Update the last activity IP of the given user
	 *  Most of these actions use triggers in the database
	 *  
	 *  @param string		$mode	Activity type
	 *  @return bool
	 */
	public function updateUserActivity( string $mode = '' ) : bool {
		$now	= \Notes\Util::utc();
		
		$data	= $this->getControllerParam( '\\\Notes\\Data' );
		$config = $this->getControllerParam( '\\\Notes\\Config' );
		$req	= $config->getRequest();
		
		// Start session first
		$this->getControllerParam( '\\\Notes\\SHandler' )->sessionCheck();
		
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
					':id'	=> $this->user_id
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
					':id'		=> $this->user_id
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
					':id'		=> $this->user_id
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
					':id'		=> $this->user_id
				];
				break;
			
			case 'lock':
				$sql	= 
				"UPDATE auth_activity SET 
					is_locked = 1 WHERE id = :id;";
				$params	= [ ':id' => $this->user_id ];
				break;
				
			case 'unlock':
				$sql	= 
				"UPDATE user_auth SET 
					is_locked = 0 WHERE id = :id;";
				$params	= [ ':id' => $this->user_id ];
				break;
			
			case 'approve':
				$sql	= 
				"UPDATE user_auth SET 
					is_approved = 1 WHERE id = :id;";
				$params	= [ ':id' => $this->user_id ];
				break;
				
			case 'unapprove':
				$sql	= 
				"UPDATE user_auth SET 
					is_approved = 0 WHERE id = :id;";
				$params	= [ ':id' => $this->user_id ];
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
				$data->setInsert( 
					"REPLACE INTO user_auth ( 
						user_id, last_ip, last_ua, 
						last_session_id, 
						is_approved
					) VALUES( :id, :ip, :ua, :sess, :ap );", 
					[
						':id'	=> $this->user_id, 
						':ip'	=> $req->getIP(), 
						':ua'	=> $req->getUA(),
						':sess'	=> \session_id(),
						':ap'	=> $ap ? 1 : 0
					], 
					\DATA 
				) ? true : false;
		}
		
		return 
		$data->setUpdate( $sql, $params, \DATA );
	}
	
	/**
	 *  Reset cookie lookup token and return new lookup
	 *  
	 *  @return string
	 */
	public function resetLookup() : string {
		$db	= $this->getControllerParam( '\\\Notes\\Data' )->getDb( \DATA );
		$stm	= 
		$db->prepare( 
			"UPDATE logout_view SET lookup = '' 
				WHERE user_id = :id;" 
		);
		
		if ( $stm->execute( [ ':id' => $this->user_id ] ) ) {
			$stm->closeCursor();
			
			// SQLite should have generated a new random lookup
			$rst = 
			$db->prepare( 
				"SELECT lookup FROM logins WHERE 
					user_id = :id;"
			);
			
			if ( $rst->execute( [ ':id' => $this->user_id ] ) ) {
				$col = $rst->fetchColumn();
				$rst->closeCursor();
				return $col;
			}
		}
		$stm->closeCursor();
		return '';
	}
	
	public function save() : bool {
		return true;
	}
	
}


