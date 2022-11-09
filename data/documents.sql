
-- Generate a random unique string
-- Usage:
-- SELECT id FROM rnd;
CREATE VIEW rnd AS 
SELECT lower( hex( randomblob( 16 ) ) ) AS id;-- --


-- GUID/UUID generator helper
CREATE VIEW uuid AS SELECT lower(
	hex( randomblob( 4 ) ) || '-' || 
	hex( randomblob( 2 ) ) || '-' || 
	'4' || substr( hex( randomblob( 2 ) ), 2 ) || '-' || 
	substr( 'AB89', 1 + ( abs( random() ) % 4 ) , 1 )  ||
	substr( hex( randomblob( 2 ) ), 2 ) || '-' || 
	hex( randomblob( 6 ) )
) AS id;-- --


CREATE TABLE configs (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	settings TEXT NOT NULL DEFAULT '{ "realm" : "" }' COLLATE NOCASE,
	realm TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( settings, '$.realm' ), "" )
	) STORED NOT NULL
);-- --
-- Unique configuration per specific realm
CREATE UNIQUE INDEX idx_config_realm ON configs ( realm ) 
	WHERE realm IS NOT "";-- --

-- Localization
CREATE TABLE languages (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
	label TEXT NOT NULL COLLATE NOCASE,
	display TEXT NOT NULL COLLATE NOCASE,
	iso_code TEXT NOT NULL COLLATE NOCASE,
	sort_order INTEGER NOT NULL DEFAULT 0,
	
	-- Default interface language
	is_default INTEGER NOT NULL DEFAULT 0,
	lang_group TEXT NOT NULL DEFAULT '' COLLATE NOCASE
);-- --
CREATE UNIQUE INDEX idx_lang_label ON languages ( label );-- --
CREATE UNIQUE INDEX idx_lang_iso ON languages ( iso_code );-- --
CREATE INDEX idx_lang_default ON languages ( is_default );-- --
CREATE INDEX idx_lang_sort ON languages ( sort_order );-- --
CREATE INDEX idx_lang_group ON languages ( lang_group );-- --

-- Unset previous default language if new default is set
CREATE TRIGGER language_default_insert BEFORE INSERT ON 
	languages FOR EACH ROW 
WHEN NEW.is_default <> 0 AND NEW.is_default IS NOT NULL
BEGIN
	UPDATE languages SET is_default = 0 
		WHERE is_default IS NOT 0;
END;-- --

CREATE TRIGGER language_default_update BEFORE UPDATE ON 
	languages FOR EACH ROW 
WHEN NEW.is_default <> 0 AND NEW.is_default IS NOT NULL
BEGIN
	UPDATE languages SET is_default = 0 
		WHERE is_default IS NOT 0 AND id IS NOT NEW.id;
END;-- --

-- User profiles
CREATE TABLE users (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	uuid TEXT DEFAULT NULL COLLATE NOCASE,
	username TEXT NOT NULL COLLATE NOCASE,
	password TEXT NOT NULL,

	-- Normalized, lowercase, and stripped of spaces
	user_clean TEXT NOT NULL COLLATE NOCASE,
	display TEXT DEFAULT NULL COLLATE NOCASE,
	bio TEXT DEFAULT NULL COLLATE NOCASE,
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	settings TEXT NOT NULL DEFAULT '{ "setting_id" : "" }' COLLATE NOCASE,
	setting_id TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( settings, '$.setting_id' ), "" )
	) STORED NOT NULL,
	status INTEGER NOT NULL DEFAULT 0
);-- --
CREATE UNIQUE INDEX idx_user_name ON users( username );-- --
CREATE UNIQUE INDEX idx_user_clean ON users( user_clean );-- --
CREATE UNIQUE INDEX idx_user_uuid ON users( uuid )
	WHERE uuid IS NOT NULL;-- --
CREATE INDEX idx_user_created ON users ( created );-- --
CREATE INDEX idx_user_updated ON users ( updated );-- --
CREATE INDEX idx_user_settings ON users ( setting_id ) 
	WHERE setting_id IS NOT "";-- --
CREATE INDEX idx_user_status ON users ( status );-- --

-- User searching
CREATE VIRTUAL TABLE user_search 
	USING fts4( username, tokenize=unicode61 );-- --


