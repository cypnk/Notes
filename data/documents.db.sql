
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
		COALESCE( json_extract( settings, '$.realm' ), '' )
	) STORED NOT NULL
);-- --
-- Unique configuration per specific realm
CREATE UNIQUE INDEX idx_config_realm ON configs ( realm ) 
	WHERE realm IS NOT "";-- --

CREATE TABLE config_stats (
	config_id INTEGER NOT NULL,
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	
	CONSTRAINT fk_config_stats
		FOREIGN KEY ( config_id ) 
		REFERENCES configs ( id )
		ON DELETE CASCADE
);-- --
CREATE INDEX idx_config_stats ON config_stats ( config_id );-- --
CREATE INDEX idx_config_created ON config_stats ( created );-- --
CREATE INDEX idx_config_updated ON config_stats ( updated );-- --

CREATE TRIGGER config_insert AFTER INSERT ON configs FOR EACH ROW
BEGIN
	INSERT INTO config_stats ( config_id ) VALUES ( NEW.id );
END;-- --

CREATE TRIGGER config_update AFTER UPDATE ON configs FOR EACH ROW
BEGIN
	UPDATE config_stats SET updated = CURRENT_TIMESTAMP 
		WHERE config_id = NEW.id;
END;-- --

CREATE TABLE themes(
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	settings TEXT NOT NULL DEFAULT '{ "label" : "default" }' COLLATE NOCASE,
	label TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( settings, '$.label' ), 'default' )
	) STORED NOT NULL,
	realm TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( settings, '$.realm' ), '' )
	) STORED NOT NULL
);-- --
CREATE UNIQUE INDEX idx_theme_label ON themes ( label )
	WHERE label IS NOT "";-- --
CREATE INDEX idx_theme_realm ON themes ( realm );-- --

-- Permanent events
CREATE TABLE events (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	name TEXT NOT NULL COLLATE NOCASE,
	
	-- Execution parameters
	params TEXT NOT NULL DEFAULT '{}' COLLATE NOCASE,
	realm TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( params, '$.realm' ), '' )
	) STORED NOT NULL,
	sort_order INTEGER NOT NULL DEFAULT 0
);-- --
CREATE UNIQUE INDEX idx_event_name ON events ( name, realm );-- --
CREATE INDEX idx_event_realm ON events ( realm ) 
	WHERE realm IS NOT "";-- --
CREATE INDEX idx_event_sort ON events ( sort_order );-- --

-- Standard event formatting
CREATE TRIGGER event_insert_format AFTER INSERT ON events FOR EACH ROW
BEGIN
	UPDATE events SET name = REPLACE( LOWER( TRIM( NEW.name ) ), ' ', '_' )
		WHERE id = NEW.id;
END;-- --

CREATE TRIGGER event_update_format AFTER UPDATE ON events FOR EACH ROW
BEGIN
	UPDATE events SET name = REPLACE( LOWER( TRIM( NEW.name ) ), ' ', '_' )
		WHERE id = NEW.id;
END;-- --

CREATE VIEW event_view AS 
SELECT name, realm, params, sort_order FROM events;-- --


-- Permanent handlers
CREATE TABLE handlers (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	params TEXT NOT NULL DEFAULT '{}' COLLATE NOCASE,
	payload TEXT GENERATED ALWAYS AS (
		json_extract( params, '$.payload' )
	) STORED NOT NULL, 
	event_name TEXT GENERATED ALWAYS AS ( 
		REPLACE( LOWER( TRIM( 
			COALESCE( json_extract( params, '$.event' ), ''	) 
		) ), ' ', '_' )
	) STORED NOT NULL, 
	priority INTEGER GENERATED ALWAYS AS ( 
		CAST( COALESCE( json_extract( 
			params, '$.priority' 
		), 0 ) AS INTEGER )
	) STORED NOT NULL
);-- --
CREATE INDEX idx_handler_name ON handlers ( event_name );-- --
CREATE INDEX idx_handler_priority ON handlers ( priority );-- --

