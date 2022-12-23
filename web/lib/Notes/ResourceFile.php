<?php declare( strict_types = 1 );

namespace Notes;

class ResourceFile extends Content {
	
	public string $src;
	
	public string $mime_type;
	
	public string $thumbnail;
	
	public int $file_size;
	
	public string $file_hash;
	
	public int $status;
	
	protected readonly array $_captions;
	
	public function __get( $name ) {
		switch ( $name ) {
			case 'captions':
				return $this->_captions ?? [];
		}
		
		return parent::__get( $name );
	}
	
	public function save() : bool {
		$rf = isset( $this->id ) ? true : false;
		
		if ( !$rf ) {
			if ( empty( $this->_content['file_size'] ) ) {
				$this->error( 'Empty file being saved' );
			}
			
			// Can't proceed without source
			if ( empty( $this->_content['src'] ) ) {
				$this->error( 'Attempted save without setting file source' );
				return false;
			}
		}
		
		$params	= [
			':content'	=> 
				static::formatSettings( $this->_content ),
			':status'	=> $this->status ?? 0
		];
		
		$db	= $this->getControllerParam( '\\\Notes\\Data' );
		if ( $rf ) {
			$params[':id']	= $this->id;
			return 
			$db->setUpdate(
				"UPDATE resources SET content = :content, status = :status
					WHERE id = :id;",
				$params,
				\DATA
			);
		}
		
		$id	= 
		$db->setInsert(
			"INESRT INTO resources ( content, status ) 
				VALUES ( :content, :status );",
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

