-- Migration: add comments.ip column to store submitter IP address
ALTER TABLE `comments`
  ADD COLUMN `ip` VARCHAR(64) NULL AFTER `content`;
