ALTER TABLE proposals ADD COLUMN proponent VARCHAR(255) NULL AFTER title;
ALTER TABLE proposals ADD COLUMN attachments LONGTEXT NULL AFTER attachment;
ALTER TABLE accomplishment_reports ADD COLUMN attachments LONGTEXT NULL AFTER attachment;
ALTER TABLE moas ADD COLUMN attachments LONGTEXT NULL AFTER attachment;
