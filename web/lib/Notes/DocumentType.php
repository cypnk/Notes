<?php declare( strict_types = 1 );

namespace Notes;

class DocumentType extends Content {
	
	public readonly string $label;
	
	public function save() : bool {
		$this->_content['label']	= $this->label;
		$db				= $this->getData();
		
		if ( isset( $this->id ) ) {
			return 
			$db->setUpdate(
				"UPDATE document_types SET content = :content 
					WHERE id = :id LIMIT 1;",
				[ 
					':content' 	=> $this->content,
					':id'		=> $this->id
				],
				\DATA
			);
		}
		
		$id = 
		$db->setInsert(
			"INSERT INTO document_types ( content ) 
				VALUES ( :content );",
			[ 
				':content' 	=> $this->content
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
