<?php declare( strict_types = 1 );

namespace Notes;

class RolePriviledge extends Provider {
	
	protected readonly int $_role_id;
	
	protected readonly int $_permission_id;
	
	
	protected static $insert_sql		= 
	"INSERT INTO role_privileges 
		( role_id, permission_id, sort_order, settings, label )
		VALUES( :role, :perm, :so, :settings, :label );";
	
	protected static $update_sql		= 
	"UPDATE role_privileges SET sort_order = :so, settings = :settings 
		WHERE id = :id";
	
	protected static array $settings_base	= [
		'setting_id'	=> '',
		'realm'		=> 'http://localhost',
		'scope'		=> 'local',
		'actions'	=> []
	];
	
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'role_id':
				$this->_role_id		= ( int ) $value;
				return;
				
			case 'permission_id':
				$this->_permission_id	= ( int ) $value;
				return;
			
			case 'actions':
				static::settings_base['actions'] = 
				[
					...static::$settings_base['actions'],
					...static::formatSettings( $value )
				];
				
				return;
				
		}
		
		parent::__set( $name, $value );
	}
	
	public function __get( $name ) {
		switch ( $name ) {
			case 'role_id':
				return $this->_role_id ?? 0;
				
			case 'permission_id':
				return $this->_permission_id ?? 0;
			
			case 'actions':
				return $this->settings['actions'] ?? [];
		}
		
		return parent::__get( $name );
	}
	
	public function save() : bool {
		$rp	= isset( $this->id ) ? true : false;
		if ( 
			!$rp && 
			( 
				empty( $this->role_id ) || 
				empty( $this->permission_id ) 
			)
		) {
			if ( empty( $this->role_id ) {
				$this->error( 'Attempted to save RolePriviledge without setting role id' );
			}
			
			if ( empty( $this->permission_id ) ) {
				$this->error( 'Attempted to save RolePriviledge without setting permission id' );
			}
			return false;
		} elseif ( !$rp ) {
			$this->params	= [
				':role'	=> $this->role_id,
				':perm'	=> $this->permission_id
			];
		}
		
		return parent::save();
	}
}
