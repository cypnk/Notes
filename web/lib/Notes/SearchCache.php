<?php declare( strict_types = 1 );

namespace Notes;

class SearchCache extends Entity {
	
	protected readonly string $_label;
	
	protected readonly string $_terms;
	
	protected string $_expires;
	
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'label':
				if ( isset( $this->_label ) ) {
					return;
				}
				$this->_label = ( string ) $value;
				return;
				
			case 'terms':
				if ( isset( $this->_terms ) ) {
					return;
				}
				if ( \is_array( $value ) ) {
					$this->_terms = \implode( ', ', $value );
					return;
				}
				$this->_terms = 
				\Notes\Util::trimmedList( ( string ) $value );
				return;
			
			case 'expires':
				$value		= ( string ) $value;
				$this->_expires = 
				\Note\Util::utc( \is_numeric( $value ) ? $value : \strtotime( $value ) );
				return;
				
		}
		
		parent::__set( $name, $value );	
	}
	
	public function __get( $name ) {
		switch ( $name ) {
			case 'label':
				return $this->_label ?? '';
				
			case 'terms':
				return $this->_terms ?? '';
				
			case 'expires':
				return 
				$this->_expires ?? \Note\Util::utc();
		}
		
		return parent::__get( $name );	
	}
	
	public function save() : bool {
		$cs = isset( $this->id ) ? true : false;
		
		if ( empty( $this->_content['label'] ) ) {
			$this->error( 'Attempted search cache save without setting label' );
			return false;
		}
		
		if ( empty( $this->_content['terms'] ) ) {
			$this->error( 'Attempted search cache save without setting terms' );
			return false;
		}
		
		$db	= $this->getControllerParam( '\\\Notes\\Data' );
		$params	= [
			':settings'	=> \Notes\Util::encode( $this->settings ),
			':expires'	=> $this->expires
		];
		
		if ( $cs ) {
			$params[':id']	= $this->id;
			return 
			$db->setUpdate(
				"UPDATE search_cache SET settings = :settings,
					expires = :expires WHERE id = :id;",
				$params,
				\DATA
			);
		}
		$id = 
		$db->setInsert(
			"INESRT INTO search_cache ( settings, expires ) 
				VALUES ( :settings, :expires );",
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