-- Matching handlers to their events
CREATE TABLE event_handlers(
	event_id INTEGER DEFAULT NULL,
	handler_id INTEGER NOT NULL,
	
	CONSTRAINT fk_handler_event 
		FOREIGN KEY ( event_id ) 
		REFERENCES events ( id )
		ON DELETE CASCADE,
	
	CONSTRAINT fk_event_handler
		FOREIGN KEY ( handler_id ) 
		REFERENCES handlers ( id )
		ON DELETE CASCADE
);-- --
CREATE UNIQUE INDEX idx_event_handler 
	ON event_handlers ( event_id, handler_id )
	WHERE event_id IS NOT NULL;-- --

CREATE TRIGGER handler_insert AFTER INSERT ON handlers FOR EACH ROW 
WHEN NEW.event_name IS NOT ""
BEGIN
	REPLACE INTO event_handlers( event_id, handler_id ) 
		VALUES ( 
			( SELECT COALESCE( id, NULL ) 
			 	FROM events 
				WHERE name = NEW.event_name LIMIT 1 
			), NEW.id 
		);
END;-- --


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

-- Language definitions/translations
CREATE TABLE language_defs (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	lang_id INTEGER DEFAULT NULL,
	
	content TEXT NOT NULL DEFAULT '{ "label" : "unknown" }' COLLATE NOCASE, 
	label TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.label' ), 'unknown' )
	) STORED NOT NULL,
	
	CONSTRAINT fk_definition_lang 
		FOREIGN KEY ( lang_id ) 
		REFERENCES languages ( id )
		ON DELETE RESTRICT
);-- --
CREATE UNIQUE INDEX idx_translation_label ON language_defs ( label );-- --


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
	settings TEXT NOT NULL DEFAULT '{ "setting_id" : "" }' COLLATE NOCASE,
	setting_id TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( settings, '$.setting_id' ), '' )
	) STORED NOT NULL,
	status INTEGER NOT NULL DEFAULT 0
);-- --
CREATE UNIQUE INDEX idx_user_name ON users( username );-- --
CREATE UNIQUE INDEX idx_user_clean ON users( user_clean );-- --
CREATE UNIQUE INDEX idx_user_uuid ON users( uuid )
	WHERE uuid IS NOT NULL;-- --
CREATE INDEX idx_user_settings ON users ( setting_id ) 
	WHERE setting_id IS NOT "";-- --
CREATE INDEX idx_user_status ON users ( status );-- --

CREATE TABLE user_stats (
	user_id INTEGER NOT NULL,
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	
	CONSTRAINT fk_user_stats
		FOREIGN KEY ( user_id ) 
		REFERENCES users ( id )
		ON DELETE CASCADE
);-- --
CREATE INDEX idx_user_stats ON user_stats ( user_id );-- --
CREATE INDEX idx_user_created ON user_stats ( created );-- --
CREATE INDEX idx_user_updated ON user_stats ( updated );-- --

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
		COALESCE( json_extract( settings, '$.setting_id' ), '' )
	) STORED NOT NULL, 
	realm TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( settings, '$.realm' ), 'http://localhost' )
	) STORED NOT NULL,
	realm_scope TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( settings, '$.scope' ), 'local' )
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
	logins.id AS id,
	logins.user_id AS user_id, 
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

-- Post-login user data
CREATE VIEW user_view AS SELECT 
	users.id AS id, 
	users.uuid AS uuid, 
	users.status AS status, 
	users.username AS username, 
	users.password AS password, 
	users.settings AS user_settings, 
	users.hash AS hash,
	ua.is_approved AS is_approved, 
	ua.is_locked AS is_locked, 
	ua.expires AS expires, 
	us.created AS created, 
	us.updated AS updated
	
	FROM users
	LEFT JOIN user_auth ua ON users.id = ua.user_id
	LEFT JOIN user_stats us ON users.id = us.user_id;-- --
	

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
	
	INSERT INTO user_stats ( user_id ) VALUES ( NEW.id );