-- Cookie based login tokens
CREATE TABLE logins(
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	user_id INTEGER NOT NULL,
	lookup TEXT NOT NULL COLLATE NOCASE,
	updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	hash TEXT DEFAULT NULL,
	
	CONSTRAINT fk_logins_user 
		FOREIGN KEY ( user_id ) 
		REFERENCES users ( id )
		ON DELETE CASCADE
);-- --
CREATE UNIQUE INDEX idx_login_user ON logins( user_id );-- --
CREATE UNIQUE INDEX idx_login_lookup ON logins( lookup );-- --
CREATE INDEX idx_login_updated ON logins( updated );-- --
CREATE INDEX idx_login_hash ON logins( hash )
	WHERE hash IS NOT NULL;-- --


-- Secondary identity providers E.G. two-factor
CREATE TABLE id_providers( 
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	uuid TEXT DEFAULT NULL COLLATE NOCASE,
	label TEXT NOT NULL COLLATE NOCASE,
	sort_order INTEGER NOT NULL DEFAULT 0,
	
	-- Serialized JSON
	settings TEXT NOT NULL DEFAULT 
		'{ 
			"setting_id" : "",
			"realm"	: "http://localhost",
			"scope" : "local"
		}' COLLATE NOCASE,
	setting_id TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( settings, '$.setting_id' ), "" )
	) STORED NOT NULL, 
	realm TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( settings, '$.realm' ), "http://localhost" )
	) STORED NOT NULL,
	realm_scope TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( settings, '$.scope' ), "local" )
	) STORED NOT NULL
);-- --
CREATE UNIQUE INDEX idx_provider_label ON id_providers( label );-- --
CREATE UNIQUE INDEX idx_provider_uuid ON id_providers ( uuid ) 
	WHERE uuid IS NOT NULL;-- --
CREATE INDEX idx_provider_sort ON id_providers( sort_order ASC );-- --
CREATE INDEX idx_provider_settings ON id_providers ( setting_id ) 
	WHERE setting_id IS NOT "";-- --
CREATE UNIQUE INDEX idx_provider_realm ON id_providers( realm, realm_scope );-- --


-- User authentication and activity metadata
CREATE TABLE user_auth(
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	user_id INTEGER NOT NULL,
	provider_id INTEGER DEFAULT NULL,
	email TEXT DEFAULT NULL COLLATE NOCASE,
	mobile_pin TEXT DEFAULT NULL COLLATE NOCASE,
	info TEXT DEFAULT NULL,
	
	-- Activity
	last_ip TEXT DEFAULT NULL COLLATE NOCASE,
	last_ua TEXT DEFAULT NULL COLLATE NOCASE,
	last_active DATETIME DEFAULT NULL,
	last_login DATETIME DEFAULT NULL,
	last_pass_change DATETIME DEFAULT NULL,
	last_lockout DATETIME DEFAULT NULL,
	last_session_id TEXT DEFAULT NULL,
	
	-- Auth status,
	is_approved INTEGER NOT NULL DEFAULT 0,
	is_locked INTEGER NOT NULL DEFAULT 0,
	
	-- Authentication tries
	failed_attempts INTEGER NOT NULL DEFAULT 0,
	failed_last_start DATETIME DEFAULT NULL,
	failed_last_attempt DATETIME DEFAULT NULL,
	
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	expires DATETIME DEFAULT NULL,
	
	CONSTRAINT fk_auth_user 
		FOREIGN KEY ( user_id ) 
		REFERENCES users ( id )
		ON DELETE CASCADE, 
		
	CONSTRAINT fk_auth_provider
		FOREIGN KEY ( provider_id ) 
		REFERENCES id_providers ( id )
		ON DELETE SET NULL
);-- --
CREATE UNIQUE INDEX idx_user_email ON user_auth( email );-- --
CREATE INDEX idx_user_auth_user ON user_auth( user_id );-- --
CREATE INDEX idx_user_auth_provider ON user_auth( provider_id )
	WHERE provider_id IS NOT NULL;-- --
CREATE INDEX idx_user_pin ON user_auth( mobile_pin ) 
	WHERE mobile_pin IS NOT NULL;-- --
CREATE INDEX idx_user_ip ON user_auth( last_ip )
	WHERE last_ip IS NOT NULL;-- --
CREATE INDEX idx_user_ua ON user_auth( last_ua )
	WHERE last_ua IS NOT NULL;-- --
CREATE INDEX idx_user_active ON user_auth( last_active )
	WHERE last_active IS NOT NULL;-- --
CREATE INDEX idx_user_login ON user_auth( last_login )
	WHERE last_login IS NOT NULL;-- --
CREATE INDEX idx_user_session ON user_auth( last_session_id )
	WHERE last_session_id IS NOT NULL;-- --
