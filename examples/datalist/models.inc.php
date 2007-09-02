<?php
class SuperHeroes
{
  protected $db;

  function __construct(PDO $db) {
    $this->db = $db;
    $this->db->exec("CREATE TABLE superheroes (name VARCHAR(255) NOT NULL, rating INTEGER NOT NULL, PRIMARY KEY (name))");
    $stmt = $this->db->prepare("INSERT INTO superheroes (name, rating) VALUES (:name, :rating)");
    $stmt->execute(Array(':name' => 'Batman',          ':rating' => 9));
    $stmt->execute(Array(':name' => 'Superman',        ':rating' => 11));
    $stmt->execute(Array(':name' => 'Wonder Woman',    ':rating' => 11));
    $stmt->execute(Array(':name' => 'The Hulk',        ':rating' => 9));
    $stmt->execute(Array(':name' => 'Captain America', ':rating' => 5));
    $stmt->execute(Array(':name' => 'Hellboy',         ':rating' => 6));
    $stmt->execute(Array(':name' => 'Juggernaut',      ':rating' => 6));
    $stmt->execute(Array(':name' => 'Wolverine',       ':rating' => 6));
    $stmt->execute(Array(':name' => 'Cyclops',         ':rating' => 6));
    $stmt->execute(Array(':name' => 'Allan Quatermain',':rating' => 2));
    $stmt->execute(Array(':name' => 'Black Panther',   ':rating' => 5));
    $stmt->execute(Array(':name' => 'Blade',           ':rating' => 10));
    $stmt->execute(Array(':name' => 'Elektra',         ':rating' => 3));
    $stmt->execute(Array(':name' => 'Doctor Strange',  ':rating' => 11));
    $stmt->execute(Array(':name' => 'Green Lantern',   ':rating' => 7));
  }

  /**
   */
  function query($offset, $limit, $orderby, $direction) {
    $sql = "SELECT * FROM superheroes";
    if ($orderby) {
      $sql .= " ORDER BY " . $orderby;
      if ($direction) {
        $sql .= " " . $direction;
      }
    }
    $sql .= " LIMIT " . $offset . ", " . $limit;

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   */
  function count() {
    $stmt = $this->db->prepare("SELECT COUNT(*) FROM superheroes");
    $stmt->execute();
    $row = $stmt->fetch();
    return $row[0];
  }
}

class SuperVillains
{
  protected $db;

  function __construct(PDO $db) {
    $this->db = $db;
    $this->db->exec("CREATE TABLE supervillains (name VARCHAR(255) NOT NULL, rating INTEGER NOT NULL, PRIMARY KEY (name))");
    $stmt = $this->db->prepare("INSERT INTO supervillains (name, rating) VALUES (:name, :rating)");
    $stmt->execute(Array(':name' => 'Catwoman',        ':rating' => 5));
    $stmt->execute(Array(':name' => 'Green Goblin',    ':rating' => 7));
    $stmt->execute(Array(':name' => 'Joker',           ':rating' => 11));
    $stmt->execute(Array(':name' => 'Lex Luthor',      ':rating' => 0));
    $stmt->execute(Array(':name' => 'Magneto',         ':rating' => 9));
    $stmt->execute(Array(':name' => 'Mystique',        ':rating' => 6));
    $stmt->execute(Array(':name' => 'Poison Ivy',      ':rating' => 5));
    $stmt->execute(Array(':name' => 'Doctor Doom',     ':rating' => 12));
    $stmt->execute(Array(':name' => 'Riddler',         ':rating' => 9));
  }

  /**
   */
  function query($offset, $limit, $orderby, $direction) {
    $sql = "SELECT * FROM supervillains";
    if ($orderby) {
      $sql .= " ORDER BY " . $orderby;
      if ($direction) {
        $sql .= " " . $direction;
      }
    }
    $sql .= " LIMIT " . $offset . ", " . $limit;

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   */
  function count() {
    $stmt = $this->db->prepare("SELECT COUNT(*) FROM supervillains");
    $stmt->execute();
    $row = $stmt->fetch();
    return $row[0];
  }
}
