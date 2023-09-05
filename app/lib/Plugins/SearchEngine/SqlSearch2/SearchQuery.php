<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/SqlSearch2/SearchQuery.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2011 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * @package CollectiveAccess
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 * Class SearchQuery
 *
 * This class represents a search query and provides methods to execute the query and retrieve the results.
 */
class SearchQuery implements Iterator, Countable {

	/**
	 * @var string The query to run in the database. Default to an empty query for cases where the search term has been
	 *      stripped.
	 */
	protected string $query = "SELECT 1 row_id,1 boost WHERE FALSE";
	protected array $arguments = [];
	protected Db $db;
	/**
	 * @var mixed
	 */
	private array $tempTables = [];

	/**
	 * @var mixed The handle variable
	 */
	private DbResult $handle;
	/**
	 * @var mixed
	 */
	private int $currentAndIndex = 0;

	/**
	 * Class constructor.
	 *
	 * @param Db $db The database object to be used.
	 */
	public function __construct( Db $db ) {
		$this->db = $db;
	}

	/**
	 * Destroys the object and performs necessary cleanup actions.
	 *
	 * @return void
	 */
	public function __destruct() {
		$this->dropTemporaryTables();
	}

	/**
	 * Add an argument to the search query.
	 *
	 * @param mixed $argument The argument to be added.
	 *
	 * @return SearchQuery Returns the updated SearchQuery object.
	 */
	public function addArgument( $argument ): SearchQuery {
		$this->arguments[] = $argument;

		return $this;
	}

	/**
	 * Set the arguments for the search query.
	 *
	 * @param array $arguments An array of arguments for the search query.
	 *
	 * @return SearchQuery Returns the current SearchQuery instance.
	 */
	public function setArguments( array $arguments ): SearchQuery {
		$this->arguments = $arguments;

		return $this;
	}

	/**
	 * Retrieves the number of records in the result set.
	 *
	 * @return int The number of records in the result set.
	 */
	public function getNumRecords(): int {
		return $this->getHandle()->numRows();
	}

	/**
	 * Sets the database connection for the SearchQuery object.
	 *
	 * @param Db $db The database connection to set.
	 *
	 * @return SearchQuery Returns the SearchQuery object.
	 */
	public function setDb( Db $db ): SearchQuery {
		$this->db = $db;

		return $this;
	}


	/**
	 * Returns the database instance used by the object.
	 *
	 * @return Db The database instance used by the object.
	 */
	public function getDb(): Db {
		return $this->db;
	}

	/**
	 * Returns the array of arguments of the current object.
	 *
	 * @return array The array of arguments.
	 */
	public function getArguments(): array {
		return $this->arguments;
	}

	/**
	 * Set the search query.
	 *
	 * @param string $query The search query to be set.
	 *
	 * @return SearchQuery The SearchQuery object.
	 */
	public function setQuery( string $query ): SearchQuery {
		$this->query = $query;

		return $this;
	}

	/**
	 * Gets the query string.
	 *
	 * @return string The query string.
	 */
	public function getQuery(): string {
		return $this->query;
	}

	/**
	 * Gets the temporary tables.
	 *
	 * @return array The array of temporary tables.
	 */
	public function getTempTables(): array {
		return $this->tempTables;
	}