CREATE INDEX idx_user_auth_approved ON user_auth( is_approved );-- --
CREATE INDEX idx_user_auth_locked ON user_auth( is_locked );-- --
CREATE INDEX idx_user_failed_last ON user_auth( failed_last_attempt )
	WHERE failed_last_attempt IS NOT NULL;-- --
CREATE INDEX idx_user_auth_created ON user_auth( created );-- --
CREATE INDEX idx_user_auth_expires ON user_auth( expires )
	WHERE expires IS NOT NULL;-- --


-- User auth last activity
CREATE VIEW auth_activity AS 
SELECT user_id, 
	provider_id,
	is_approved,
	is_locked,
	last_ip,
	last_ua,
	last_active,
	last_login,
	last_lockout,
	last_pass_change,
	last_session_id,
	failed_attempts,
	failed_last_start,
	failed_last_attempt
	
	FROM user_auth;-- --


-- Auth activity helpers
CREATE TRIGGER user_last_login INSTEAD OF 
	UPDATE OF last_login ON auth_activity
BEGIN 
	UPDATE user_auth SET 
		last_ip			= NEW.last_ip,
		last_ua			= NEW.last_ua,
		last_session_id		= NEW.last_session_id,
		last_login		= CURRENT_TIMESTAMP, 
		last_active		= CURRENT_TIMESTAMP,
		failed_attempts		= 0
		WHERE id = OLD.id;
END;-- --

CREATE TRIGGER user_last_ip INSTEAD OF 
	UPDATE OF last_ip ON auth_activity
BEGIN 
	UPDATE user_auth SET 
		last_ip			= NEW.last_ip, 
		last_ua			= NEW.last_ua,
		last_session_id		= NEW.last_session_id,
		last_active		= CURRENT_TIMESTAMP 
		WHERE id = OLD.id;
END;-- --

CREATE TRIGGER user_last_active INSTEAD OF 
	UPDATE OF last_active ON auth_activity
BEGIN 
	UPDATE user_auth SET last_active = CURRENT_TIMESTAMP
		WHERE id = OLD.id;
END;-- --

CREATE TRIGGER user_last_lockout INSTEAD OF 
	UPDATE OF is_locked ON auth_activity
	WHEN NEW.is_locked = 1
BEGIN 
	UPDATE user_auth SET 
		is_locked	= 1,
		last_lockout	= CURRENT_TIMESTAMP 
		WHERE id = OLD.id;
END;-- --

CREATE TRIGGER user_failed_last_attempt INSTEAD OF 
	UPDATE OF failed_last_attempt ON auth_activity
BEGIN 
	UPDATE user_auth SET 
		last_ip			= NEW.last_ip, 
		last_ua			= NEW.last_ua,
		last_session_id		= NEW.last_session_id,
		last_active		= CURRENT_TIMESTAMP,
		failed_last_attempt	= CURRENT_TIMESTAMP, 
		failed_attempts		= ( failed_attempts + 1 ) 
		WHERE id = OLD.id;
	
	-- Update current start window if it's been 24 hours since 
	-- last window
	UPDATE user_auth SET failed_last_start = CURRENT_TIMESTAMP 
		WHERE id = OLD.id AND ( 
		failed_last_start IS NULL OR ( 
		strftime( '%s', 'now' ) - 
		strftime( '%s', 'failed_last_start' ) ) > 86400 );
END;-- --



-- Login view
-- Usage:
-- SELECT * FROM login_view WHERE lookup = :lookup;
-- SELECT * FROM login_view WHERE name = :username;
CREATE VIEW login_view AS SELECT 
	logins.user_id AS id, 
	users.uuid AS uuid, 
	logins.lookup AS lookup, 
	logins.hash AS hash, 
	logins.updated AS updated, 
	users.status AS status, 
	users.username AS name, 
	users.password AS password, 
	users.settings AS user_settings, 
	ua.is_approved AS is_approved, 
	ua.is_locked AS is_locked, 
	ua.expires AS expires
	
	FROM logins
	JOIN users ON logins.user_id = users.id
	LEFT JOIN user_auth ua ON users.id = ua.user_id;-- --


-- Login regenerate. Not intended for SELECT
-- Usage:
-- UPDATE logout_view SET lookup = '' WHERE user_id = :user_id;
CREATE VIEW logout_view AS 
SELECT user_id, lookup FROM logins;-- --

-- Reset the lookup string to force logout a user
CREATE TRIGGER user_logout INSTEAD OF UPDATE OF lookup ON logout_view
BEGIN
	UPDATE logins SET lookup = ( SELECT id FROM rnd ), 
		updated = CURRENT_TIMESTAMP
		WHERE user_id = NEW.user_id;
