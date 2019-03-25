CREATE TABLE IF NOT EXISTS customers
(
    id uuid PRIMARY KEY,
    name varchar NOT NULL,
    email varchar NOT NULL
);