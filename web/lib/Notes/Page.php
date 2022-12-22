<?php declare( strict_types = 1 );

namespace Notes;

class Page extends Content {
	
	public readonly int $document_id;
	
	protected array $blocks		= [];
	
	public function setDocument( int $id ) {
		if ( isset( $this->document_id ) ) {
			$this->error( 'Attempted document change' );
			return;
		}
		
		$this->document_id = $id;
	}
	
	public function addBlock( int $id, ?int $sort_order = null ) {
		$block = new Block( $id );
		if ( null != $sort_order ) {
			$block->sort_order = $sort_order;
		}
		
		$this->blocks[] = $block;
		return $block;
	}
	
	public function loadBlocks( int $start, int $finish ) {
		$start	= \Util::intRange( $start, 1, \PHP_INT_MAX - 3 );
		$finish	= \Util::intRange( $finish, $start, \PHP_INT_MAX - 2 );
		
		$db	= $this->getData();
		
		$this->blocks = 
		$db->getResults( 
			"SELECT id, type_id, body, content, lang_id, sort_order 
			FROM page_blocks WHERE page_id = :id ORDER BY 
				id ASC, sort_order ASC
			LIMIT :limit OFFSET :offset;", 
			[
				':id'		=> $this->id,
				':limit'	=> $finish - $start,
				':offset'	=> $start
			], 
			\DATA,
			'controllable|\\Notes\\PageBlock'
		);
	}
	
	public function save() : bool {
		$ps = isset( $this->id ) ? true : false;
		
		if ( !$ps && empty( $this->document_id ) ) {
			$this->error( 'Attempted save without setting document' );
			return false;
		}
		
		$db	= $this->getData();
		$params	= [
			'sort_order'	=> $this->sort_order,
			'status'	=> $this->status,
		];
		
		if ( $ps ) {
			// Save loaded blocks first
			foreach ( $this->blocks as $b ) {
				$b->save();
			}
			
			return 
			$db->setUpdate(
				"UPDATE pages SET sort_order = :so, status = :status
					WHERE id = :id;",
				[
					':so'		=> $this->sort_order ?? 0,
					':status'	=> $this->status ?? 0,
					':id'		=> $this->id
				],
				\DATA
			);
		}
		
		$id	= 
		$db->setInsert(
			"INESRT INTO pages ( document_id, sort_order, status ) 
				VALUES ( :doc, :so, status );",
			[
				':doc'		=> $this->document_id,
				':so'		=> $this->sort_order ?? 0,
				':status'	=> $this->status ?? 0
			],
			\DATA
		);
		if ( empty( $id ) ) {
			return false;
		}
		$this->id = $id;
		return true;
	}
}

