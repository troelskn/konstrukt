CREATE TABLE blogentries (
  name varchar(255) NOT NULL,
  published datetime NOT NULL,
  title varchar(255) NOT NULL,
  excerpt text NOT NULL,
  content longtext NOT NULL,
  PRIMARY KEY (name)
);