END;-- --

-- New user, generate UUID, insert user search and create login lookups
CREATE TRIGGER user_insert AFTER INSERT ON users FOR EACH ROW 
BEGIN
	-- Create search data
	INSERT INTO user_search( docid, username ) 
		VALUES ( NEW.id, NEW.username );
	
	-- New login lookup
	INSERT INTO logins( user_id, lookup )
		VALUES( NEW.id, ( SELECT id FROM rnd ) );
	
	UPDATE users SET uuid = ( SELECT id FROM uuid )
		WHERE id = NEW.id;
END;-- --

-- Update last modified
CREATE TRIGGER user_update AFTER UPDATE ON users FOR EACH ROW
BEGIN
	UPDATE users SET updated = CURRENT_TIMESTAMP 
		WHERE id = OLD.id;
	
	UPDATE user_search 
		SET username = NEW.username || ' ' || NEW.display
		WHERE docid = OLD.id;
END;-- --

-- Delete user search data following user delete
CREATE TRIGGER user_delete BEFORE DELETE ON users FOR EACH ROW 
BEGIN
	DELETE FROM user_search WHERE rowid = OLD.rowid;
END;-- --

-- ID Provider creation
CREATE TRIGGER id_provider_insert AFTER INSERT ON id_providers FOR EACH ROW
BEGIN
	UPDATE id_providers SET uuid = ( SELECT id FROM uuid )
		WHERE id = NEW.id;
END;-- --


-- User roles
CREATE TABLE roles(
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	label TEXT NOT NULL COLLATE NOCASE,
	description TEXT DEFAULT NULL COLLATE NOCASE
);-- --
CREATE UNIQUE INDEX idx_role_label ON roles( label ASC );-- --

-- Third party role permission providers
CREATE TABLE permission_providers(
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	uuid TEXT DEFAULT NULL COLLATE NOCASE,
	label TEXT NOT NULL COLLATE NOCASE,
	settings TEXT NOT NULL DEFAULT 
		'{ 
			"setting_id" : "",
			"realm"	: "http://localhost",
			"scope" : "local"
		}' COLLATE NOCASE,
	setting_id TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( settings, '$.setting_id' ), "" )
	) STORED NOT NULL, 
	realm TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( settings, '$.realm' ), "http://localhost" )
	) STORED NOT NULL,
	realm_scope TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( settings, '$.scope' ), "local" )
	) STORED NOT NULL
);-- --
CREATE UNIQUE INDEX idx_perm_provider_label ON permission_providers( label ASC );-- --
CREATE UNIQUE INDEX idx_perm_provider_uuid ON permission_providers ( uuid ) 
	WHERE uuid IS NOT NULL;-- --
CREATE UNIQUE INDEX idx_perm_provider_realm ON permission_providers( realm, realm_scope );-- --
CREATE INDEX idx_perm_settings ON permission_providers ( setting_id ) 
	WHERE setting_id IS NOT "";-- --

-- Permission provider creation
CREATE TRIGGER perm_provider_insert AFTER INSERT ON permission_providers FOR EACH ROW
BEGIN
	UPDATE permission_providers SET uuid = ( SELECT id FROM uuid )
		WHERE id = NEW.id;
END;-- --

-- Role permissions
CREATE TABLE role_privileges(
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	role_id INTEGER NOT NULL,
	permission_id INTEGER DEFAULT NULL,
	settings TEXT NOT NULL DEFAULT  
		'{ 
			"setting_id"	: "",
			"realm"		: "http://localhost",
			"scope"		: "local",
			"actions"	: []
		}' COLLATE NOCASE,
	setting_id TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( settings, '$.setting_id' ), "" )
	) STORED NOT NULL, 
	realm TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( settings, '$.realm' ), "http://localhost" )
	) STORED NOT NULL,
	realm_scope TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( settings, '$.scope' ), "local" )
	) STORED NOT NULL,
	
	CONSTRAINT fk_privilege_role 
		FOREIGN KEY ( role_id ) 
		REFERENCES roles ( id )
		ON DELETE CASCADE, 
	
	CONSTRAINT fk_privilege_provider
		FOREIGN KEY ( permission_id ) 
		REFERENCES permission_providers ( id )
		ON DELETE RESTRICT
);-- --
CREATE INDEX idx_privilege_role ON role_privileges( role_id );-- --
CREATE INDEX idx_privilege_provider ON role_privileges ( permission_id )
	WHERE permission_id IS NOT NULL;-- --
