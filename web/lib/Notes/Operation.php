<?php declare( strict_types = 1 );

namespace Notes;

class Operation extends Entity {
	
	protected readonly string $_label;
	
	protected readonly string $_pattern;
	
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'label':
				if ( isset( $this->_label ) ) {
					return;
				}
				$this->_label = ( string ) $value;
				return;
				
			case 'pattern':
				if ( isset( $this->_pattern ) ) {
					return;
				}
				$this->_pattern = ( string ) $value;
				return;
				
			case 'realm':
				$this->_settings['realm']	= 
				\Notes\Util::cleanUrl( 
					( string ) $value
				);
				return;
				
			case 'event':
				$this->_settings['event']	= 
				\Notes\Util::unifySpaces(
					\Notes\Util::bland( 
						( string ) $value 
					), '_'
				);
				return;
			
			case 'op_scope':
				$this->_settings['op_scope']	= 
				\Notes\Util::unifySpaces(
					( string ) $value
				);
				return;
				
			case 'method':
				$this->_settings['method']	= 
				\Notes\Util::bland( 
					( string ) $value 
				);
				
				return;	
		}
		parent::__set( $name, $value );
	}
	
	public function __get( $name ) {
		switch( $name ) {
			case 'label':
				return $this->_label ?? '';
				
			case 'pattern':
				return $this->_pattern ?? '//i';
			
			case 'event':
				return $this->_settings['event'] ?? 'operation';
			
			case 'realm':
				return $this->_settings['realm'] ?? '/';
			
			case 'op_scope':
				return $this->_settings['op_scope'] ?? 'global';
				
			case 'method':
				return $this->_settings['method'] ?? 'get';
		}
		
		return parent::__get( $name );
	}
	
	public function save() : bool {
		$os	= isset( $this->id ) ? true : false;
		
		// Unsaved operation and no label?
		if ( !$os && empty( $this->_settings['label'] ) ) {
			$this->error( 'Attempted operation save without label' );
			return false;
		}
		
		// Missing pattern
		if ( !$os && empty( $this->_settings['pattern'] ) ) {
			$this->error( 'Attempted operation save without pattern' );
			return false;
		}
		
		$params	= [
			':label'	=> $this->label,
			':pattern'	=> $this->pattern,
			':settings'	=> 
				static::formatSettings( $this->settings )
		];
		
		$db	= $this->getControllerParam( '\\\Notes\\Data' );
		
		if ( $os ) {
			$sql = 
			"UPDATE operations SET label = :label, 
				pattern = :pattern, 
				settings = :settings";
			
			$params[':id'] = $this->id;
			$sql .= ' WHERE id = :id;';
			
			return $db->setUpdate( $sql, $params, \DATA );
		}
		
		$id	= 
		$db->setInsert(
			"INESRT INTO operations
				( label, pattern, settings ) 
			VALUES ( :label, :pattern, :settings );",
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

