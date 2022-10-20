
-- Generate a random unique string
-- Usage:
-- SELECT id FROM rnd;
CREATE VIEW rnd AS 
SELECT lower( hex( randomblob( 16 ) ) ) AS id;-- --


CREATE TABLE users (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	username TEXT NOT NULL COLLATE NOCASE,
	password TEXT NOT NULL,
	
	-- Normalized, lowercase, and stripped of spaces
	user_clean TEXT NOT NULL COLLATE NOCASE,
	
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
CREATE INDEX idx_user_created ON users ( created );-- --
CREATE INDEX idx_user_updated ON users ( updated );-- --
CREATE INDEX idx_user_settings ON users ( setting_id ) 
	WHERE setting_id IS NOT "";-- --
CREATE INDEX idx_user_status ON users ( status );-- --


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

-- Login view
CREATE VIEW login_view AS SELECT 
	logins.user_id AS id,
	logins.lookup AS lookup, 
	logins.hash AS hash, 
	logins.updated AS updated, 
	users.status AS status, 
	users.username AS name, 
	users.password AS password
	
	FROM logins
	JOIN users ON logins.user_id = users.id;-- --


-- Login regenerate
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
	-- New login lookup
	INSERT INTO logins( user_id, lookup )
		VALUES( NEW.id, ( SELECT id FROM rnd ) );
END;-- --

-- Update last modified
CREATE TRIGGER user_update AFTER UPDATE ON users FOR EACH ROW
BEGIN
	UPDATE users SET updated = CURRENT_TIMESTAMP 
		WHERE id = OLD.id;
END;-- --

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
	type_id INTEGER NOT NULL,
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
		ON DELETE CASCADE
);-- --
CREATE INDEX idx_doc_type ON documents ( type_id );-- --
CREATE INDEX idx_doc_created ON documents ( created );-- --
CREATE INDEX idx_doc_updated ON documents ( updated );-- --
CREATE INDEX idx_doc_settings ON users ( setting_id ) 
	WHERE setting_id IS NOT "";-- --
CREATE INDEX idx_doc_status ON documents ( status );-- --


-- Update last modified
CREATE TRIGGER document_update AFTER UPDATE ON documents FOR EACH ROW
BEGIN
	UPDATE documents SET updated = CURRENT_TIMESTAMP 
		WHERE id = OLD.id;
END;-- --

-- Content segments
CREATE TABLE pages (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	document_id INTEGER NOT NULL,
	sort_order INTEGER NOT NULL DEFAULT 0,
	
	CONSTRAINT fk_document_page 
		FOREIGN KEY ( document_id ) 
		REFERENCES documents ( id )
		ON DELETE CASCADE
);-- --
CREATE INDEX idx_page_document ON pages ( document_id );-- --
CREATE INDEX idx_page_sort ON pages ( sort_order );-- --

-- Content breakpoints
CREATE TABLE page_lines (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	content TEXT NOT NULL DEFAULT '{ "body" : "" }' COLLATE NOCASE, 
	page_id INTEGER NOT NULL,
	sort_order INTEGER NOT NULL DEFAULT 0,
	
	CONSTRAINT fk_line_page 
		FOREIGN KEY ( page_id ) 
		REFERENCES pages ( id )
		ON DELETE CASCADE
);-- --
CREATE INDEX idx_line_page ON page_lines ( page_id );-- --
CREATE INDEX idx_line_sort ON page_lines ( sort_order );-- --

-- Content searching
CREATE VIRTUAL TABLE line_search 
	USING fts4( body, tokenize=unicode61 );-- --

CREATE TRIGGER line_insert AFTER INSERT ON page_lines FOR EACH ROW 
WHEN NEW.content IS NOT ""
BEGIN
	-- Create search data
	INSERT INTO line_search( docid, body ) 
		VALUES ( 
			NEW.id, 
			json_extract( NEW.content, '$.body' )
		);
	
END;-- --

CREATE TRIGGER line_update AFTER INSERT ON page_lines FOR EACH ROW 
WHEN NEW.content IS NOT ""
BEGIN
	REPLACE INTO line_search( docid, body ) 
		VALUES ( 
			NEW.id, 
			json_extract( NEW.content, '$.body' )
		);
	