CREATE UNIQUE INDEX idx_privilege_realm ON role_privileges( realm, realm_scope );-- --
CREATE INDEX idx_privilege_settings ON role_privileges( setting_id ) 
	WHERE setting_id IS NOT "";-- --

-- User role relationships
CREATE TABLE user_roles(
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	role_id INTEGER NOT NULL,
	user_id INTEGER NOT NULL,
	
	CONSTRAINT fk_user_roles_user 
		FOREIGN KEY ( user_id ) 
		REFERENCES users ( id )
		ON DELETE CASCADE,
	
	CONSTRAINT fk_user_roles_role 
		FOREIGN KEY ( role_id ) 
		REFERENCES roles ( id )
		ON DELETE CASCADE
);-- --
CREATE UNIQUE INDEX idx_user_role ON 
	user_roles( role_id, user_id );-- --

-- Role based user permission view
CREATE VIEW user_permission_view AS 
SELECT 
	user_id AS id, 
	GROUP_CONCAT( DISTINCT roles.label ) AS label,
	GROUP_CONCAT( 
		COALESCE( rp.settings, '{}' ), ',' 
	) AS privilege_settings,
	GROUP_CONCAT( rp.realm, ',' ) AS privilege_realms,
	GROUP_CONCAT( rp.realm_scope, ',' ) AS privilege_scopes,
	GROUP_CONCAT( 
		COALESCE( pr.settings, '{}' ), ',' 
	) AS provider_settings,
	GROUP_CONCAT( pr.realm, ',' ) AS provider_realms,
	GROUP_CONCAT( pr.realm_scope, ',' ) AS provider_scopes
	
	FROM user_roles
	JOIN roles ON user_roles.role_id = roles.id
	LEFT JOIN role_privileges rp ON roles.id = rp.role_id
	LEFT JOIN permission_providers pr ON rp.permission_id = pr.id;-- --


-- Structured content types
CREATE TABLE document_types (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	content TEXT NOT NULL DEFAULT '{ "label" : "generic" }' COLLATE NOCASE, 
	label TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.label' ), "generic" )
	) STORED NOT NULL
);-- --
CREATE UNIQUE INDEX idx_doc_label ON document_types ( label );-- --

-- Main content
CREATE TABLE documents (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	uuid TEXT DEFAULT NULL COLLATE NOCASE,
	type_id INTEGER NOT NULL,
	lang_id INTEGER DEFAULT NULL,
	abstract TEXT NOT NULL DEFAULT '' COLLATE NOCASE,
	
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	settings TEXT NOT NULL DEFAULT '{ "setting_id" : "" }' COLLATE NOCASE,
	setting_id TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( settings, '$.setting_id' ), "" )
	) STORED NOT NULL,
	status INTEGER NOT NULL DEFAULT 0,
	
	CONSTRAINT fk_document_type 
		FOREIGN KEY ( type_id ) 
		REFERENCES document_types ( id )
		ON DELETE CASCADE,
	
	CONSTRAINT fk_document_lang 
		FOREIGN KEY ( lang_id ) 
		REFERENCES languages ( id )
		ON DELETE RESTRICT
);-- --
CREATE UNIQUE INDEX idx_doc_uuid ON documents( uuid )
	WHERE uuid IS NOT NULL;-- --
CREATE INDEX idx_doc_type ON documents ( type_id );-- --
CREATE INDEX idx_doc_lang ON documents ( lang_id )
	WHERE lang_id IS NOT NULL;-- --
CREATE INDEX idx_doc_created ON documents ( created );-- --
CREATE INDEX idx_doc_updated ON documents ( updated );-- --
CREATE INDEX idx_doc_settings ON users ( setting_id ) 
	WHERE setting_id IS NOT "";-- --
CREATE INDEX idx_doc_status ON documents ( status );-- --


-- Update document details
CREATE TRIGGER document_insert AFTER INSERT ON documents FOR EACH ROW
BEGIN
	UPDATE documents SET uuid = ( SELECT id FROM uuid )
		WHERE id = NEW.id;
	
	UPDATE documents SET lang_id = (
			SELECT COALESCE( id, NULL ) FROM languages 
				WHERE iso_code = 
					json_extract( NEW.settings, '$.lang' )
			LIMIT 1
		) WHERE id = NEW.id AND 
			json_extract( NEW.settings, '$.lang' ) IS NOT "";
END;-- --

