<?php declare( strict_types = 1 );

namespace Notes;

abstract class Entity implements Stringable extends Controllable {
	
	private readonly int $_id;
	
	private readonly string $_uuid;
	
	private readonly string $_created;
	
	private readonly string $_updated;
	
	public int $status;
	
	public int $sort_order;
	
	protected string $_setting_id;
	
	protected array $_settings		= [];
	
	protected array $_readonly		= [];
	
	abstract public function save() : bool;
	
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'id':
				if ( isset( $this->_id ) ) {
					return;
				}
				$this->_id = ( int ) $value;
				break;
				
			case 'uuid':
				if ( isset( $this->_uuid ) ) {
					return;
				}
				$this->_uuid = ( string ) $value;
				break;
				
			case 'created':
				if ( isset( $this->_created ) ) {
					return;
				}
				$this->_created = ( string ) $value;
				break;
				
			case 'updated':
				if ( isset( $this->_updated ) ) {
					return;
				}
				$this->_updated = ( string ) $value;
				break;
				
			case 'settings':
				// Configure settings format
				$this->_settings		= 
					static::formatSettings( $value );
				
				// Reset setting ID as hash
				$this->_setting_id		= 
				\hash( 
					'sha384', 
					\Notes\Util::encode( $this->_settings ) 
				);
				
				// Include ID in original setting data
				$this->_settings['setting_id']	= 
					$this->_setting_id;
				break;
		}
	}
	
	public function __get( $name ) {
		switch ( $name ) {
			case 'id':
				return $this->_id ?? 0;
				
			case 'uuid':
				return $this->_uuid ?? '';
				
			case 'created':
				return $this->_created ?? '';
				
			case 'updated':
				return $this->_updated ?? '';
				
			case 'settings':
				return $this->_settings ?? [];
			
			case 'setting_id':
				return $this->_setting_id ?? 
					$this->_settings['setting_id'] ?? '';
		}
		
		// Nothing else at this level
		return null;
	}
	
	public function __toString() {
		return 
		\Notes\Util::encode( 
			[ ...new ArrayIterator( [ 
				"id"		=> $this->id, 
				"created"	=> $this->created,
				"updated"	=> $this->updated,
				"setting_id"	=> $this->setting_id
			] ), ...$this->_settings ];
		);
	}
	
	public function __isset( $name ) {
		switch ( $name ) {
			case 'id':
				return isset( $this->_id );
			
			case 'uuid':
				return isset( $this->_uuid );
				
			case 'uuid':
				return isset( $this->_uuid );
				
			case 'created':
				return isset( $this->_created );
				
			case 'updated':
				return isset( $this->_updated );
				
			case 'setting_id':
				return 
					isset( $this->_setting_id ) || 
					isset( $this->_settings['setting_id'] );
		}
		return false;
	}
	
	/**
	 *  Directly apply settings value externally, if it's not write-protected
	 *  
	 *  @param string	$name		Setting key
	 *  @param mixed	$value		Non-object setting value
	 *  @return bool
	 */
	public function setSetting( string $name, $value ) : bool {
		if ( \in_array( $name, $this->_readonly ) ) {
			return false;
		}
		
		$this->_settings[$name] = $value;
		return true;
	}
	
	/**
	 *  Find setting value by key name, if it has been set
	 *  
	 *  @param string	$name		Setting key to search
	 *  @param mixed	$default	Fallback value
	 *  @return mixed
	 */
	public function setting( string $name, $default ) {
		return $this->_settings[$name] ?? $default;
	}
}