END;-- --

-- Update last modified
CREATE TRIGGER user_update AFTER UPDATE ON users FOR EACH ROW
BEGIN
	UPDATE user_stats SET updated = CURRENT_TIMESTAMP 
		WHERE user_id = NEW.id;
	
	UPDATE user_search 
		SET username = NEW.username || ' ' || NEW.display
		WHERE docid = NEW.id;
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
		COALESCE( json_extract( settings, '$.setting_id' ), '' )
	) STORED NOT NULL, 
	realm TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( settings, '$.realm' ), 'http://localhost' )
	) STORED NOT NULL,
	realm_scope TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( settings, '$.scope' ), 'local' )
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
		COALESCE( json_extract( settings, '$.setting_id' ), '' )
	) STORED NOT NULL, 
	realm TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( settings, '$.realm' ), 'http://localhost' )
	) STORED NOT NULL,
	realm_scope TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( settings, '$.scope' ), 'local' )
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


-- JSON Formatted universal content forms
CREATE TABLE forms(
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	params TEXT NOT NULL DEFAULT '{ "label" : "generic" }' COLLATE NOCASE, 
	label TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( params, '$.label' ), 'generic' )
	) STORED NOT NULL
);-- --
CREATE UNIQUE INDEX idx_form_label ON forms ( label );-- --

-- Structured content types
CREATE TABLE document_types (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	content TEXT NOT NULL DEFAULT '{ "label" : "generic" }' COLLATE NOCASE, 
	label TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.label' ), 'generic' )
	) STORED NOT NULL
);-- --
CREATE UNIQUE INDEX idx_doc_label ON document_types ( label );-- --

-- Main content
CREATE TABLE documents (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	uuid TEXT DEFAULT NULL COLLATE NOCASE,
	type_id INTEGER NOT NULL,
	lang_id INTEGER DEFAULT NULL,
	summary TEXT NOT NULL DEFAULT '' COLLATE NOCASE,
	
	settings TEXT NOT NULL DEFAULT '{ "setting_id" : "" }' COLLATE NOCASE,
	setting_id TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( settings, '$.setting_id' ), '' )
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
CREATE INDEX idx_doc_settings ON users ( setting_id ) 
	WHERE setting_id IS NOT "";-- --
CREATE INDEX idx_doc_status ON documents ( status );-- --

CREATE TABLE document_stats (
	document_id INTEGER NOT NULL,
	page_count INTEGER NOT NULL DEFAULT 0,
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	
	CONSTRAINT fk_document_stats 
		FOREIGN KEY ( document_id ) 
		REFERENCES documents ( id )
		ON DELETE CASCADE
);-- --
CREATE INDEX idx_doc_stats ON document_stats ( document_id );-- --
CREATE INDEX idx_doc_created ON document_stats ( created );-- --
CREATE INDEX idx_doc_updated ON document_stats ( updated );-- --


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
	
	INSERT INTO document_stats ( document_id ) VALUES ( NEW.id );
END;-- --

CREATE TRIGGER document_update AFTER UPDATE ON documents FOR EACH ROW
BEGIN
	UPDATE document_stats SET updated = CURRENT_TIMESTAMP 
		WHERE document_id = NEW.id;
	
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

CREATE TABLE page_stats (
	page_id INTEGER NOT NULL,
	block_count INTEGER NOT NULL DEFAULT 0,
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	
	CONSTRAINT fk_page_stats
		FOREIGN KEY ( page_id ) 
		REFERENCES pages ( id )
		ON DELETE CASCADE
);-- --
CREATE INDEX idx_page_stats ON page_stats ( page_id );-- --
CREATE INDEX idx_page_created ON page_stats ( created );-- --
CREATE INDEX idx_page_updated ON page_stats ( updated );-- --