CREATE TRIGGER document_update AFTER UPDATE ON documents FOR EACH ROW
BEGIN
	UPDATE documents SET updated = CURRENT_TIMESTAMP 
		WHERE id = OLD.id;
		
	UPDATE documents SET lang_id = (
			SELECT COALESCE( id, NULL ) FROM languages 
				WHERE iso_code = 
					json_extract( NEW.settings, '$.lang' )
			LIMIT 1
		) WHERE id = NEW.id AND 
			json_extract( NEW.settings, '$.lang' ) IS NOT "";
END;-- --

-- Content segments
CREATE TABLE pages (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	uuid TEXT DEFAULT NULL COLLATE NOCASE,
	document_id INTEGER NOT NULL,
	sort_order INTEGER NOT NULL DEFAULT 0,
	status INTEGER NOT NULL DEFAULT 0,
	
	CONSTRAINT fk_document_page 
		FOREIGN KEY ( document_id ) 
		REFERENCES documents ( id )
		ON DELETE CASCADE
);-- --
CREATE UNIQUE INDEX idx_page_uuid ON pages( uuid )
	WHERE uuid IS NOT NULL;-- --
CREATE INDEX idx_page_document ON pages ( document_id );-- --
CREATE INDEX idx_page_sort ON pages ( sort_order );-- --
CREATE INDEX idx_page_status ON pages ( status );-- --

CREATE TRIGGER page_insert AFTER INSERT ON pages FOR EACH ROW
BEGIN
	UPDATE pages SET uuid = ( SELECT id FROM uuid )
		WHERE id = NEW.id;
END;-- --

-- Content breakpoints
CREATE TABLE page_blocks (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	content TEXT NOT NULL DEFAULT '{ "body" : "" }' COLLATE NOCASE, 
	
	body TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.body' ), "" )
	) STORED NOT NULL, 
	page_id INTEGER NOT NULL,
	lang_id INTEGER DEFAULT NULL,
	sort_order INTEGER NOT NULL DEFAULT 0,
	status INTEGER NOT NULL DEFAULT 0,
	
	CONSTRAINT fk_block_page 
		FOREIGN KEY ( page_id ) 
		REFERENCES pages ( id )
		ON DELETE CASCADE,
	
	CONSTRAINT fk_block_lang 
		FOREIGN KEY ( lang_id ) 
		REFERENCES languages ( id )
		ON DELETE RESTRICT
);-- --
CREATE INDEX idx_block_page ON page_blocks ( page_id );-- --
CREATE INDEX idx_block_lang ON page_blocks ( lang_id )
	WHERE lang_id IS NOT NULL;-- --
CREATE INDEX idx_block_sort ON page_blocks ( sort_order );-- --
CREATE INDEX idx_block_status ON page_blocks ( status );-- --

-- Content searching
CREATE VIRTUAL TABLE block_search 
	USING fts4( body, tokenize=unicode61 );-- --

CREATE TRIGGER block_insert AFTER INSERT ON page_blocks FOR EACH ROW
WHEN NEW.body IS NOT ""
BEGIN
	-- Create search data
	INSERT INTO block_search( docid, body ) 
		VALUES ( NEW.id, NEW.body );
	
	UPDATE page_blocks SET lang_id = (
			SELECT COALESCE( id, NULL ) FROM languages 
				WHERE iso_code = 
					json_extract( NEW.content, '$.lang' )
			LIMIT 1
		) WHERE id = NEW.id AND 
			json_extract( NEW.content, '$.lang' ) IS NOT "";
END;-- --

CREATE TRIGGER block_update AFTER UPDATE ON page_blocks FOR EACH ROW
WHEN NEW.body IS NOT ""
BEGIN
	REPLACE INTO block_search( docid, body ) 
		VALUES ( NEW.id, NEW.body );
	
	UPDATE page_blocks SET lang_id = (
			SELECT COALESCE( id, NULL ) FROM languages 
				WHERE iso_code = 
					json_extract( NEW.content, '$.lang' )
			LIMIT 1
		) WHERE id = NEW.id AND 
			json_extract( NEW.content, '$.lang' ) IS NOT "";
END;-- --

CREATE TRIGGER block_clear AFTER UPDATE ON page_blocks FOR EACH ROW 
WHEN NEW.body IS ""
BEGIN
	DELETE FROM block_search WHERE docid = NEW.id;
END;-- --

CREATE TRIGGER block_delete BEFORE DELETE ON page_blocks FOR EACH ROW 
BEGIN
	DELETE FROM block_search WHERE docid = OLD.id;
END;-- --


