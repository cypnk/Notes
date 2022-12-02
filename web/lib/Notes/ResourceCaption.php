<?php declare( strict_types = 1 );

namespace Notes;

class ResourceCaption extends Content {
	
	protected readonly int $_resource_id;
	
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'resource_id':
				if ( isset( $this->_resource_id ) ) {
					$this->error( 'Attempted resource change' );
					return;
				}
				$this->_resource_id = ( int ) $value;
				break;
				
			case 'label':
				$this->_content['label'] = 
					\Notes\Util::unifySpaces( ( string ) $value );
				break;
			
			case 'body':
				if ( empty( $value ) ) {
					return;
				}
				
				$bc = [];
				if ( \is_array( $value ) ) {
					foreach ( $value as $v ) {
						if ( \is_array( $v ) ) {
							break;
						}
						$bc[] = \Notes\Util::unifySpaces( ( string ) $v );
					}
					// Nothing to add
					if ( empty( $bc ) ) {
						return;	
					}
				}
				
				$this->_content['body'] = empty( $bc ) ? 
					\Notes\Util::unifySpaces( ( string ) $value ) : 
					implode( "\n", $bc );
				
		}
		parent::__set( $name, $value );
	}
	
	public function __get( $name ) {
		switch ( $name ) {
			case 'resource_id':
				return $this->_resource_id ?? 0;
			
			case 'label':
				return $this->_content['label'] ?? '';
			
			case 'body':
				return \Notes\Util::lines( $this->_content['body'] ?? '' );
		}
		return parent::__get( $name );	
	}
	
	public function save() : bool {
		$rc = isset( $this->id ) ? true : false;
		if ( !$rc && empty( $this->_resource_id ) ) {
			$this->error( 'Attempted save without setting resource' );
			return false;
		}
		
		// Default empty body and label
		$this->_content['body']		??= '';
		$this->_content['label']	??= '';
		
		$params	= [
			':content'	=> 
				static::formatSettings( $this->_content )
			':lang'		=> $this->lang_id ?? null
		];
		
		if ( $rc ) {
			$params[':id']	= $this->id;
			
			return 
			$db->setUpdate(
				"UPDATE resource_captions
				SET content = :content, lang_id = :lang 
					WHERE id = :id;",
				$params,
				\DATA
			);
		}
		
		// Set resource
		$params[':res']	= $this->_resource_id;
		
		$id	= 
		$db->setInsert(
			"INESRT INTO resource_captions
				( content, lang_id, resource_id ) 
			VALUES ( :content, :lang, :res );",
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

