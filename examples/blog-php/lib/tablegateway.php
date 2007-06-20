<?php
class TableGateway
{
  public $table;
  public $pkey;

  protected $connection;
  protected $columns = NULL;

  function __construct($table, $connection) {
    $this->table = $table;
    $this->connection = $connection;
    $this->pkey = $this->getPKey();
  }

  protected function createRecord($row) {
    return new ArrayObject($row, ArrayObject::ARRAY_AS_PROPS | ArrayObject::STD_PROP_LIST);
  }

  function reflect() {
    if (!$this->columns) {
      $this->columns = $this->connection->getTableMeta($this->table);
    }
    return $this->columns;
  }

  function getPKey() {
    foreach ($this->reflect() as $column => $info) {
      if ($info['pk']) {
        return $column;
      }
    }
  }

  function getColumns() {
    return array_keys($this->reflect());
  }

  function fetchByPK($pkey) {
    return $this->fetch(Array($this->pkey => $pkey));
  }

  function deleteByPK($pkey) {
    return $this->delete(Array($this->pkey => $pkey));
  }

  function fetch($condition) {
    $query = "SELECT * FROM ".$this->connection->quoteName($this->table);
    $where = Array();
    foreach ($condition as $column => $value) {
      $where[] = $this->connection->quoteName($column)."=".$this->connection->quote($value);
    }
    if (count($where) == 0) {
      throw new Exception("No conditions given for fetch");
    }
    $query .= " WHERE ".implode(" AND ", $where);
    $result = $this->connection->query($query);
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $resultset = $result->fetchAll();
    if (count($resultset) != 1) {
      return NULL;
    }
    return $this->createRecord($resultset[0]);
  }

  function selectIndex($orderby = NULL, $direction = NULL) {
    $query = "SELECT ".$this->connection->quoteName($this->pkey)." FROM ".$this->connection->quoteName($this->table);
    if (!is_null($orderby)) {
      $query .= " ORDER BY ".$this->connection->quoteName($orderby);
    }
    if (!is_null($direction)) {
      $query .= (strtolower($direction) == 'asc') ? ' ASC ' : ' DESC ';
    }
    $result = $this->connection->query($query);

    $result->setFetchMode(PDO::FETCH_ASSOC);
    $resultset = Array();
    foreach ($result->fetchAll() as $row) {
      $resultset[] = $this->createRecord($row);
    }
    return $resultset;
  }

  function select($orderby = NULL, $direction = NULL) {
    $query = "SELECT * FROM ".$this->connection->quoteName($this->table);
    if (!is_null($orderby)) {
      $query .= " ORDER BY ".$this->connection->quoteName($orderby);
    }
    if (!is_null($direction)) {
      $query .= (strtolower($direction) == 'asc') ? ' ASC ' : ' DESC ';
    }
    $result = $this->connection->query($query);

    $result->setFetchMode(PDO::FETCH_ASSOC);
    $resultset = Array();
    foreach ($result->fetchAll() as $row) {
      $resultset[] = $this->createRecord($row);
    }
    return $resultset;
  }

  function insert($data) {
    $query = "INSERT INTO ".$this->connection->quoteName($this->table);
    $columns = Array();
    $values = Array();
    foreach ($this->getColumns() as $column) {
      if (isset($data[$column])) {
        $columns[] = $this->connection->quoteName($column);
        $values[] = $this->connection->quote($data[$column]);
      }
    }
    $query .= " (".implode(",", $columns).")";
    $query .= " VALUES (".implode(",", $values).")";
    $this->connection->exec($query);
    return TRUE;
  }

  function update($data, $condition) {
    $query = "UPDATE ".$this->connection->quoteName($this->table);
    $values = Array();
    foreach ($this->getColumns() as $column) {
      if (isset($data[$column])) {
        $values[] = $this->connection->quoteName($column)."=".$this->connection->quote($data[$column]);
      }
    }
    $query .= " SET ".implode(",", $values);
    $where = Array();
    foreach ($condition as $column => $value) {
      $where[] = $this->connection->quoteName($column)."=".$this->connection->quote($value);
    }
    if (count($where) == 0) {
      throw new Exception("No conditions given for update");
    }
    $query .= " WHERE ".implode(" AND ", $where);
    $this->connection->exec($query);
    return TRUE;
  }

  function delete($condition) {
    $query = "DELETE FROM ".$this->connection->quoteName($this->table);
    $where = Array();
    foreach ($condition as $column => $value) {
      $where[] = $this->connection->quoteName($column)."=".$this->connection->quote($value);
    }
    if (count($where) == 0) {
      throw new Exception("No conditions given for delete");
    }
    $query .= " WHERE ".implode(" AND ", $where);
    $result = $this->connection->query($query);
    return $result->rowCount() == 1;
  }
}