-- Special labels
CREATE TABLE memos (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	content TEXT NOT NULL DEFAULT '{ "body" : "" }' COLLATE NOCASE, 
	
	body TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.body' ), "" )
	) STORED NOT NULL,
	lang_id INTEGER DEFAULT NULL,
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	
	CONSTRAINT fk_memo_lang 
		FOREIGN KEY ( lang_id ) 
		REFERENCES languages ( id )
		ON DELETE RESTRICT
);-- --
CREATE INDEX idx_memo_lang ON memos ( lang_id )
	WHERE lang_id IS NOT NULL;-- --
CREATE INDEX idx_memo_created ON memos ( created );-- --
CREATE INDEX idx_memo_updated ON memos ( updated );-- --

-- Memo searching
CREATE VIRTUAL TABLE memo_search 
	USING fts4( body, tokenize=unicode61 );-- --

CREATE TRIGGER memo_insert AFTER INSERT ON memos FOR EACH ROW 
WHEN NEW.body IS NOT ""
BEGIN
	-- Create search data
	INSERT INTO memo_search( docid, body ) 
		VALUES ( NEW.id, NEW.body );
	
END;-- --

CREATE TRIGGER memo_update AFTER UPDATE ON memos FOR EACH ROW 
WHEN NEW.body IS NOT ""
BEGIN
	REPLACE INTO memo_search( docid, body ) 
		VALUES ( NEW.id, NEW.body );
	
	UPDATE memos SET updated = CURRENT_TIMESTAMP 
		WHERE id = NEW.id;
END;-- --

CREATE TRIGGER memo_clear AFTER UPDATE ON memos FOR EACH ROW 
WHEN NEW.body IS ""
BEGIN
	DELETE FROM memo_search WHERE docid = NEW.id;
	DELETE FROM memos WHERE id = NEW.id;
END;-- --

CREATE TRIGGER memo_delete BEFORE DELETE ON memos FOR EACH ROW 
BEGIN
	DELETE FROM memo_search WHERE docid = OLD.id;
END;-- --


CREATE TABLE memo_blocks (
	memo_id INTEGER NOT NULL,
	block_id INTEGER NOT NULL,
	begin_range INTEGER NOT NULL DEFAULT 0,
	end_range INTEGER NOT NULL DEFAULT 0,
	
	CONSTRAINT fk_block_memo  
		FOREIGN KEY ( memo_id ) 
		REFERENCES memos ( id )
		ON DELETE CASCADE,
	
	CONSTRAINT fk_memo_block
		FOREIGN KEY ( block_id ) 
		REFERENCES page_blocks ( id )
		ON DELETE CASCADE
);-- --
CREATE INDEX idx_memo_block ON memo_blocks ( memo_id, block_id );-- --
CREATE UNIQUE INDEX idx_memo_block_range ON memo_blocks 
	( memo_id, block_id, begin_range, end_range )
	WHERE begin_range IS NOT 0 AND end_range IS NOT 0;-- --


-- Remove orphaned memo associations
CREATE TRIGGER memo_block_update AFTER UPDATE ON memo_blocks FOR EACH ROW
BEGIN
	DELETE FROM memo_blocks WHERE begin_range = end_range;
END;-- --

-- Authorship
CREATE TABLE user_documents (
	user_id, INTEGER NOT NULL,
	document_id INTEGER NOT NULL,
	status INTEGER NOT NULL DEFAULT 0,
	
	CONSTRAINT fk_document_user 
		FOREIGN KEY ( user_id ) 
		REFERENCES users ( id )
		ON DELETE CASCADE,
	
	CONSTRAINT fk_user_document 
		FOREIGN KEY ( document_id ) 
		REFERENCES documents ( id )
		ON DELETE CASCADE
);-- --
CREATE UNIQUE INDEX idx_user_doc
	ON user_documents ( user_id, document_id );-- --
CREATE INDEX idx_user_doc_status ON user_documents ( status );-- --

CREATE TABLE user_pages (
	user_id, INTEGER NOT NULL,
	page_id INTEGER NOT NULL,
	status INTEGER NOT NULL DEFAULT 0,
	
	CONSTRAINT fk_page_user 
		FOREIGN KEY ( user_id ) 
		REFERENCES users ( id )
		ON DELETE CASCADE,
	
	CONSTRAINT fk_user_page 
		FOREIGN KEY ( page_id ) 
		REFERENCES pages ( id )
		ON DELETE CASCADE
);-- --
CREATE UNIQUE INDEX idx_user_page ON user_pages ( user_id, page_id );-- --
CREATE INDEX idx_user_page_status ON user_pages ( status );-- --

