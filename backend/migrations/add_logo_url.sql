-- v1.2.0 Migration: Firma logo URL
ALTER TABLE alt_firma ADD COLUMN logo_url VARCHAR(500) NULL DEFAULT NULL;
