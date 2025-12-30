-- ============================================
-- AtoM Test Data: Actors and Events
-- For RiC visualization testing
-- ============================================
-- Run: mysql -u root -p archive < populate_test_events.sql
-- ============================================

-- Get the term IDs we need
SET @creation_type = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id = ti.id WHERE ti.name = 'Creation' AND ti.culture = 'en' LIMIT 1);
SET @accumulation_type = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id = ti.id WHERE ti.name = 'Accumulation' AND ti.culture = 'en' LIMIT 1);
SET @person_type = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id = ti.id WHERE ti.name = 'Person' AND ti.culture = 'en' LIMIT 1);
SET @corporate_type = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id = ti.id WHERE ti.name = 'Corporate body' AND ti.culture = 'en' LIMIT 1);
SET @family_type = (SELECT t.id FROM term t JOIN term_i18n ti ON t.id = ti.id WHERE ti.name = 'Family' AND ti.culture = 'en' LIMIT 1);

-- If types not found, use defaults
SET @creation_type = COALESCE(@creation_type, 111);
SET @accumulation_type = COALESCE(@accumulation_type, 112);
SET @person_type = COALESCE(@person_type, 132);
SET @corporate_type = COALESCE(@corporate_type, 131);
SET @family_type = COALESCE(@family_type, 133);

-- ============================================
-- CREATE ACTORS
-- ============================================

-- Get max actor ID to avoid conflicts
SET @max_actor = (SELECT COALESCE(MAX(id), 0) FROM actor);

-- Persons
INSERT INTO actor (id, entity_type_id, source_culture) VALUES
(@max_actor + 1, @person_type, 'en'),
(@max_actor + 2, @person_type, 'en'),
(@max_actor + 3, @person_type, 'en'),
(@max_actor + 4, @person_type, 'en'),
(@max_actor + 5, @person_type, 'en'),
(@max_actor + 6, @person_type, 'en'),
(@max_actor + 7, @person_type, 'en'),
(@max_actor + 8, @person_type, 'en');

INSERT INTO actor_i18n (id, culture, authorized_form_of_name, dates_of_existence, history) VALUES
(@max_actor + 1, 'en', 'Dr. Sarah van der Merwe', '1945-2020', 'Prominent South African archivist and historian. Pioneer in digital preservation.'),
(@max_actor + 2, 'en', 'James Nkosi', '1932-1998', 'Anti-apartheid activist and community organizer. Documented resistance movements.'),
(@max_actor + 3, 'en', 'Maria Santos', '1960-', 'Photographer and visual artist. Documented township life from 1985 onwards.'),
(@max_actor + 4, 'en', 'Prof. Willem Botha', '1938-2015', 'Academic historian specializing in colonial archives.'),
(@max_actor + 5, 'en', 'Thandi Mbeki', '1970-', 'Oral historian and cultural preservationist.'),
(@max_actor + 6, 'en', 'Robert Chen', '1955-', 'Documentary filmmaker and archival researcher.'),
(@max_actor + 7, 'en', 'Grace Dlamini', '1948-2010', 'Journalist and human rights documentarian.'),
(@max_actor + 8, 'en', 'Heinrich Mueller', '1920-1985', 'German-South African engineer. Extensive technical documentation.');

-- Corporate Bodies
INSERT INTO actor (id, entity_type_id, source_culture) VALUES
(@max_actor + 10, @corporate_type, 'en'),
(@max_actor + 11, @corporate_type, 'en'),
(@max_actor + 12, @corporate_type, 'en'),
(@max_actor + 13, @corporate_type, 'en'),
(@max_actor + 14, @corporate_type, 'en'),
(@max_actor + 15, @corporate_type, 'en');

INSERT INTO actor_i18n (id, culture, authorized_form_of_name, dates_of_existence, history) VALUES
(@max_actor + 10, 'en', 'South African Heritage Foundation', '1975-', 'Non-profit organization dedicated to preserving South African cultural heritage.'),
(@max_actor + 11, 'en', 'Johannesburg Historical Society', '1920-', 'Oldest historical society in Gauteng. Extensive photographic collections.'),
(@max_actor + 12, 'en', 'Cape Archives Trust', '1985-2015', 'Private trust managing Western Cape historical collections.'),
(@max_actor + 13, 'en', 'Liberation Movement Documentation Centre', '1990-', 'University-affiliated research centre. Primary sources on apartheid resistance.'),
(@max_actor + 14, 'en', 'Pretoria Technical Institute', '1935-1998', 'Technical college with engineering and mining documentation.'),
(@max_actor + 15, 'en', 'African Oral History Project', '2000-', 'International collaboration documenting oral traditions across Africa.');

-- Families
INSERT INTO actor (id, entity_type_id, source_culture) VALUES
(@max_actor + 20, @family_type, 'en'),
(@max_actor + 21, @family_type, 'en'),
(@max_actor + 22, @family_type, 'en');

INSERT INTO actor_i18n (id, culture, authorized_form_of_name, dates_of_existence, history) VALUES
(@max_actor + 20, 'en', 'Van der Berg Family', '1820-', 'Prominent Cape Dutch family. Farming and political legacy spanning 200 years.'),
(@max_actor + 21, 'en', 'Sisulu Family', '1900-', 'Family of activists and politicians central to liberation struggle.'),
(@max_actor + 22, 'en', 'Oppenheimer Family', '1880-', 'Mining dynasty with significant cultural and philanthropic collections.');

-- ============================================
-- CREATE EVENTS (linking actors to records)
-- ============================================

