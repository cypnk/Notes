<?php declare( strict_types = 1 );

namespace Notes;

class Cache extends Entity {
	
	protected readonly string $_cache_id;
	
	protected int $_ttl;
	
	public string $content;
	
	protected string $_expires;
	
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'cache_id':
				if ( isset( $this->_cache_id ) ) {
					$this->error( 'Attempted cache_id change' );
					return;
				}
				$this->_cache_id = ( string ) $value;
				break;
				
			case 'ttl':
				$this->_ttl = ( int ) $value;
				break;
			
			case 'expires':
				$this->_expires = 
					\strtotime( ( string ) $value );
				break;
		}
		
		parent::__set( $name, $value );
	}
	
	public function __get( $name ) {
		switch ( $name ) {
			case 'cache_id':
				return $this->_cache_id ?? '';
				
			case 'ttl':
				return $this->_ttl ?? 0;
			
			case 'expires':
				return $this->_expires ?? time();
		}
		
		return parent::__get( $name );
	}
	
	public function save() : bool {
		$cs = isset( $this->id ) ? true : false;
		
		if ( !$cs && empty( $this->_cache_id ) ) {
			$this->error( 'Attempted cache save without setting cache_id' );
			return false;
		}
		
		if ( !$cs && empty( $this->_ttl ) ) {
			$this->error( 'Attempted cache save without setting TTL' );
			return false;
		}
		
		$db	= $this->getData();
		$db->setUpdate(
			"REPLACE INTO caches ( cache_id, ttl, content )
				VALUES ( :id, :ttl, :content );",
			[
				':id'		=> $this->cache_id,
				':ttl'		=> $this->ttl,
				':content'	=> $this->content
			],
			\CACHE
		);
	}
}

