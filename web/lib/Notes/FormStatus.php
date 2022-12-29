<?php declare( strict_types = 1 );

namespace Notes;

enum FormStatus {
	case Valid;
	case Invalid;
	
	case Expired;
	case Flood;
	
	public function mode() : string {
		return 
		match( $this ) {
			FormStatus::Valid,
			FormStatus::Inalid		=> 'validity',
			
			FormStatus::Expired,
			FormStatus::Flood		=> 'frequency'
		};
	}
}

