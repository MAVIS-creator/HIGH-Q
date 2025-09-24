INSERT INTO icons (name, filename, `class`) VALUES
  ('Target', 'target.svg', 'bx bxs-bullseye'),
  ('Book Stack', 'book-stack.svg', 'bx bxs-book-bookmark'),
  ('Book Open', 'book-open.svg', 'bx bxs-book-open'),
  ('Trophy', 'trophy.svg', 'bx bxs-trophy'),
  ('Star', 'star.svg', 'bx bxs-star'),
  ('Laptop', 'laptop.svg', 'bx bxs-laptop'),
  ('Teacher', 'teacher.svg', 'bx bxs-user'),
  ('Results', 'results.svg', 'bx bxs-bar-chart-alt-2'),
  ('Graduation', 'graduation.svg', 'bx bxs-graduation')
ON DUPLICATE KEY UPDATE `class` = VALUES(`class`), name = VALUES(name);
