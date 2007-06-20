<?php
class Database extends PDO
{
  protected $nameOpening;
  protected $nameClosing;

  function __construct($dsn, $user, $password) {
    parent::__construct($dsn, $user, $password);
    $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $this->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
    switch ($this->getAttribute(PDO::ATTR_DRIVER_NAME)) {
      case 'mysql':
        $this->nameOpening = $this->nameClosing = '`';
        break;

      case 'mssql':
        $this->nameOpening = '[';
        $this->nameClosing = ']';
        break;

      default:
        $this->nameOpening = $this->nameClosing = '"';
        break;
    }
  }

  function quoteName($name) {
    return $this->nameOpening
      .str_replace($this->nameClosing, $this->nameClosing.$this->nameClosing, $name)
      .$this->nameClosing;
  }

  function getTableMeta($table) {
    switch ($this->getAttribute(PDO::ATTR_DRIVER_NAME)) {
      case 'mysql':
        $result = $this->query("SHOW COLUMNS FROM ".$this->quoteName($table));
        $result->setFetchMode(PDO::FETCH_ASSOC);
        $meta = Array();
        foreach ($result as $row) {
          $meta[$row['field']] = Array(
            'pk' => $row['key'] == 'PRI',
            'type' => $row['type'],
          );
        }
        return $meta;
      case 'sqlite':
        $result = $this->query("PRAGMA table_info(".$this->quoteName($table).")");
        $result->setFetchMode(PDO::FETCH_ASSOC);
        $meta = Array();
        foreach ($result as $row) {
          $meta[$row['name']] = Array(
            'pk' => $row['pk'] == '1',
            'type' => $row['type'],
          );
        }
        return $meta;
      default:
        throw new Exception("meta querying not available for driver type");
    }
  }

}
