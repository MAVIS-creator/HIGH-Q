-- Remove the program slugs added by 2025-12-23-upsert-program-slugs.sql
-- This restores the database to use only the original courses

-- Delete the 6 courses and their features that were added
DELETE FROM course_features WHERE course_id IN (
  SELECT id FROM courses WHERE slug IN (
    'jamb-preparation',
    'waec-preparation', 
    'neco-preparation',
    'post-utme',
    'special-tutorials',
    'computer-training'
  )
);

DELETE FROM courses WHERE slug IN (
  'jamb-preparation',
  'waec-preparation',
  'neco-preparation', 
  'post-utme',
  'special-tutorials',
  'computer-training'
);
