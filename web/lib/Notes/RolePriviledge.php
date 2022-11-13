<?php declare( strict_types = 1 );

namespace Notes;

class RolePriviledge extends Provider {
	
	protected readonly int $_role_id;
	
	protected readonly int $_permission_id;
	
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
				\array_merge( 
					static::settings_base['actions'],
					static::formatSettings( $value )
				);
				
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
}