CREATE TRIGGER page_insert AFTER INSERT ON pages FOR EACH ROW
BEGIN
	UPDATE pages SET uuid = ( SELECT id FROM uuid )
		WHERE id = NEW.id;
	
	INSERT INTO page_stats ( page_id ) VALUES ( NEW.id );
	
	UPDATE document_stats SET page_count = ( page_count + 1 )
		WHERE document_id = NEW.document_id;
END;-- --

CREATE TRIGGER page_update AFTER INSERT ON pages FOR EACH ROW
BEGIN
	UPDATE page_stats SET updated = CURRENT_TIMESTAMP 
		WHERE page_id = NEW.id;
END;-- --

CREATE TRIGGER page_delete BEFORE DELETE ON pages FOR EACH ROW
BEGIN
	UPDATE document_stats SET page_count = ( page_count - 1 )
		WHERE document_id = OLD.document_id;
END;-- --

-- Content types
CREATE TABLE block_types (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	content TEXT NOT NULL DEFAULT '{ "label" : "text" }' COLLATE NOCASE, 
	label TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.label' ), 'text' )
	) STORED NOT NULL,
	view_template TEXT NOT NULL COLLATE NOCASE, 
	create_template TEXT NOT NULL COLLATE NOCASE, 
	edit_template TEXT NOT NULL COLLATE NOCASE, 
	delete_template TEXT NOT NULL COLLATE NOCASE
);-- --
CREATE UNIQUE INDEX idx_block_type_label ON block_types ( label );-- --

-- Content breakpoints
CREATE TABLE page_blocks (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	content TEXT NOT NULL DEFAULT '{ "body" : "" }' COLLATE NOCASE, 
	
	body TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.body' ), '' )
	) STORED NOT NULL, 
	
	type_id INTEGER NOT NULL,
	page_id INTEGER NOT NULL,
	lang_id INTEGER DEFAULT NULL,
	sort_order INTEGER NOT NULL DEFAULT 0,
	status INTEGER NOT NULL DEFAULT 0,
	
	CONSTRAINT fk_block_type 
		FOREIGN KEY ( type_id ) 
		REFERENCES block_types ( id )
		ON DELETE CASCADE,
	
	CONSTRAINT fk_block_page 
		FOREIGN KEY ( page_id ) 
		REFERENCES pages ( id )
		ON DELETE CASCADE,
	
	CONSTRAINT fk_block_lang 
		FOREIGN KEY ( lang_id ) 
		REFERENCES languages ( id )
		ON DELETE RESTRICT
);-- --
CREATE INDEX idx_block_type ON page_blocks ( type_id );-- --
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

CREATE TRIGGER block_stat_insert AFTER INSERT ON page_blocks FOR EACH ROW
BEGIN
	UPDATE page_stats SET block_count = ( block_count + 1 )
		WHERE page_id = NEW.page_id;
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
	
	UPDATE page_stats SET block_count = ( block_count - 1 )
		WHERE page_id = OLD.page_id;
END;-- --


-- Special labels
CREATE TABLE memos (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	content TEXT NOT NULL DEFAULT '{ "body" : "" }' COLLATE NOCASE, 
	
	body TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.body' ), '' )
	) STORED NOT NULL,
	lang_id INTEGER DEFAULT NULL,
	
	CONSTRAINT fk_memo_lang 
		FOREIGN KEY ( lang_id ) 
		REFERENCES languages ( id )
		ON DELETE RESTRICT
);-- --
CREATE INDEX idx_memo_lang ON memos ( lang_id )
	WHERE lang_id IS NOT NULL;-- --

CREATE TABLE memo_stats (
	memo_id INTEGER NOT NULL,
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	
	CONSTRAINT fk_memo_stats
		FOREIGN KEY ( memo_id ) 
		REFERENCES memos ( id )
		ON DELETE CASCADE
);-- --
CREATE INDEX idx_memo_stats ON memo_stats ( memo_id );-- --
CREATE INDEX idx_memo_created ON memo_stats ( created );-- --
CREATE INDEX idx_memo_updated ON memo_stats ( updated );-- --

-- Memo searching
CREATE VIRTUAL TABLE memo_search 
	USING fts4( body, tokenize=unicode61 );-- --

