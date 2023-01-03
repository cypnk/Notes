<?php declare( strict_types = 1 );

namespace Notes;

enum AuthType {
	case Form;
	case Basic;
	case Digest;
	case External;
	
	case Unknown;
}

