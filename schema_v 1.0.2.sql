-- Migration v1.0.2: Add Stock Tracking Columns
USE library_db;

-- 1. Add total_copies and available_copies to books table
-- Defaulting to 1 to support existing single-copy books
ALTER TABLE books
ADD COLUMN total_copies INT NOT NULL DEFAULT 1 AFTER publication_year,
ADD COLUMN available_copies INT NOT NULL DEFAULT 1 AFTER total_copies;

-- 2. Update available_copies for currently issued books
-- If a book is currently issued (status_id corresponds to 'Issued'), available_copies should be 0.
-- We need to find the status_id for 'Issued' first.

SET @issued_status_id = (SELECT status_id FROM status WHERE status_name = 'Issued');
SET @available_status_id = (SELECT status_id FROM status WHERE status_name = 'Available');

-- Update available_copies to 0 for books that are currently marked as Issued
UPDATE books 
SET available_copies = 0
WHERE status_id = @issued_status_id;

-- 3. (Optional) Ensure consistency for Available books
-- (They default to 1, which is correct for single-copy model, so no action needed usually, 
-- but good to be explicit if we had other logic)

-- 4. Add index for faster availability checks
CREATE INDEX idx_books_available_copies ON books(available_copies);