CREATE TRIGGER memo_insert AFTER INSERT ON memos FOR EACH ROW 
WHEN NEW.body IS NOT ""
BEGIN
	-- Create search data
	INSERT INTO memo_search( docid, body ) 
		VALUES ( NEW.id, NEW.body );
	
	INSERT INTO memo_stats ( memo_id ) VALUES ( NEW.id );
END;-- --

CREATE TRIGGER memo_update AFTER UPDATE ON memos FOR EACH ROW 
WHEN NEW.body IS NOT ""
BEGIN
	REPLACE INTO memo_search( docid, body ) 
		VALUES ( NEW.id, NEW.body );
	
	UPDATE memo_stats SET updated = CURRENT_TIMESTAMP 
		WHERE memo_id = NEW.id;
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
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
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


-- Bookmarks/dog ears
CREATE TABLE user_marks (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	user_id INTEGER NOT NULL,
	content TEXT NOT NULL DEFAULT '{ "label" : "read" }' COLLATE NOCASE, 
	label TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.label' ), 'read' )
	) STORED NOT NULL,
	
	-- Text, highlight range {start,end}, flag, number, etc...
	format TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.format' ), 'text' )
	) STORED NOT NULL, 
	document_id INTEGER GENERATED ALWAYS AS ( 
		CAST( COALESCE( json_extract( content, '$.document_id' ), NULL ) AS INTEGER )
	) STORED, 
	page_id INTEGER GENERATED ALWAYS AS ( 
		CAST( COALESCE( json_extract( content, '$.page_id' ), NULL ) AS INTEGER )
	) STORED, 
	block_id INTEGER GENERATED ALWAYS AS ( 
		CAST( COALESCE( json_extract( content, '$.block_id' ), NULL ) AS INTEGER )
	) STORED, 
	memo_id INTEGER GENERATED ALWAYS AS ( 
		CAST( COALESCE( json_extract( content, '$.memo_id' ), NULL ) AS INTEGER )
	) STORED, 
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	expires DATETIME DEFAULT NULL,
	sort_order INTEGER NOT NULL DEFAULT 0,
	status INTEGER NOT NULL DEFAULT 0,
	
	CONSTRAINT fk_mark_user 
		FOREIGN KEY ( user_id ) 
		REFERENCES users ( id )
		ON DELETE CASCADE,
	
	CONSTRAINT fk_mark_document 
		FOREIGN KEY ( document_id ) 
		REFERENCES documents ( id )
		ON DELETE CASCADE,
	
	CONSTRAINT fk_mark_page 
		FOREIGN KEY ( page_id ) 
		REFERENCES pages ( id )
		ON DELETE CASCADE,
	
	CONSTRAINT fk_mark_block 
		FOREIGN KEY ( block_id ) 
		REFERENCES page_blocks ( id )
		ON DELETE CASCADE,
	
	CONSTRAINT fk_mark_memo 
		FOREIGN KEY ( memo_id ) 
		REFERENCES memos ( id )
		ON DELETE CASCADE
);-- --
CREATE INDEX idx_mark_user ON user_marks ( user_id );-- --
CREATE INDEX idx_mark_label ON user_marks ( label );-- --
CREATE INDEX idx_mark_format ON user_marks ( format )
	WHERE format IS NOT "";-- --
CREATE INDEX idx_mark_doc ON user_marks ( document_id )
	WHERE document_id IS NOT NULL;-- --
CREATE INDEX idx_mark_page ON user_marks ( page_id )
	WHERE page_id IS NOT NULL;-- --
CREATE INDEX idx_mark_block ON user_marks ( block_id )
	WHERE block_id IS NOT NULL;-- --
CREATE UNIQUE INDEX idx_mark_memo ON user_marks ( memo_id )
	WHERE memo_id IS NOT NULL;-- --
CREATE UNIQUE INDEX idx_mark_full ON user_marks ( document_id, page_id, block_id )
	WHERE document_id IS NOT NULL AND page_id IS NOT NULL AND block_id IS NOT NULL;-- --
