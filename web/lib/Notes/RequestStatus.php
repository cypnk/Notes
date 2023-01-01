<?php declare( strict_types = 1 );

namespace Notes;

enum RequestStatus {
	
	case Search;
	case Captcha;
	
	case UserView;
	case UserManage;
	case UserLogin;
	case UserRegister;
	case UserEdit;
	case UserSave;
	case UserDelete;
	
	case DocumentView;
	case DocumentManage;
	case DocumentNew;
	case DocumentEdit;
	case DocumentSave;
	case DocumentDelete;
	
	case PageView;
	case PageManage;
	case PageNew;
	case PageEdit;
	case PageSave;
	case PageDelete;
	
	case BlockTypeView;
	case BlockTypeManage;
	case BlockTypeNew;
	case BlockTypeEdit;
	case BlockTypeSave;
	case BlockTypeDelete;
	
	case BlockView;
	case BlockManage;
	case BlockNew;
	case BlockEdit;
	case BlockSave;
	case BlockDelete;
	
	case MemoView;
	case MemoManage;
	case MemoNew;
	case MemoEdit;
	case MemoSave;
	case MemoDelete;
	
	case MarkView;
	case MarkManage;
	case MarkNew;
	case MarkEdit;
	case MarkSave;
	case MarkDelete;
	
	case ConfigView;
	case ConfigManage;
	case ConfigEdit;
	case ConfigSave;
	
	case FileResourceView;
	case FileResourceManage;
	case FileResourceNew;
	case FileResourceRange;
	case FileResourceEdit;
	case FileResourceDelete;
	
	public function mode() : string {
		return 
		match( $this ) {
			RequestStatus::Search		=> 'search',
			RequestStatus::Captcha		=> 'captcha',
			
			RequestStatus::UserView,
			RequestStatus::UserManage,
			RequestStatus::UserLogin,
			RequestStatus::UserRegister,
			RequestStatus::UserEdit,
			RequestStatus::UserSave,
			RequestStatus::UserDelete	=> 'user',
			
			RequestStatus::DocumentView,
			RequestStatus::DocumentManage,
			RequestStatus::DocumentNew,
			RequestStatus::DocumentEdit,
			RequestStatus::DocumentSave,
			RequestStatus::DocumentDelete	=> 'document',
			
			RequestStatus::PageView,
			RequestStatus::PageManage,
			RequestStatus::PageNew,
			RequestStatus::PageEdit,
			RequestStatus::PageSave,
			RequestStatus::PageDelete	=> 'page',
			
			RequestStatus::BlockTypeView,
			RequestStatus::BlockTypeManage,
			RequestStatus::BlockTypeNew,
			RequestStatus::BlockTypeEdit,
			RequestStatus::BlockTypeSave,
			RequestStatus::BlockTypeDelete	=> 'blocktype',
			
			RequestStatus::BlockView,
			RequestStatus::BlockManage,
			RequestStatus::BlockNew,
			RequestStatus::BlockEdit,
			RequestStatus::BlockSave,
			RequestStatus::BlockDelete	=> 'block',
			
			RequestStatus::MemoView,
			RequestStatus::MemoManage,
			RequestStatus::MemoNew,
			RequestStatus::MemoEdit,
			RequestStatus::MemoSave,
			RequestStatus::MemoDelete	=> 'memo',
			
			RequestStatus::MarkView,
			RequestStatus::MarkManage,
			RequestStatus::MarkNew,
			RequestStatus::MarkEdit,
			RequestStatus::MarkSave,
			RequestStatus::MarkDelete	=> 'mark',
			
			RequestStatus::ConfigView,
			RequestStatus::ConfigManage,
			RequestStatus::ConfigEdit,
			RequestStatus::ConfigSave	=> 'config',
			
			RequestStatus::FileResourceView,
			RequestStatus::FileResourceManage,
			RequestStatus::FileResourceRange,
			RequestStatus::FileResourceNew,
			RequestStatus::FileResourceEdit,
			RequestStatus::FileResourceDelete
							=> 'file'
		};
	}
}


