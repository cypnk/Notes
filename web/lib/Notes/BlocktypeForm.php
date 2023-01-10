<?php declare( strict_types = 1 );

namespace Notes;

class BlocktypeForm extends ContentForm {
	
	public string $label;
	
	public string $create_template;
	
	public string $edit_template;
	
	public string $view_template;
	
	protected static string $form_name	= 'web_blocktype';
	
	public static function newForm(
		\Notes\Controller	$ctrl,
		string			$action = ''
	) {
		return 
		static::loadForm( 
			$ctrl, 
			static::$form_name, 
			'\\Notes\\BlocktypeForm', 
			$action
		); 
	}
	
	public function render() : string {
		$this->placeholders = [
			'{id}'			=> ( string ) ( $this->id ?? '' ),
			'{label}'		=> $this->label ?? '',
			'{view_template}'	=> $this->view_template ?? '',
			'{edit_template}'	=> $this->edit_template ?? '',
			'{create_template}'	=> $this->create_template ?? ''
		];
		return parent::render();
	}
}