END;-- --

CREATE TRIGGER line_clear AFTER UPDATE ON page_lines FOR EACH ROW 
WHEN NEW.content IS ""
BEGIN
	DELETE FROM line_search WHERE docid = NEW.id;
END;-- --

CREATE TRIGGER line_delete BEFORE DELETE ON page_lines FOR EACH ROW 
BEGIN
	DELETE FROM memo_search WHERE docid = OLD.id;
END;-- --


-- Special labels
CREATE TABLE memos (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	content TEXT NOT NULL DEFAULT '{ "body" : "" }' COLLATE NOCASE, 
	
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);-- --
CREATE INDEX idx_memo_created ON memos ( created );-- --
CREATE INDEX idx_memo_updated ON memos ( updated );-- --

-- Memo searching
CREATE VIRTUAL TABLE memo_search 
	USING fts4( body, tokenize=unicode61 );-- --

CREATE TRIGGER memo_insert AFTER INSERT ON memos FOR EACH ROW 
WHEN NEW.content IS NOT ""
BEGIN
	-- Create search data
	INSERT INTO memo_search( docid, body ) 
		VALUES ( 
			NEW.id, 
			json_extract( NEW.content, '$.body' )
		);
	
END;-- --

CREATE TRIGGER memo_update AFTER UPDATE ON memos FOR EACH ROW 
WHEN NEW.content IS NOT ""
BEGIN
	REPLACE INTO memo_search( docid, body ) 
		VALUES ( 
			NEW.id, 
			json_extract( NEW.content, '$.body' )
		);
	
	UPDATE memos SET updated = CURRENT_TIMESTAMP 
		WHERE id = NEW.id;
END;-- --

CREATE TRIGGER memo_clear AFTER UPDATE ON memos FOR EACH ROW 
WHEN NEW.content IS ""
BEGIN
	DELETE FROM memo_search WHERE docid = NEW.id;
	DELETE FROM memos WHERE id = NEW.id;
END;-- --

CREATE TRIGGER memo_delete BEFORE DELETE ON memos FOR EACH ROW 
BEGIN
	DELETE FROM memo_search WHERE docid = OLD.id;
END;-- --


CREATE TABLE memo_lines (
	memo_id INTEGER NOT NULL,
	line_id INTEGER NOT NULL,
	begin_range INTEGER NOT NULL DEFAULT 0,
	end_range INTEGER NOT NULL DEFAULT 0,
	
	CONSTRAINT fk_line_memo  
		FOREIGN KEY ( memo_id ) 
		REFERENCES memos ( id )
		ON DELETE CASCADE,
	
	CONSTRAINT fk_memo_line
		FOREIGN KEY ( line_id ) 
		REFERENCES lines ( id )
		ON DELETE CASCADE
);-- --
CREATE INDEX idx_memo_line ON memo_lines ( memo_id, line_id );-- --
CREATE UNIQUE INDEX idx_memo_line_range ON memo_lines 
	( memo_id, line_id, begin_range, end_range )
	WHERE begin_range IS NOT 0 AND end_range IS NOT 0;-- --


-- Remove orphaned memo associations
CREATE TRIGGER memo_line_update AFTER UPDATE ON memo_lines FOR EACH ROW
BEGIN
	DELETE FROM memo_lines WHERE begin_range = end_range;
END;-- --

-- Authorship
CREATE TABLE user_documents (
	user_id, INTEGER NOT NULL,
	document_id INTEGER NOT NULL,
	
	CONSTRAINT fk_document_user 
		FOREIGN KEY ( user_id ) 
		REFERENCES users ( id )
		ON DELETE CASCADE,
	
	CONSTRAINT fk_user_document 
		FOREIGN KEY ( document_id ) 
		REFERENCES documents ( id )
		ON DELETE CASCADE
);-- --
CREATE UNIQUE INDEX idx_user_document 
	ON user_documents ( user_id, document_id );-- --

CREATE TABLE user_pages (
	user_id, INTEGER NOT NULL,
	page_id INTEGER NOT NULL,
	
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

CREATE TABLE user_memos (
	user_id, INTEGER NOT NULL,
	memo_id INTEGER NOT NULL,
	
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