CREATE INDEX idx_mark_created ON user_marks ( created );-- --
CREATE INDEX idx_mark_expires ON user_marks ( expires )
	WHERE expires IS NOT NULL;-- --
CREATE INDEX idx_mark_sort ON user_marks ( sort_order );-- --
CREATE INDEX idx_mark_status ON user_marks ( status );-- --

-- Mark self clean
CREATE TRIGGER user_mark_insert AFTER INSERT ON user_marks FOR EACH ROW 
BEGIN
	DELETE FROM user_marks WHERE 
		document_id IS NULL AND 
		page_id IS NULL AND 
		block_id IS NULL AND 
		memo_id IS NULL;
	
	DELETE FROM user_marks WHERE expires IS NOT NULL AND 
		strftime( '%s', expires ) < strftime( '%s','now' );
END;-- --

CREATE TRIGGER user_mark_update AFTER UPDATE ON user_marks FOR EACH ROW 
BEGIN
	DELETE FROM user_marks WHERE 
		document_id IS NULL AND 
		page_id IS NULL AND 
		block_id IS NULL AND 
		memo_id IS NULL;
	
	DELETE FROM user_marks WHERE expires IS NOT NULL AND 
		strftime( '%s', expires ) < strftime( '%s','now' );
END;-- --


-- Content hierarchies
CREATE TABLE document_tree (
	parent_id INTEGER NOT NULL,
	child_id INTEGER NOT NULL,
	
	CONSTRAINT fk_parent_document 
		FOREIGN KEY ( parent_id ) 
		REFERENCES documents ( id )
		ON DELETE CASCADE,
	
	CONSTRAINT fk_child_document 
		FOREIGN KEY ( child_id ) 
		REFERENCES documents ( id )
		ON DELETE CASCADE
);-- --
CREATE UNIQUE INDEX idx_document_tree ON document_tree ( parent_id, child_id );-- --

CREATE TABLE page_tree (
	parent_id INTEGER NOT NULL,
	child_id INTEGER NOT NULL,
	
	CONSTRAINT fk_parent_page 
		FOREIGN KEY ( parent_id ) 
		REFERENCES pages ( id )
		ON DELETE CASCADE,
	
	CONSTRAINT fk_child_page 
		FOREIGN KEY ( child_id ) 
		REFERENCES pages ( id )
		ON DELETE CASCADE
);-- --
CREATE UNIQUE INDEX idx_page_tree ON page_tree ( parent_id, child_id );-- --

CREATE TABLE block_tree (
	parent_id INTEGER NOT NULL,
	child_id INTEGER NOT NULL,
	
	CONSTRAINT fk_parent_block 
		FOREIGN KEY ( parent_id ) 
		REFERENCES page_blocks ( id )
		ON DELETE CASCADE,
	
	CONSTRAINT fk_child_block 
		FOREIGN KEY ( child_id ) 
		REFERENCES page_blocks ( id )
		ON DELETE CASCADE
);-- --
CREATE UNIQUE INDEX idx_page_block_tree ON block_tree ( parent_id, child_id );-- --

CREATE TABLE memo_tree (
	parent_id INTEGER NOT NULL,
	child_id INTEGER NOT NULL,
	
	CONSTRAINT fk_parent_memo 
		FOREIGN KEY ( parent_id ) 
		REFERENCES memos ( id )
		ON DELETE CASCADE,
	
	CONSTRAINT fk_child_memo 
		FOREIGN KEY ( child_id ) 
		REFERENCES memos ( id )
		ON DELETE CASCADE
);-- --
CREATE UNIQUE INDEX idx_page_memo_tree ON memo_tree ( parent_id, child_id );-- --


