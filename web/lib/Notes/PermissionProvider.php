<?php declare( strict_types = 1 );

namespace Notes;

class PermissionProvider extends Provider {
	
	protected static array $settings_base	= [
		'setting_id'	=> '',
		'realm'		=> 'http://localhost',
		'scope'		=> 'local',
		'report'	=> [],
		'authority'	=> []
	];
	
	
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'report':
			case 'authority':
				static::settings_base[$name] = 
				\array_merge( 
					static::settings_base[$name],
					static::formatSettings( $value )
				);
				return;
		}
		
		parent::__set( $name, $value );
	}
	
	public function __get( $name ) {
		switch ( $name ) {
			case 'report':
			case 'authority':
				return static::settings_base[$name] ?? [];
		}
		
		return parent::__get( $name );
	}
	
}
