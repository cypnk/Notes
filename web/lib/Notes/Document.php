<?php declare( strict_types = 1 );

namespace Notes;

class Document extends Content {
	
	protected readonly int $_type_id;
	
	public string $summary;
	
	public array $pages	= [];
	
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'type_id':
				$value = ( int ) $value;
				
				// Prevent override
				if ( isset( $this->_type_id ) ) {
					$this->error( 
						'Attempted to override document type from ' . 
						$this->_type_id . ' to ' . $value
					);
					return;
				}
				
				$this->_type_id = $value;
				break;
				
		}
		
		parent::__set( $name, $value );	
	}
	
	public function __get( $name ) {
		switch ( $switch ) {
			case 'type_id':
				return $this->_type_id ?? 0;
		}
		
		return parent::__get( $name );
	}
	
	public function addPage() {
		if ( !isset( $this->id ) ) {
			$this->error( 
				'Attempted to add page without saving page first'
			);
			
			return null;
		}
		
		$page	= new Page( $this->id );
		$this->pages[] = $page;
		return $page;
	}
	
	
	public function loadPages( int $start, int $finish ) {
		$start	= \Util::intRange( $start, 1, 9999 );
		$finish	= \Util::intRange( $finish, $start, 10000 );
		
		// TODO: Get loaded pages
		
		return $this->pages;
	}
	
	public function save() : bool {
		$ds	= isset( $this->id ) ? true : false;
		
		if ( !$ds && empty( $this->type_id ) ) {
			$this->error( 'Attempted save without setting document type' );
			return false;
		}
		
		$db	= $this->getControllerParam( '\\\Notes\\Data' );
		$params	= [
			':lang'		=> $this->lang_id ?? null,
			':so'		=> $this->sort_order ?? 0,
			':summary'	=> $this->summary ?? '',
			':settings'	=> 
				\Notes\Util::encode( $this->settings ),
			':status'	=> $this->status ?? 0
		];
		
		if ( $ds ) {
			// Save loaded pages first
			foreach ( $p as $this->pages ) {
				$p->save();
			}
			
			$params[':id']		= $this->id;
			return 
			$db->setUpdate(
				"UPDATE documents SET 
					lang_id = :lang, 
					sort_order = :so, 
					summary = :summary, 
					settings = :settings, 
					status = :status 
						WHERE id = :id",
				$params,
				\DATA
			);
			
		}
		
		$params[':type']		= $this->type_id;
		
		$id = 
		$db->setInsert(
			"INSERT INTO documents 
			( lang_id, sort_order, summary, settings, status, type_id )
				VALUES ( :lang, :so, :summary, :settings, :status, :type );",
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