-- Uploaded media
CREATE TABLE resources (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	content TEXT NOT NULL DEFAULT '{ "src" : "" }' COLLATE NOCASE, 
	src TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( content, '$.src' ), '' )
	) STORED NOT NULL,
	mime_type TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( content, '$.mime_type' ), '' )
	) STORED NOT NULL,
	thumbnail TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( content, '$.thumbnail' ), '' )
	) STORED NOT NULL,
	file_size INTEGER GENERATED ALWAYS AS (
		CAST( COALESCE( json_extract( content, '$.file_size' ), 0 ) AS INTEGER )
	) STORED NOT NULL,
	
	-- hash_file( 'sha256', src ) 
	file_hash TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( content, '$.file_hash' ), '' )
	) STORED NOT NULL,
	
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	status INTEGER NOT NULL DEFAULT 0
);-- --
CREATE UNIQUE INDEX idx_resource_src ON resources ( src )
	WHERE src IS NOT "";-- --
CREATE INDEX idx_resource_mime ON resources ( mime_type )
	WHERE mime_type IS NOT "";-- --
CREATE UNIQUE INDEX idx_resource_hash ON resources ( file_hash )
	WHERE file_hash IS NOT "";-- --
CREATE INDEX idx_resource_created ON resources ( created );-- --
CREATE INDEX idx_resource_status ON resources ( status );-- --

CREATE TRIGGER resource_insert AFTER INSERT ON resources FOR EACH ROW 
BEGIN
	-- Clean empty files
	DELETE FROM resources WHERE src IS "";
END;-- --

CREATE TABLE resource_captions (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	resource_id INTEGER NOT NULL,
	lang_id INTEGER DEFAULT NULL,
	content TEXT NOT NULL DEFAULT '{ "body" : "" }' COLLATE NOCASE,
	
	-- Description, subtitles etc...
	label TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( content, '$.label' ), 'description' )
	) STORED NOT NULL,
	body TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( content, '$.body' ), '' )
	) STORED NOT NULL,
	
	CONSTRAINT fk_caption_resource 
		FOREIGN KEY ( resource_id ) 
		REFERENCES resources ( id )
		ON DELETE CASCADE,
		
	CONSTRAINT fk_caption_lang 
		FOREIGN KEY ( lang_id ) 
		REFERENCES languages ( id )
		ON DELETE RESTRICT
);-- --
CREATE INDEX idx_resource_label ON resource_captions ( label );-- --
CREATE INDEX idx_resource_caption ON resource_captions ( resource_id );-- --
CREATE INDEX idx_resource_lang ON resource_captions ( lang_id )
	WHERE lang_id IS NOT NULL;-- --

CREATE TRIGGER resource_caption_insert AFTER INSERT ON resource_captions FOR EACH ROW
WHEN NEW.body IS NOT ""
BEGIN
	UPDATE resource_captions SET lang_id = (
			SELECT COALESCE( id, NULL ) FROM languages 
				WHERE iso_code = 
					json_extract( NEW.content, '$.lang' )
			LIMIT 1
		) WHERE id = NEW.id AND 
			json_extract( NEW.content, '$.lang' ) IS NOT "";
END;-- --

CREATE TRIGGER resource_caption_update AFTER UPDATE ON resource_captions FOR EACH ROW
WHEN NEW.body IS NOT ""
BEGIN 
	UPDATE resource_captions SET lang_id = (
			SELECT COALESCE( id, NULL ) FROM languages 
				WHERE iso_code = 
					json_extract( NEW.content, '$.lang' )
			LIMIT 1
		) WHERE id = NEW.id AND 
			json_extract( NEW.content, '$.lang' ) IS NOT "";
END;-- --

