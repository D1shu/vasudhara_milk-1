
/* ============================
   ADDITIONAL MASTER DATA
   (Villages for new centres)
   ============================ */

-- New villages required for the list below (all under taluka_id = 1 : Vansda)
INSERT IGNORE INTO villages (taluka_id, name) VALUES
(1, 'Khadakiya'),
(1, 'Jhuj'),
(1, 'Manpur'),
(1, 'Dhakmal'),
(1, 'Kapadvanj'),
(1, 'Navtad'),
(1, 'Khambhala'),
(1, 'Bilmora'),
(1, 'Vadbari');



/* ============================
   ADDITIONAL ANGANWADI/SCHOOLS
   FROM PROVIDED LIST (1–30)
   ============================ */

-- NOTE:
-- route_id kept NULL for now; update later when routes are finalized.
-- total_children, pregnant_women are kept 0 by default.

INSERT INTO anganwadi (village_id, route_id, aw_code, name, type, total_children, pregnant_women, contact_person, mobile) VALUES
-- Anganwadi (1–19)
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Vansda'    LIMIT 1), NULL, 'AW006', 'Vansda - 3',            'anganwadi', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Vansda'    LIMIT 1), NULL, 'AW007', 'Vansda - 1',            'anganwadi', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Vansda'    LIMIT 1), NULL, 'AW008', 'Vansda - 8',            'anganwadi', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Khadakiya' LIMIT 1), NULL, 'AW009', 'Khadakiya',             'anganwadi', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Jhuj'      LIMIT 1), NULL, 'AW010', 'Jhuj',                  'anganwadi', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Manpur'    LIMIT 1), NULL, 'AW011', 'Manpur - 4',            'anganwadi', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Manpur'    LIMIT 1), NULL, 'AW012', 'Manpur - 1',            'anganwadi', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Manpur'    LIMIT 1), NULL, 'AW013', 'Manpur - 3',            'anganwadi', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Dhakmal'   LIMIT 1), NULL, 'AW014', 'Dhakmal Mini',          'anganwadi', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Dhakmal'   LIMIT 1), NULL, 'AW015', 'Dhakmal',               'anganwadi', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Kapadvanj' LIMIT 1), NULL, 'AW016', 'Kapadvanj',             'anganwadi', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Navtad'    LIMIT 1), NULL, 'AW017', 'Navtad - 2',            'anganwadi', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Khambhala' LIMIT 1), NULL, 'AW018', 'Khambhala - 3',         'anganwadi', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Khambhala' LIMIT 1), NULL, 'AW019', 'Khambhala - 4',         'anganwadi', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Khambhala' LIMIT 1), NULL, 'AW020', 'Khambhala - 5',         'anganwadi', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Khambhala' LIMIT 1), NULL, 'AW021', 'Khambhala - 6',         'anganwadi', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Khambhala' LIMIT 1), NULL, 'AW022', 'Khambhala - 1',         'anganwadi', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Khambhala' LIMIT 1), NULL, 'AW023', 'Khambhala - 2',         'anganwadi', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Bilmora'   LIMIT 1), NULL, 'AW024', 'Bilmora',               'anganwadi', 0, 0, NULL, NULL),

-- Primary Schools (20–30) -> type = 'school'
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Vadbari'   LIMIT 1), NULL, 'AW025', 'Vadbari Class School',              'school', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Khadakiya' LIMIT 1), NULL, 'AW026', 'Khadakiya Primary School',          'school', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Jhuj'      LIMIT 1), NULL, 'AW027', 'Juj Pvt. School',                   'school', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Manpur'    LIMIT 1), NULL, 'AW028', 'Manpur Pvt. School',               'school', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Khambhala' LIMIT 1), NULL, 'AW029', 'Khambhala Main School',            'school', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Bilmora'   LIMIT 1), NULL, 'AW030', 'Bilmora Pvt. School',              'school', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Dhakmal'   LIMIT 1), NULL, 'AW031', 'Dhakmal Pvt. School',              'school', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Kapadvanj' LIMIT 1), NULL, 'AW032', 'Kapadvanj Pvt. School',            'school', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Khambhala' LIMIT 1), NULL, 'AW033', 'Khambhala Class School',           'school', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Manpur'    LIMIT 1), NULL, 'AW034', 'Kundan Asram School Manpur',       'school', 0, 0, NULL, NULL),
((SELECT id FROM villages WHERE taluka_id = 1 AND name = 'Khambhala' LIMIT 1), NULL, 'AW035', 'Keshav Asram School Khambhala',    'school', 0, 0, NULL, NULL);