<?php declare( strict_types = 1 );

namespace Notes;

class UserMark extends Content {
	
	public int $user_id;
	
	protected readonly string $_label;
	
	protected readonly int $_document_id;
	
	protected readonly int $_page_id;
	
	protected readonly int $_block_id;
	
	protected readonly int $_memo_id;
	
	public string $expires;
	
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'label':
				$this->_label = ( string ) $value;
				return;
				
			case 'document_id':
				$this->_document_id = ( int ) $value;
				return;
				
			case 'page_id':
				$this->_page_id = ( int ) $value;
				return;
				
			case 'block_id':
				$this->_block_id = ( int ) $value;
				return;
				
			case 'memo_id':
				$this->_memo_id = ( int ) $value;
				return;
		}
		
		parent::__set( $name, $value );
	}
	
	public function __get( $name ) {
		switch ( $name ) {
			case 'user_id':
				return $this->_user_id ?? 0;
				
			case 'label':
				return $this->_label ?? '';
				
			case 'document_id':
				return $this->_document_id ?? 0;
				
			case 'page_id':
				return $this->_page_id ?? 0;
				
			case 'block_id':
				return $this->_block_id ?? 0;
				
			case 'memo_id':
				return $this->_memo_id ?? 0;
		}
		
		return parent::__get( $name );
	}
	
	public function move( string $mode, int $idx ) {
		switch ( $mode ) {
			case 'document':
			case 'page':
			case 'block':
			case 'memo':
				$this->_content[$mode . '_id'] = $idx;
		}
	}
	
	public function setLabel( string $label ) {
		$this->_content['label'] = 
		\Notes\Util::unifySpaces( trim( $label ) );
	}
	
	public function save() : bool {
		$um = isset( $this->id ) ? true : false;
		
		if ( !$um && empty( $this->user_id ) ) {
			$this->error( 'Attempted save without user' );
			return false;
		}
		
		$this->setLabel( 
			( string ) ( $this->_content['label'] ?? '' ) 
		);
		
		if ( empty( $this->_content['label'] ) ){
			$this->error( 'Attempted save without label' );
			return false;
		}
		
		if (
			empty( $this->_content['document_id'] )	&& 
			empty( $this->_content['page_id'] )	&& 
			empty( $this->_content['block_id'] )	&& 
			empty( $this->_content['memo_id'] )
		) {
			$this->error( 'Attempted save without location' );
			return false;
		}
		
		if ( !empty( $this->expires ) ) {
			$this->expires = 
				\Notes\Util::utc( $this->expires );
		}
		
		$params	= [
			':content'	=> 
				static::formatSettings( $this->_content ),
			':expires'	=> $this->expires ?? null,
			':status'	=> $this->status ?? 0
		];
		
		if ( $um ) {
			$params[':id']	= $this->id;
			
			return 
			$db->setUpdate(
				"UPDATE user_marks 
				SET content = :content, expires = :expires, 
					status = :status 
					WHERE id = :id;",
				$params,
				\DATA
			);
		}
		
		$params[':user_id']	= $this->user_id;
		
		$id	= 
		$db->setInsert(
			"INESRT INTO user_marks 
				( content, expires, status, user_id ) 
			VALUES ( :content, :expires, :status, :user_id );",
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