CREATE TABLE resource_users (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	content TEXT NOT NULL DEFAULT '{ "label" : "" }' COLLATE NOCASE, 
	
	-- Uploader, Onwer, Editor etc...
	label TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( content, '$.label' ), 'owner' )
	) STORED NOT NULL,
	user_id INTEGER NOT NULL,
	resource_id INTEGER NOT NULL,
	
	CONSTRAINT fk_resource_user 
		FOREIGN KEY ( user_id ) 
		REFERENCES users ( id )
		ON DELETE CASCADE,
	
	CONSTRAINT fk_user_resource 
		FOREIGN KEY ( resource_id ) 
		REFERENCES resources ( id )
		ON DELETE CASCADE
);-- --
CREATE UNIQUE INDEX idx_resource_user ON resource_users ( user_id, resource_id );-- --
CREATE INDEX idx_resource_user_label ON resource_users ( label );-- --


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
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	content TEXT NOT NULL DEFAULT '{ "label" : "action" }' COLLATE NOCASE,
	user_id INTEGER NOT NULL,
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	
	label TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( content, '$.label' ), 'action' )
	) STORED NOT NULL,
	
	CONSTRAINT fk_history_user 
		FOREIGN KEY ( user_id ) 
		REFERENCES users ( id )
		ON DELETE RESTRICT
);-- --
CREATE INDEX idx_history_user ON history ( user_id );-- --
CREATE INDEX idx_history_label ON history ( label );-- --
CREATE INDEX idx_history_created ON history ( created );-- --


-- Function execution
CREATE TABLE operations (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	label TEXT NOT NULL COLLATE NOCASE,
	pattern TEXT NOT NULL DEFAULT "" COLLATE NOCASE,
	settings TEXT NOT NULL DEFAULT '{ "realm" : "" }' COLLATE NOCASE,
	realm TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( settings, '$.realm' ), '/' )
	) STORED NOT NULL,
	
	-- Pattern match event
	event TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( settings, '$.event' ), 'operation' )
	) STORED NOT NULL,
	
	-- E.G. global, document, page, block, memo, user, role
	op_scope TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( settings, '$.scope' ), 'global' )
	) STORED NOT NULL,
	method TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( settings, '$.method' ), 'get' )
	) STORED NOT NULL
);-- --
CREATE UNIQUE INDEX idx_operation ON operations ( label, pattern, op_scope );-- --
CREATE UNIQUE INDEX idx_operation_event ON operations ( event )
	WHERE event IS NOT "operation";-- --
CREATE INDEX idx_operation_realm ON configs ( realm ) WHERE realm IS NOT "";-- --
CREATE INDEX idx_operation_method ON operations ( method );-- --


-- Search result history
CREATE TABLE search_cache(
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	
	-- Serialized JSON
	settings TEXT NOT NULL DEFAULT 
		'{ 
			"label" : "",
			"terms" : "",
			"results" : [], 
			"total" : 0 
		}' COLLATE NOCASE,
	label TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( settings, '$.label' ), '' )
	) STORED NOT NULL,
	terms TEXT GENERATED ALWAYS AS ( 
		COALESCE( json_extract( settings, '$.terms' ), '' )
	) STORED NOT NULL,
	
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	expires DATETIME DEFAULT NULL
);-- --
CREATE UNIQUE INDEX idx_search_scope ON search_cache( label, terms )
	WHERE label IS NOT "" AND terms IS NOT "";-- --
CREATE INDEX idx_search_terms ON search_cache( terms );-- --
CREATE INDEX idx_search_expires ON search_cache( expires )
	WHERE expires IS NOT NULL;-- --

-- Set default search expiration to 1 hour
CREATE TRIGGER search_exp_after_insert AFTER INSERT ON search_cache FOR EACH ROW 
WHEN NEW.expires IS NULL
BEGIN
	UPDATE search_cache SET updated = CURRENT_TIMESTAMP, 
		expires = datetime( 
			( strftime( '%s','now' ) + 3600 ), 
			'unixepoch' 
		) WHERE rowid = NEW.rowid;
END;-- --

CREATE TRIGGER search_after_insert AFTER INSERT ON search_cache FOR EACH ROW 
BEGIN
	-- Remove expired searches
	DELETE FROM search_cache WHERE expires IS NOT NULL 
		AND (
			strftime( '%s', expires ) < 
			strftime( '%s', 'now' ) 
		);
	
	-- Empty searches
	DELETE FROM search_cache WHERE terms IS "";
END;-- --