CREATE TABLE user_blocks (
	user_id, INTEGER NOT NULL,
	block_id INTEGER NOT NULL,
	status INTEGER NOT NULL DEFAULT 0,
	
	CONSTRAINT fk_page_user 
		FOREIGN KEY ( user_id ) 
		REFERENCES users ( id )
		ON DELETE CASCADE,
	
	CONSTRAINT fk_user_block 
		FOREIGN KEY ( block_id ) 
		REFERENCES page_blocks ( id )
		ON DELETE CASCADE
);-- --
CREATE UNIQUE INDEX idx_user_block ON user_blocks ( user_id, block_id );-- --
CREATE INDEX idx_user_block_status ON user_blocks ( status );-- --

CREATE VIEW user_block_view AS SELECT 
		pb.id AS id, 
		pb.content AS content, 
		pb.page_id AS page_id, 
		pb.sort_order AS sort_order, 
		pb.status AS block_status, 
		u.username AS username, 
		u.status AS user_status
		
		FROM page_blocks pb
		LEFT JOIN user_blocks ub ON pb.id = ub.id 
		LEFT JOIN users u ON ub.user_id = u.id;-- --

CREATE TABLE user_memos (
	user_id, INTEGER NOT NULL,
	memo_id INTEGER NOT NULL,
	status INTEGER NOT NULL DEFAULT 0,
	
	CONSTRAINT fk_memo_user 
		FOREIGN KEY ( user_id ) 
		REFERENCES users ( id )
		ON DELETE CASCADE,
	
	CONSTRAINT fk_user_memo 
		FOREIGN KEY ( memo_id ) 
		REFERENCES memos ( id )
		ON DELETE CASCADE
);-- --
CREATE UNIQUE INDEX idx_user_memo ON user_memos ( user_id, memo_id );-- --

-- Copied data
CREATE TABLE clipboard (
	user_id INTEGER NOT NULL,
	content TEXT NOT NULL COLLATE NOCASE,
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	
	CONSTRAINT fk_clip_user 
		FOREIGN KEY ( user_id ) 
		REFERENCES users ( id )
		ON DELETE CASCADE
);-- --
CREATE INDEX idx_clip_user ON clipboard ( user_id );-- --
CREATE INDEX idx_clip_created ON clipboard ( created );-- --

-- Undo history
CREATE TABLE history (
	content TEXT NOT NULL DEFAULT '{ "label" : "action" }' COLLATE NOCASE,
	user_id INTEGER NOT NULL,
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	
	label TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( content, '$.label' ), "action" )
	) STORED NOT NULL,
	
	CONSTRAINT fk_history_user 
		FOREIGN KEY ( user_id ) 
		REFERENCES users ( id )
		ON DELETE RESTRICT
);-- --
CREATE INDEX idx_history_user ON history ( user_id );-- --
CREATE INDEX idx_history_label ON history ( label );-- --
CREATE INDEX idx_history_created ON history ( created );-- --

-- Errors and notices
CREATE TABLE messages(
	content TEXT NOT NULL DEFAULT '{ "type" : "notice" }' COLLATE NOCASE,
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	mtype TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( content, '$.type' ), "notice" )
	) STORED NOT NULL
);-- --
CREATE INDEX idx_message_type ON messages ( mtype );-- --
CREATE INDEX idx_message_created ON messages ( created );-- --

-- Function execution
CREATE TABLE operations (
	label TEXT NOT NULL COLLATE NOCASE,
	pattern TEXT NOT NULL DEFAULT "" COLLATE NOCASE,
	settings TEXT NOT NULL DEFAULT '{ "realm" : "" }' COLLATE NOCASE,
	realm TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( settings, '$.realm' ), "/" )
	) STORED NOT NULL,
	
	-- Pattern match event
	event TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( settings, '$.event' ), "operation" )
	) STORED NOT NULL,
	
	-- E.G. global, document, page, block, memo, user, role
	op_scope TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( settings, '$.scope' ), "global" )
	) STORED NOT NULL,
	method TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( settings, '$.method' ), "get" )
	) STORED NOT NULL
);-- --
CREATE UNIQUE INDEX idx_operation ON operations ( label, pattern, op_scope );-- --
CREATE UNIQUE INDEX idx_operation_event ON operations ( event )
	WHERE event IS NOT "operation";-- --
CREATE INDEX idx_operation_realm ON configs ( realm ) WHERE realm IS NOT "";-- --
CREATE INDEX idx_operation_method ON operations ( method );-- --

