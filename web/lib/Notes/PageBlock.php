<?php declare( strict_types = 1 );

namespace Notes;

class PageBlock extends Content {
	
	protected readonly int $_type_id;
	
	protected readonly int $_page_id;
	
	public readonly BlockType $type;
	
	public array $marks = [];
	
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'type_id':
				if ( isset( $this->_type_id ) ) {
					$this->error( 'Attempted type change' );
					return;
				}
				$this->_type_id = ( int ) $value;
				return;
			
			case 'page_id':
				if ( isset( $this->_page_id ) ) {
					$this->error( 'Attempted type change' );
					return;
				}
				$this->_page_id = ( int ) $value;
				return;
		}
		
		parent::__set( $name, $value );
	}
	
	public function __get( $name ) {
		switch ( $name ) {
			case 'type_id':
				return $this->_type_id ?? 0;
		}
		
		return parent::__get( $name );
	}
	
	public function setType( BlockType $bt ) {
		if ( isset( $this->type ) || isset( $this->_type_id ) ) {
			$this->error( 'Attempted type change' );
			return;
		}
		
		$this->_type_id	= $bt->id;
		$this->type	= $bt;
	}
	
	public function setPage( int $id ) {
		if ( isset( $this->page_id ) ) {
			$this->error( 'Attempted page change' );
			return;
		}
		
		$this->page_id = $id;
	}
	
	public function addFormatting( array $fmt ) {
		$this->_content['formatting'] = 
		\array_merge( $this->_content['formatting'] ?? [], $fmt );
	}
	
	public function resetFormatting( array $fmt ) {
		$this->_content['formatting'] = [];
	}
	
	public function addMemo( string $msg, int $start, int $finish ) {
		// TODO: Insert new memo at selected text range
	}
	
	/**
	 *  Edit new memo at given new range
	 *  
	 *  @param int		$id	Given memo identifier
	 *  @param string	$msg	Memo content body
	 *  @param int		$start	New text selection range start
	 *  @param int		$finish New text selection range finish
	 *  @return bool
	 */
	public function editMemo( 
		int	$id, 
		string	$msg,
		?int	$start,
		?int	$finish 
	) : bool {
		// TODO: Update memo
		return true;
	}
	
	public function removeMemo( int $id ) : bool {
		// TODO: Delete memo
		return true;
	}
	
	public function render( string $name, array $params ) : string {
		// TODO: Format substitutions for events and other placeholders
		return '';
	}
	
	public function save() : bool {
		$pb = isset( $this->id ) ? true : false;
		$ns = false;
		if ( !$ps ) {
			if ( empty( $this->_content['page_id'] ) ) {
				$this->error( 'Attempted save without setting page' );
				$ns = true;
			}
			
			if ( empty( $this->_content['type_id'] ) ) {
				$this->error( 'Attempted save without setting type' );
				$ns = true;
			}
			
			if ( $ns ) {
				return false;
			}
		}
		
		// Default empty body
		$this->_content['body'] ??= '';
		
		$params	= [
			':content'	=> 
				static::formatSettings( $this->_content ),
			':so'		=> $this->sort_order,
			':lang'		=> $this->lang_id ?? null,
			':status'	=> $this->status ?? 0
		];
		
		$db	= $this->getControllerParam( '\\\Notes\\Data' );
		
		if ( $pb ) {
			$params[':id']	= $this->id;
			
			return 
			$db->setUpdate(
				"UPDATE page_blocks 
				SET content = :content, sort_order = :so, 
					lang_id = :lang, status = :status
					WHERE id = :id;",
				$params,
				\DATA
			);
		}
		
		// Set page
		$params[':page_id']	= $this->_page_id;
		$params[':type_id']	= $this->_type_id;
		
		$id	= 
		$db->setInsert(
			"INESRT INTO page_blocks 
				( content, sort_order, lang_id, status, page_id, type_id ) 
			VALUES ( :content, :so, :lang, :status, :page_id, :type_id );",
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

