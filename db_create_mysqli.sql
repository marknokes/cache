CREATE DATABASE feedcache;
USE feedcache;
CREATE TABLE cache_data(
	id varchar(50) NOT NULL,
	last_run bigint NOT NULL,
	cache_content text NOT NULL
)