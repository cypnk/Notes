<?php declare( strict_types = 1 );

namespace Notes;

class PageBlock extends Content {
	
	public readonly int $page_id;
	
	public function __construct( ?int = $id ) {
		if ( !empty( $id ) ) {
			$this->setPage( $id );
		}
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
	
	public function save() : bool {
		$pb = isset( $this->id ) ? true : false;
		
		if ( !$ps && empty( $this->page_id ) ) {
			$this->error( 'Attempted save without setting page' );
			return false;
		}
		
		// Default empty body
		$this->_content['body'] ??= '';
		
		$params	= [
			':content'	=> 
				static::formatSettings( $this->_content )
			':so'		=> $this->sort_order,
			':lang'		=> $this->lang_id ?? null,
			':status'	=> $this->status ?? 0
		];
		
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
		$params[':page_id']	= $this->page_id;
		
		$id	= 
		$db->setInsert(
			"INESRT INTO page_blocks 
				( content, sort_order, lang_id, status, page_id ) 
			VALUES ( :content, :so, :lang, :status, :page_id );",
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

