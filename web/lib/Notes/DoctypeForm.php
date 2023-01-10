<?php declare( strict_types = 1 );

namespace Notes;

class DoctypeForm extends ContentForm {
	
	public string $label;
	
	public string $description;
	
	protected static string $form_name	= 'web_doctype';
	
	public static function newForm(
		\Notes\Controller	$ctrl,
		string			$action = ''
	) {
		return 
		static::loadForm( 
			$ctrl, 
			static::$form_name, 
			'\\Notes\\DoctypeForm', 
			$action
		); 
	}
	
	public function render() : string {
		$this->placeholders = [
			'{id}'		=> ( string ) ( $this->id ?? '' ),
			'{label}'	=> $this->label ?? '',
			'{description}'	=> $this->description ?? ''
		];
		return parent::render();
	}
}