	/**
	 * @throws SearchException
	 */
	public function createTemporaryTable( string $name ): bool {
		$this->getDb()->query( "DROP TEMPORARY TABLE IF EXISTS `$name`" );
		$this->getDb()->query( "
			CREATE TEMPORARY TABLE `$name` (
				row_id int unsigned not null primary key,
				boost int not null default 1,
				field_container_id int unsigned null,
				INDEX (field_container_id),
				INDEX (boost)
			);
		" );
		if ( $this->db->numErrors() ) {
			throw new SearchException( $this->getDb()->getErrors() );
		}
		$this->tempTables[ $name ] = $name;

		return true;
	}


	/**
	 *
	 */
	public function execute() {
		return $this->getHandle( true );
	}

	/**
	 * Returns the current element in the iterator.
	 *
	 * @return array The current element.
	 */
	public function current() {
		return $this->getHandle()->getRow();
	}

	/**
	 * Moves the internal pointer of the iterator to the next row and returns the value.
	 */
	public function next(): bool {
		return $this->getHandle()->nextRow();
	}

	/**
	 * Returns the current key of the iterator.
	 *
	 * @return mixed The current key of the iterator.
	 */
	public function key() {
		return $this->getHandle()->opn_current_row;
	}

	/**
	 * Checks if the current element of the iterator is valid.
	 *
	 * @return bool Returns true if the current element is valid, false otherwise.
	 */
	public function valid(): bool {
		return $this->key() !== null;
	}

	/**
	 * Rewinds the position of the handle.
	 */
	public function rewind() {
		$this->getHandle()->seek( 0 );
	}

	public function seek( $index ) {
		return $this->getHandle()->seek( $index );
	}

	/**
	 * Returns the number of records.
	 *
	 * @return int The number of records.
	 */
	public function count(): int {
		return $this->getNumRecords();
	}

	/**
	 * Gets the handle for executing the database query.
	 *
	 * This method returns the handle used to execute the database query. If the handle
	 * is not already set, it will be set by calling the `query` method of the associated
	 * database object.
	 *
	 * @return mixed The handle for executing the database query.
	 */
	public function getHandle( bool $refresh = false ) {
		if ( ! isset( $this->handle ) || $refresh ) {
			$this->handle = $this->getDb()->query( $this->getQuery(), $this->getArguments() );
		}

		return $this->handle;
	}


	/**
	 * Drops a temporary table if it exists in the database.
	 *
	 * @param string $table The name of the temporary table to drop.
	 *
	 * @return void
	 */
	public function dropTemporaryTable( $table ): void {
		$this->getDb()->query( "DROP TEMPORARY TABLE IF EXISTS `$table`" );
	}

	/**
	 * Drops all temporary tables.
	 *
	 * @return void
	 */
	public function dropTemporaryTables(): void {
		foreach ( $this->getTempTables() as $table ) {
			$this->dropTemporaryTable( $table );
		}
	}

	public function addAnd( SearchQuery $hits ): SearchQuery {
		if ( ! isset( $this->query ) ) {
			throw new SearchException( "You cannot add an add query to an empty query" );
		}
		$withTableName    = $this->getWithTableName();
		$newWithTableName = $this->getWithTableName();
		$this->query      = <<<AND_QUERY

		WITH $withTableName AS ($this->query),
		$newWithTableName AS ($hits->query)
		SELECT $withTableName.row_id, $withTableName.boost
		FROM
		$withTableName JOIN $newWithTableName ON $withTableName.row_id = $newWithTableName.row_id
		
AND_QUERY;

		return $this->appendArgumentsAndExecute( $hits );
	}

	public function addNot( SearchQuery $hits ): SearchQuery {
		if ( ! isset( $this->query ) ) {
			throw new SearchException( "You cannot add an add query to an empty query" );
		}
		$withTableName    = $this->getWithTableName();
		$newWithTableName = $this->getWithTableName();
		$this->query      = <<<NOT_QUERY
		WITH $withTableName AS ($this->query),
		$newWithTableName AS ($hits->query)
		SELECT $withTableName.row_id, $withTableName.boost
		FROM
		$withTableName LEFT JOIN $newWithTableName ON $withTableName.row_id = $newWithTableName.row_id
		WHERE $newWithTableName.row_id IS NULL
NOT_QUERY;

		return $this->appendArgumentsAndExecute( $hits );
	}

	public function addOr( SearchQuery $hits ): SearchQuery {
		if ( ! isset( $this->query ) ) {
			throw new SearchException( "You cannot add an add query to an empty query" );
		}

		$this->query = <<<OR_QUERY

		SELECT row_id, SUM(boost) AS boost FROM(
			SELECT row_id, boost FROM ($this->query) AS {$this->getWithTableName()}
			UNION
			SELECT row_id, boost FROM ($hits->query) AS {$this->getWithTableName()})
			AS {$this->getWithTableName()}
		GROUP BY row_id
OR_QUERY;

		return $this->appendArgumentsAndExecute( $hits );
	}

	/**
	 * @return string
	 */
	public function getWithTableName(): string {
		$withTableName = "withTable$this->currentAndIndex";
		$this->currentAndIndex ++;

		return $withTableName;
	}

	/**
	 * @param SearchQuery $hits
	 *
	 * @return $this
	 */
	public function appendArgumentsAndExecute( SearchQuery $hits ): SearchQuery {

		$this->addArguments( $hits->getArguments() );

		$this->execute();

		return $this;
	}

	/**
	 * @param array $arguments
	 *
	 * @return void
	 */
	public function addArguments( array $arguments ): void {
		foreach ( $arguments as $argument ) {
			$this->addArgument( $argument );
		}
	}

}
