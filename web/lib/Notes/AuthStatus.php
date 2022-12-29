<?php declare( strict_types = 1 );

namespace Notes;

enum AuthStatus {
	case Success;
	case Failed;
	
	case NoUser;
	case Duplicate;
	case LockedOut;
	case Unapproved;
	
	case PinError;
	case EmailError;
	
	case AuthProviderError;
	case PermProviderError;
	
	case UserError;
	case SessionError;
	case CookieError;
	case RealmError;
	case ScopeError;
	case RoleError;
	
	/**
	 *  Success or failure mode groups
	 *  @return string
	 */
	public function mode() : string {
		return 
		match( $this ) {
			AuthStatus::Success,
			AuthStatus::Failed,
			AuthStatus::Duplicate,
			AuthStatus::NoUser		=> 'credentials',
			
			AuthStatus::PinError,
			AuthStatus::EmailError		=> 'twofactor',
			
			AuthStatus::LockedOut,
			AuthStatus::Unapproved,
			AuthStatus::RoleError		=> 'permissions',
			
			AuthStatus::AuthProviderError,
			AuthStatus::PermProviderError	=> 'provider',
			
			AuthStatus::UserError,
			AuthStatus::SessionError,
			AuthStatus::CookieError		=> 'session',
			
			AuthStatus::RealmError,
			AuthStatus::ScopeError		=> 'location'
		};
	}
}

