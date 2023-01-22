
-- GUID/UUID generator helper
CREATE VIEW uuid AS SELECT lower(
	hex( randomblob( 4 ) ) || '-' || 
	hex( randomblob( 2 ) ) || '-' || 
	'4' || substr( hex( randomblob( 2 ) ), 2 ) || '-' || 
	substr( 'AB89', 1 + ( abs( random() ) % 4 ) , 1 )  ||
	substr( hex( randomblob( 2 ) ), 2 ) || '-' || 
	hex( randomblob( 6 ) )
) AS id;-- --

-- Service documents
CREATE TABLE services(
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	uuid TEXT DEFAULT NULL,
	settings TEXT NOT NULL DEFAULT '{ "realm" : "" }' COLLATE NOCASE,
	realm TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( settings, '$.realm' ), "" )
	) STORED NOT NULL
);-- --
CREATE INDEX idx_service_uuid ON services ( uuid ) 
	WHERE uuid IS NOT NULL;-- --
CREATE INDEX idx_service_realm ON services ( realm );-- --

CREATE TABLE service_stats(
	service_id INTEGER NOT NULL,
	workspace_count INTEGER NOT NULL DEFAULT 0,
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
	
	CONSTRAINT fk_service_stats
		FOREIGN KEY ( service_id ) 
		REFERENCES services ( id )
		ON DELETE CASCADE
);-- --
CREATE INDEX idx_service_stats ON service_stats ( service_id );-- --
CREATE INDEX idx_service_created ON service_stats ( created );-- --
CREATE INDEX idx_service_updated ON service_stats ( updated );-- --

CREATE TRIGGER service_insert AFTER INSERT ON services FOR EACH ROW 
BEGIN
	INSERT INTO service_stats ( service_id ) VALUES ( NEW.id );
	
	UPDATE services SET uuid = ( SELECT id FROM uuid )
		WHERE id = NEW.id;
END;-- --

CREATE TRIGGER service_update_date BEFORE UPDATE ON services FOR EACH ROW
WHEN OLD.uuid IS NOT NULL
BEGIN
	UPDATE service_stats SET updated = CURRENT_TIMESTAMP 
		WHERE id = NEW.id;
END;-- --

-- Areas
CREATE TABLE workspaces(
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	uuid TEXT DEFAULT NULL,
	settings TEXT NOT NULL DEFAULT '{ "title" : "" }' COLLATE NOCASE,
	title TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( settings, '$.title' ), "" )
	) STORED NOT NULL,
	service_id INTEGER GENERATED ALWAYS AS ( 
		CAST( COALESCE( json_extract( settings, '$.service_id' 
		), NULL ) AS INTEGER )
	) STORED, 
	
	CONSTRAINT fk_workspace_service
		FOREIGN KEY ( service_id ) 
		REFERENCES services ( id )
		ON DELETE CASCADE
);-- --
CREATE INDEX idx_workspace_uuid ON workspaces ( uuid ) 
	WHERE uuid IS NOT NULL;-- --
CREATE INDEX idx_workspace_title ON workspaces ( title );-- --
CREATE INDEX idx_workspace_service ON workspaces ( service_id ) 
	WHERE service_id IS NOT NULL;-- --

CREATE TABLE workspace_stats(
	workspace_id INTEGER NOT NULL,
	collection_count INTEGER NOT NULL DEFAULT 0,
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
	
	CONSTRAINT fk_workspace_stats
		FOREIGN KEY ( workspace_id ) 
		REFERENCES workspaces ( id )
		ON DELETE CASCADE
);-- --
CREATE INDEX idx_workspace_stats ON workspace_stats ( workspace_id );-- --
CREATE INDEX idx_workspace_created ON workspace_stats ( created );-- --
CREATE INDEX idx_workspace_updated ON workspace_stats ( updated );-- --

CREATE TRIGGER workspace_insert AFTER INSERT ON workspaces FOR EACH ROW 
BEGIN
	INSERT INTO workspace_stats ( workspace_id ) VALUES ( NEW.id );
	
	UPDATE service_stats SET workspace_count = ( workspace_count + 1 ) 
		WHERE service_id = NEW.service_id;
		
	UPDATE workspaces SET uuid = ( SELECT id FROM uuid )
		WHERE id = NEW.id;
END;-- --

CREATE TRIGGER workspace_delete BEFORE DELETE ON workspaces FOR EACH ROW
BEGIN
	UPDATE service_stats SET workspace_count = ( workspace_count - 1 ) 
		WHERE service_id = OLD.service_id;
END;-- --

