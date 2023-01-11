<?php declare( strict_types = 1 );

namespace Notes;

class UserProfileForm extends UserAuthForm {
	
	public string $display;
	
	public string $bio;
	
	protected static string $form_name	= 'web_user_profile';
	
	public static function newForm(
		\Notes\Controller	$ctrl,
		string			$action = ''
	) {
		return 
		static::loadForm( 
			$ctrl, 
			static::$form_name, 
			'\\Notes\\UserProfileForm', 
			$action
		); 
	}
	
	public function render() : string {
		$this->placeholders = [
			'{username}'	=> $this->username ?? '',
			'{display}'	=> $this->display ?? '',
			'{bio}'		=> $this->bio ?? ''
		];
		return parent::render();
	}
}

