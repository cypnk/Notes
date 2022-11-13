<?php declare( strict_types = 1 );

namespace Notes;

class Document extends Content {
	
	public readonly int $type_id;
	
	public string $summary;
	
	public array $pages	= [];
	
	public function __construct() {
		
	}
	
	public function docType( ?int $id = null ) : int {
		if ( empty( $id ) ) {
			return $this->type_id ?? 0;
		}
		
		if ( isset( $this->type_id ) ) {
			return $this->type_id;
		}
		$this->type_id = $id;
		return $id;
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
		$db	= $this->getData();
		if ( isset( $this->id ) ) {
			// Save loaded pages first
			foreach ( $p as $this->pages ) {
				$p->save();
			}
			
			return 
			$db->setUpdate(
				"UPDATE documents SET lang_id = :lang,
					sort_order = :so, 
					summary = :summary,
					settings = :settings, 
					status = :status WHERE id = :id"
				[
					':lang'		=> $this->lang_id ?? null,
					':so'		=> $this->sort_order ?? 0,
					':summary'	=> $this->summary ?? '',
					':settings'	=> 
					\Notes\Util::encode( $this->settings ),
					':id'	=> $this->id
				],
				\DATA
			);
			
		}
		
		$id = 
		$db->setInsert(
			"INSERT INTO documents 
			( type_id, lang_id, sort_order, summary, settings )
				VALUES ( :type, :lang, :so, :summary, :settings );",
			[
				':type'		=> $this->type_id,
				':lang'		=> $this->lang_id ?? null,
				':so'		=> $this->sort_order ?? 0,
				':summary'	=> $this->summary ?? '',
				':settings'	=> 
					\Notes\Util::encode( $this->settings ),
				':id'	=> $this->id
			],
			\DATA
		);
		if ( empty( $id ) )) {
			return false;
		}
		$this->id = $id;
		return true;
	}
}