CREATE TRIGGER workspace_update_date BEFORE UPDATE ON workspaces FOR EACH ROW
WHEN OLD.uuid IS NOT NULL
BEGIN
	UPDATE workspace_stats SET updated = CURRENT_TIMESTAMP 
		WHERE id = NEW.id;
END;-- --



CREATE TABLE collections(
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	uuid TEXT DEFAULT NULL,
	settings TEXT NOT NULL DEFAULT '{ "title" : "" }' COLLATE NOCASE,
	title TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( settings, '$.title' ), "" )
	) STORED NOT NULL,
	workspace_id INTEGER GENERATED ALWAYS AS ( 
		CAST( COALESCE( json_extract( settings, '$.workspace_id' 
		), NULL ) AS INTEGER )
	) STORED,
	href TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( settings, '$.href' ), "" )
	) STORED NOT NULL,
	
	CONSTRAINT fk_collection_workspace
		FOREIGN KEY ( workspace_id ) 
		REFERENCES workspaces ( id )
		ON DELETE CASCADE
);-- --
CREATE INDEX idx_collection_uuid ON collections ( uuid ) 
	WHERE uuid IS NOT NULL;-- --
CREATE INDEX idx_collection_title ON collections ( title );-- --
CREATE INDEX idx_collection_href ON collections ( href );-- --
CREATE INDEX idx_collection_workspace ON collections ( workspace_id ) 
	WHERE workspace_id IS NOT NULL;-- --

CREATE TABLE collection_stats(
	collection_id INTEGER NOT NULL,
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
	
	CONSTRAINT fk_collection_stats
		FOREIGN KEY ( collection_id ) 
		REFERENCES collections ( id )
		ON DELETE CASCADE
);-- --
CREATE INDEX idx_collection_stats ON collection_stats ( collection_id );-- --
CREATE INDEX idx_collection_created ON collection_stats ( created );-- --
CREATE INDEX idx_collection_updated ON collection_stats ( updated );-- --

CREATE TABLE collection_accept(
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	term TEXT NOT NULL,
	collection_id INTEGER NOT NULL,
	
	CONSTRAINT fk_accept_collection
		FOREIGN KEY ( collection_id ) 
		REFERENCES collections ( id )
		ON DELETE CASCADE
);-- --
CREATE INDEX idx_accept_collection ON collection_accept ( collection_id ) 
	WHERE collection_id IS NOT NULL;-- --
CREATE INDEX idx_accept_term ON collection_accept( term );-- --

CREATE TRIGGER collection_insert AFTER INSERT ON collections FOR EACH ROW 
BEGIN
	INSERT INTO collection_accept( collection_id, title ) 
	SELECT 
		NEW.id, 
		COALESCE( json_extract( value, '$.accept' ), "" )
	FROM json_each( NEW.settings, '$.accept' );
	
	UPDATE workspace_stats SET collection_count = ( collection_count + 1 ) 
		WHERE workspace_id = NEW.workspace_id;
		
	INSERT INTO collection_stats ( collection_id ) VALUES ( NEW.id );
	
	UPDATE collections SET uuid = ( SELECT id FROM uuid )
		WHERE id = NEW.id;
END;-- --

CREATE TRIGGER collection_update AFTER UPDATE ON collections FOR EACH ROW
BEGIN
	DELETE FROM collection_accept 
		WHERE collection_id = NEW.id;
	
	REPLACE INTO collection_accept( collection_id, title ) 
	SELECT 
		NEW.id, 
		COALESCE( json_extract( value, '$.accept' ), "" )
	FROM json_each( NEW.settings, '$.accept' ) accept;
END;-- --

CREATE TRIGGER collection_delete BEFORE DELETE ON collections FOR EACH ROW
BEGIN
	UPDATE workspace_stats SET collection_count = ( collection_count - 1 ) 
		WHERE workspace_id = OLD.workspace_id;
END;-- --

CREATE TRIGGER collection_update_date BEFORE UPDATE ON collections FOR EACH ROW
WHEN OLD.uuid IS NOT NULL
BEGIN
	UPDATE collection_stats SET updated = CURRENT_TIMESTAMP 
		WHERE id = NEW.id;
END;-- --



CREATE TABLE categories(
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	uuid TEXT DEFAULT NULL,
	settings TEXT NOT NULL DEFAULT '{ "term" : "" }' COLLATE NOCASE,
	term TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( settings, '$.term' ), "" )
	) STORED NOT NULL,
	scheme TEXT GENERATED ALWAYS AS (
		json_extract( settings, '$.scheme' )
	) STORED
);-- --
CREATE INDEX idx_category_uuid ON categories ( uuid ) 
	WHERE uuid IS NOT NULL;-- --