-- Get max event ID
SET @max_event = (SELECT COALESCE(MAX(id), 0) FROM event);

-- Get all information_object IDs from existing fonds
-- We'll link events to various levels of description

-- For pie_fonds (ID 776) and its children
INSERT INTO event (id, type_id, object_id, actor_id, start_date, end_date, source_culture)
SELECT 
    @max_event := @max_event + 1,
    @creation_type,
    io.id,
    @max_actor + 1 + (io.id % 8),  -- Rotate through persons
    DATE_SUB(CURDATE(), INTERVAL (io.id % 50) YEAR),
    DATE_SUB(CURDATE(), INTERVAL ((io.id % 50) - 5) YEAR),
    'en'
FROM information_object io
WHERE io.id IN (
    SELECT id FROM (
        WITH RECURSIVE hierarchy AS (
            SELECT id FROM information_object WHERE id = 776
            UNION ALL
            SELECT io.id FROM information_object io
            INNER JOIN hierarchy h ON io.parent_id = h.id
        )
        SELECT id FROM hierarchy
    ) AS h
);

-- Add corporate body accumulation events
INSERT INTO event (id, type_id, object_id, actor_id, start_date, end_date, source_culture)
SELECT 
    @max_event := @max_event + 1,
    @accumulation_type,
    io.id,
    @max_actor + 10 + (io.id % 6),  -- Rotate through corporate bodies
    DATE_SUB(CURDATE(), INTERVAL (io.id % 30) YEAR),
    NULL,
    'en'
FROM information_object io
WHERE io.parent_id = 1  -- All fonds level
AND io.id != 1;

-- For mob001 (ID 768) and its children
INSERT INTO event (id, type_id, object_id, actor_id, start_date, end_date, source_culture)
SELECT 
    @max_event := @max_event + 1,
    @creation_type,
    io.id,
    @max_actor + 1 + ((io.id + 3) % 8),
    DATE_SUB(CURDATE(), INTERVAL (io.id % 40) YEAR),
    DATE_SUB(CURDATE(), INTERVAL ((io.id % 40) - 3) YEAR),
    'en'
FROM information_object io
WHERE io.id IN (
    SELECT id FROM (
        WITH RECURSIVE hierarchy AS (
            SELECT id FROM information_object WHERE id = 768
            UNION ALL
            SELECT io.id FROM information_object io
            INNER JOIN hierarchy h ON io.parent_id = h.id
        )
        SELECT id FROM hierarchy
    ) AS h
);

-- For eng_fonds (ID 829) - add multiple creators
INSERT INTO event (id, type_id, object_id, actor_id, start_date, end_date, source_culture)
SELECT 
    @max_event := @max_event + 1,
    @creation_type,
    io.id,
    @max_actor + 1 + ((io.id + 5) % 8),
    DATE_SUB(CURDATE(), INTERVAL (io.id % 60) YEAR),
    NULL,
    'en'
FROM information_object io
WHERE io.id IN (
    SELECT id FROM (
        WITH RECURSIVE hierarchy AS (
            SELECT id FROM information_object WHERE id = 829
            UNION ALL
            SELECT io.id FROM information_object io
            INNER JOIN hierarchy h ON io.parent_id = h.id
        )
        SELECT id FROM hierarchy
    ) AS h
);

-- Add family creators to fonds
INSERT INTO event (id, type_id, object_id, actor_id, start_date, end_date, source_culture)
SELECT 
    @max_event := @max_event + 1,
    @creation_type,
    io.id,
    @max_actor + 20 + (io.id % 3),  -- Rotate through families
    '1950-01-01',
    '1990-12-31',
    'en'
FROM information_object io
WHERE io.parent_id = 1
AND io.id IN (776, 768, 829);

-- Add some cross-fonds relationships (same creator across multiple fonds)
-- Dr. Sarah van der Merwe worked on multiple collections
INSERT INTO event (id, type_id, object_id, actor_id, start_date, end_date, source_culture)
VALUES
(@max_event + 100, @accumulation_type, 776, @max_actor + 1, '2000-01-01', '2010-12-31', 'en'),
(@max_event + 101, @accumulation_type, 768, @max_actor + 1, '2005-01-01', '2015-12-31', 'en'),
(@max_event + 102, @accumulation_type, 829, @max_actor + 1, '2008-01-01', NULL, 'en');

-- Liberation Movement Documentation Centre accumulated from multiple sources
INSERT INTO event (id, type_id, object_id, actor_id, start_date, end_date, source_culture)
VALUES
(@max_event + 110, @accumulation_type, 776, @max_actor + 13, '1995-01-01', NULL, 'en'),
(@max_event + 111, @accumulation_type, 768, @max_actor + 13, '1995-01-01', NULL, 'en');

-- ============================================
-- VERIFICATION QUERIES
-- ============================================

SELECT 'Actors created:' as status, COUNT(*) as count FROM actor WHERE id > @max_actor - 25;
SELECT 'Events created:' as status, COUNT(*) as count FROM event WHERE id > @max_event - 200;

SELECT 
    ai.authorized_form_of_name,
    ti.name as entity_type,
    COUNT(e.id) as event_count
FROM actor a
JOIN actor_i18n ai ON a.id = ai.id AND ai.culture = 'en'
LEFT JOIN term_i18n ti ON a.entity_type_id = ti.id AND ti.culture = 'en'
LEFT JOIN event e ON a.id = e.actor_id
WHERE a.id > @max_actor - 25
GROUP BY a.id, ai.authorized_form_of_name, ti.name
ORDER BY event_count DESC;

SELECT 'Test data population complete!' as message;
