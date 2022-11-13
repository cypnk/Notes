#!/bin/bash

# Binary destination and code source
BD=bin
SC=src

# Timestamp
DATE=`date +%Y-%m-%d-%H-%M-%S`

# File presets
IF=$BD/notes
OF=$BD/notes.sha1

# Document storage
DB=../data/documents.db
SQ=../data/documents.sql

# Backup of document storage
BK=data-backup-$DATE.sql

# Make bin direcory
mkdir -p $BD

# Make and clean
make && make clean

# Create binary hash
sha1sum $IF > $OF

# Display
echo ''
echo 'Build complete'
echo ''
if [ -f $DB ]; then
	sqlite3 $DB .dump > $BK
	echo 'Database backed up'
else
	sqlite3 $DB < $SQ
	echo 'Database created'
fi
echo ''
cat $OF

exit