CREATE INDEX idx_category_term ON categories ( term );-- --
CREATE INDEX idx_category_scheme ON categories ( scheme ) 
	WHERE scheme IS NOT NULL;-- --

CREATE TRIGGER category_insert AFTER INSERT ON categories FOR EACH ROW 
BEGIN
	UPDATE categories SET uuid = ( SELECT id FROM uuid )
		WHERE id = NEW.id;
END;-- --

CREATE TABLE collection_categories(
	collection_id INTEGER NOT NULL,
	category_id INTEGER NOT NULL,
	is_fixed INTEGER DEFAULT 0,
	
	CONSTRAINT fk_category_collection
		FOREIGN KEY ( collection_id ) 
		REFERENCES collections ( id )
		ON DELETE CASCADE
		
	CONSTRAINT fk_collection_category
		FOREIGN KEY ( category_id ) 
		REFERENCES category ( id )
		ON DELETE CASCADE
);-- --
CREATE UNIQUE INDEX idx_collection_category 
	ON collection_categories ( collection_id, category_id );-- --


CREATE TABLE entries (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	uuid TEXT DEFAULT NULL,
	content TEXT NOT NULL DEFAULT '{ "title" : "" }' COLLATE NOCASE,
	title TEXT GENERATED ALWAYS AS (
		COALESCE( json_extract( content, '$.title' ), "" )
	) STORED NOT NULL,
	summary TEXT GENERATED ALWAYS AS ( 
		json_extract( content, '$.summary' )
	) STORED
);-- --
CREATE INDEX idx_entry_uuid ON entries ( uuid ) 
	WHERE uuid IS NOT NULL;-- --


CREATE TABLE entry_stats(
	entry_id INTEGER NOT NULL,
	published DATETIME DEFAULT NULL,
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
	
	CONSTRAINT fk_entry_stats
		FOREIGN KEY ( entry_id ) 
		REFERENCES entries ( id )
		ON DELETE CASCADE
);-- --
CREATE INDEX idx_entry_stats ON entry_stats ( entry_id );-- --
CREATE INDEX idx_entry_pub ON entry_stats ( published ) 
	WHERE published IS NOT NULL;-- --
CREATE INDEX idx_entry_created ON entry_stats ( created );-- --
CREATE INDEX idx_entry_updated ON entry_stats ( updated );-- --

CREATE TABLE entry_content(
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	entry_id INTEGER NOT NULL,
	content_type TEXT DEFAULT NULL,
	src TEXT DEFAULT NULL,
	body TEXT DEFAULT NULL,
	lang TEXT DEFAULT NULL,
	
	CONSTRAINT fk_content_entry
		FOREIGN KEY ( entry_id ) 
		REFERENCES entries ( id )
		ON DELETE CASCADE
);-- --
CREATE UNIQUE INDEX idx_content_type 
	ON entry_content ( entry_id, content_type )
	WHERE content_type IS NOT NULL;-- --
CREATE UNIQUE INDEX idx_content_lsrc 
	ON entry_content ( entry_id, src )
	WHERE src IS NOT NULL;-- --
CREATE INDEX idx_content_src ON entry_content ( src ) 
	WHERE src IS NOT NULL;-- --

-- Entry function and reference links
CREATE TABLE links(
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	entry_id INTEGER NOT NULL,
	rel TEXT NOT NULL DEFAULT "",
	href TEXT DEFAULT NULL,
	link_type TEXT DEFAULT NULL,
	
	CONSTRAINT fk_link_entry
		FOREIGN KEY ( entry_id ) 
		REFERENCES entries ( id )
		ON DELETE CASCADE
);-- --
CREATE UNIQUE INDEX idx_link_href_rel ON links ( entry_id, rel, href )
	WHERE rel IS NOT "" AND href IS NOT NULL;-- --
CREATE INDEX idx_link_entry ON links ( entry_id );-- --
CREATE INDEX idx_link_rel ON links ( rel ) WHERE rel IS NOT NULL;-- --
CREATE INDEX idx_link_type ON links ( link_type ) 
	WHERE link_type IS NOT NULL;-- --
CREATE INDEX idx_link_href ON links ( href ) WHERE href IS NOT "";-- --


