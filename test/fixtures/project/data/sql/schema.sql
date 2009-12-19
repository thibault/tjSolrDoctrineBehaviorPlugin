CREATE TABLE post (id BIGINT AUTO_INCREMENT, thread_id BIGINT NOT NULL, title VARCHAR(255) NOT NULL, body LONGTEXT NOT NULL, INDEX thread_id_idx (thread_id), PRIMARY KEY(id)) ENGINE = INNODB;
CREATE TABLE thread (id BIGINT AUTO_INCREMENT, title VARCHAR(255) NOT NULL, PRIMARY KEY(id)) ENGINE = INNODB;
ALTER TABLE post ADD CONSTRAINT post_thread_id_thread_id FOREIGN KEY (thread_id) REFERENCES thread(id) ON DELETE CASCADE;