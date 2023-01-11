<?php declare( strict_types = 1 );

namespace Notes;

class ContentForm extends Form {
	
	public int $id;
	
	protected static string $form_name	= 'web_content';
	
	public function render() : string {
		$this->placeholders = [
			'{id}'		=> ( string ) ( $this->id ?? '' )
		];
		return parent::render();
	}
	
}