CREATE TRIGGER entry_insert AFTER INSERT ON entries FOR EACH ROW
BEGIN
	-- TODO: Fix column error
	-- UPDATE entries SET uuid = ( SELECT id FROM uuid ) 
	--	WHERE id = NEW.id;
	
	-- Create stats
	INSERT INTO entry_stats ( entry_id ) VALUES ( NEW.id );
	
	-- Insert links if any
	REPLACE INTO links( entry_id, rel, href, link_type ) 
	SELECT 
		NEW.id,
		COALESCE( json_extract( value, '$.rel' ), "" ), 
		COALESCE( json_extract( value, '$.href' ), NULL ), 
		COALESCE( json_extract( value, '$.type' ), NULL )
	FROM json_each( json_extract( NEW.content, '$.links' ) );
	
	-- Insert content if any
	INSERT INTO entry_content( entry_id, content_type, src, body, lang ) 
	SELECT 
		NEW.id,
		COALESCE( json_extract( value, '$.content_type' ), "text/plain" ),
		COALESCE( json_extract( value, '$.src' ), NULL ),
		COALESCE( json_extract( value, '$.body' ), NULL ),
		COALESCE( json_extract( value, '$.lang' ), NULL )
	FROM json_each( NEW.content, '$.content' );
END;-- --

CREATE TRIGGER entry_update_content BEFORE UPDATE ON entries FOR EACH ROW
BEGIN 
	REPLACE INTO links( entry_id, rel, href, link_type ) 
	SELECT DISTINCT
		NEW.id, 
		COALESCE( json_extract( value, '$.rel' ), "" ), 
		COALESCE( json_extract( value, '$.href' ), NULL ), 
		COALESCE( json_extract( value, '$.type' ), NULL ) 
	FROM json_each( json_extract( NEW.content, '$.links' ) );
	
	REPLACE INTO entry_content( entry_id, content_type, src, body, lang ) 
	SELECT
		NEW.id,
		COALESCE( json_extract( value, '$.content_type' ), "text/plain" ),
		COALESCE( json_extract( value, '$.src' ), NULL ),
		COALESCE( json_extract( value, '$.body' ), NULL ),
		COALESCE( json_extract( value, '$.lang' ), NULL )
	FROM json_each( NEW.content, '$.content' );
END;-- --

CREATE TRIGGER entry_update_date BEFORE UPDATE ON entries FOR EACH ROW
WHEN OLD.uuid IS NOT NULL
BEGIN
	UPDATE entry_stats SET updated = CURRENT_TIMESTAMP 
		WHERE id = NEW.id;
END;-- --



CREATE TABLE authors(
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	uuid TEXT DEFAULT NULL,
	name TEXT NOT NULL
);-- --
CREATE INDEX idx_author_uuid ON authors ( uuid ) 
	WHERE uuid IS NOT NULL;-- --
CREATE UNIQUE INDEX idx_author_name ON authors ( name );-- --

CREATE TABLE author_stats(
	author_id INTEGER NOT NULL,
	last_entry_id INTEGER DEFAULT NULL,
	last_published DATETIME DEFAULT NULL,
	entry_count INTEGER NOT NULL DEFAULT 0,
	created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	
	CONSTRAINT fk_author_stats
		FOREIGN KEY ( author_id ) 
		REFERENCES authors ( id )
		ON DELETE CASCADE
);-- --
CREATE INDEX idx_author_stats ON author_stats ( author_id );-- --
CREATE INDEX idx_author_last_entry ON author_stats ( last_entry_id )
	WHERE last_entry_id IS NOT NULL;-- --
CREATE INDEX idx_author_published ON author_stats ( last_published )
	WHERE last_published IS NOT NULL;-- --
CREATE INDEX idx_author_created ON author_stats ( created );-- --
CREATE INDEX idx_author_updated ON author_stats ( updated );-- --

CREATE TABLE entry_authors(
	entry_id INTEGER NOT NULL,
	author_id INTEGER NOT NULL,
	
	CONSTRAINT fk_author_entry
		FOREIGN KEY ( entry_id ) 
		REFERENCES entries ( id )
		ON DELETE CASCADE
		
	CONSTRAINT fk_entry_author
		FOREIGN KEY ( author_id ) 
		REFERENCES authors ( id )
		ON DELETE CASCADE
);-- --
CREATE UNIQUE INDEX idx_entry_author ON 
	entry_authors( entry_id, author_id );-- --


CREATE TRIGGER author_insert AFTER INSERT ON authors FOR EACH ROW
BEGIN
	INSERT INTO author_stats ( author_id ) VALUES ( NEW.id );
	
	UPDATE authors SET uuid = ( SELECT id FROM uuid )
		WHERE id = NEW.id;
END;-- --

CREATE TRIGGER author_update_date BEFORE UPDATE ON authors FOR EACH ROW
WHEN OLD.uuid IS NOT NULL
BEGIN
	UPDATE author_stats SET updated = CURRENT_TIMESTAMP 
		WHERE author_id = NEW.id;
END;-- --


