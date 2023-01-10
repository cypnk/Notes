<?php declare( strict_types = 1 );

namespace Notes;

class DocumentForm extends ContentForm {
	
	public string $summary;
	
	protected static string $form_name	= 'web_document';
	
	public static function newForm(
		\Notes\Controller	$ctrl,
		string			$action = ''
	) {
		return 
		static::loadForm( 
			$ctrl, 
			static::$form_name, 
			'\\Notes\\DocumentForm', 
			$action
		); 
	}
	
	public function render() : string {
		$this->placeholders = [
			'{id}'		=> ( string ) ( $this->id ?? '' ),
			'{summary}'	=> $this->summary ?? ''
		];
		return parent::render();
	}
}

