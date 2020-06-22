CREATE DATABASE stats;
CREATE USER stats WITH ENCRYPTED PASSWORD 'password';

\c stats;

CREATE TABLE surfers_logs (
    datetime timestamp,
    status int CHECK (status = 1 or status = 2)
);

CREATE TABLE surfers_logs_by_seconds (
    datetime timestamp,
    max_surfers int,
    surfers_at_the_end int,
    CONSTRAINT datetime_unique UNIQUE (datetime)
);

CREATE TABLE surfers_logs_by_seconds_parts (
    datetime timestamp,
    part int,
    max_surfers int,
    surfers_at_the_end int,
    CONSTRAINT datetime_part_unique UNIQUE (datetime, part)
);

CREATE INDEX ON surfers_logs (datetime);
CREATE INDEX ON surfers_logs_by_seconds (datetime);
CREATE INDEX ON surfers_logs_by_seconds (max_surfers);

GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO stats;
