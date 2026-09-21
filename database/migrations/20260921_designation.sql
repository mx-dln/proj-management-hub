INSERT INTO document_categories (name, description, is_active)
SELECT 'Designation', 'Designation letters and office orders', 1
WHERE NOT EXISTS (SELECT 1 FROM document_categories WHERE name = 'Designation');
