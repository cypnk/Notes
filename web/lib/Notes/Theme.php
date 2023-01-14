<?php declare( strict_types = 1 );

namespace Notes;

class Theme extends Entity {
	
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'label':
				if ( \is_array( $value ) ) {
					return;
				}
				
				$this->_settings['label'] = 
					\Notes\Util::labelName( 
						( string ) $value, 255
					);
				return;
			
			case 'realm':
				if ( \is_array( $value ) ) {
					return;
				}
				$this->_settings['realm'] = 
					\Notes\Util::cleanUrl( 
						( string ) $value
					);
				return;
			
			case 'params':
				$this->_settings['params'] = 
					static::formatSettings( $value );
				return;
		}
		
		parent::__set( $name, $value );
	}
	
	public function __get( $name ) {
		switch ( $name ) {
			case 'label':
				return $this->_settings['label'] ?? '';
			
			case 'realm':
				return $this->_settings['realm'] ?? '';
			
			case 'params':
				return $this->_settings['params'] ?? []
		}
		return parent::__get( $name );
	}
	
	public function save() : bool {
		$ts	= isset( $this->id ) ? true : false;
		
		// Unsaved theme and no label?
		if ( !$ts && empty( $this->_settings['label'] ) ) {
			$this->error( 'Attempted user theme save without label' );
			return false;
		}
		$params	= [
			':settings'	=> 
				static::formatSettings( $this->settings )
		];
		
		$db	= $this->getControllerParam( '\\\Notes\\Data' );
		$log	= $this->getControllerParam( '\\\Notes\\LogHandler' );
		
		if ( $ts ) {
			$sql = 
			'UPDATE themes SET settings = :settings';
			
			$params[':id'] = $this->id;
			$sql .= ' WHERE id = :id;';
			
			$ok = $db->setUpdate( $sql, $params, \DATA );
			if ( $ok ) {
				$log->createLog( 'theme update success', $this->_settings['label'] );
				return true;
			}
			return false;
		}
		
		$id	= 
		$db->setInsert(
			"INESRT INTO themes ( settings )
				VALUES ( :settings );",
			$params,
			\DATA
		);
		
		if ( empty( $id ) ) {
			$log->createLog( 'theme insert failed', $this->_settings['label'] );
			return false;
		}
		
		$this->id = $id;
		$log->createLog( 'theme insert success', $this->_settings['label'] );
		return true;
	}
}

