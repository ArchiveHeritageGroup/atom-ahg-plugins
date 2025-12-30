-- ============================================
-- AtoM Test Data: Actors and Events (Fixed Version)
-- Handles AtoM's class table inheritance
-- ============================================
-- Backup first: mysqldump -u root -p archive > archive_backup.sql
-- Run: mysql -u root -p archive < populate_test_fixed.sql
-- ============================================

-- ============================================
-- STEP 1: INSERT INTO OBJECT TABLE FIRST
-- AtoM uses class table inheritance (actor extends object)
-- ============================================

INSERT INTO object (id, class_name, created_at, updated_at) VALUES
(900001, 'QubitActor', NOW(), NOW()),
(900002, 'QubitActor', NOW(), NOW()),
(900003, 'QubitActor', NOW(), NOW()),
(900004, 'QubitActor', NOW(), NOW()),
(900005, 'QubitActor', NOW(), NOW()),
(900006, 'QubitActor', NOW(), NOW()),
(900007, 'QubitActor', NOW(), NOW()),
(900008, 'QubitActor', NOW(), NOW()),
(900010, 'QubitActor', NOW(), NOW()),
(900011, 'QubitActor', NOW(), NOW()),
(900012, 'QubitActor', NOW(), NOW()),
(900013, 'QubitActor', NOW(), NOW()),
(900014, 'QubitActor', NOW(), NOW()),
(900015, 'QubitActor', NOW(), NOW()),
(900020, 'QubitActor', NOW(), NOW()),
(900021, 'QubitActor', NOW(), NOW()),
(900022, 'QubitActor', NOW(), NOW());

-- Events also extend object
INSERT INTO object (id, class_name, created_at, updated_at) VALUES
(900100, 'QubitEvent', NOW(), NOW()),
(900101, 'QubitEvent', NOW(), NOW()),
(900102, 'QubitEvent', NOW(), NOW()),
(900110, 'QubitEvent', NOW(), NOW()),
(900111, 'QubitEvent', NOW(), NOW()),
(900112, 'QubitEvent', NOW(), NOW()),
(900113, 'QubitEvent', NOW(), NOW()),
(900120, 'QubitEvent', NOW(), NOW()),
(900121, 'QubitEvent', NOW(), NOW()),
(900122, 'QubitEvent', NOW(), NOW()),
(900130, 'QubitEvent', NOW(), NOW()),
(900131, 'QubitEvent', NOW(), NOW()),
(900132, 'QubitEvent', NOW(), NOW()),
(900140, 'QubitEvent', NOW(), NOW()),
(900141, 'QubitEvent', NOW(), NOW()),
(900142, 'QubitEvent', NOW(), NOW());

-- ============================================
-- STEP 2: GET TERM IDS FOR ENTITY TYPES
-- ============================================

-- Check entity type term IDs
SELECT @person_type := t.id FROM term t 
JOIN term_i18n ti ON t.id = ti.id 
WHERE ti.name = 'Person' AND ti.culture = 'en' LIMIT 1;

SELECT @corporate_type := t.id FROM term t 
JOIN term_i18n ti ON t.id = ti.id 
WHERE ti.name = 'Corporate body' AND ti.culture = 'en' LIMIT 1;

SELECT @family_type := t.id FROM term t 
JOIN term_i18n ti ON t.id = ti.id 
WHERE ti.name = 'Family' AND ti.culture = 'en' LIMIT 1;

-- Check event type term IDs
SELECT @creation_type := t.id FROM term t 
JOIN term_i18n ti ON t.id = ti.id 
WHERE ti.name = 'Creation' AND ti.culture = 'en' LIMIT 1;

SELECT @accumulation_type := t.id FROM term t 
JOIN term_i18n ti ON t.id = ti.id 
WHERE ti.name = 'Accumulation' AND ti.culture = 'en' LIMIT 1;

-- Show what we found
SELECT @person_type as person, @corporate_type as corporate, @family_type as family, 
       @creation_type as creation, @accumulation_type as accumulation;

-- ============================================
-- STEP 3: CREATE ACTORS (Persons)
-- ============================================

INSERT INTO actor (id, entity_type_id, source_culture) VALUES
(900001, @person_type, 'en'),
(900002, @person_type, 'en'),
(900003, @person_type, 'en'),
(900004, @person_type, 'en'),
(900005, @person_type, 'en'),
(900006, @person_type, 'en'),
(900007, @person_type, 'en'),
(900008, @person_type, 'en');

INSERT INTO actor_i18n (id, culture, authorized_form_of_name, dates_of_existence, history) VALUES
(900001, 'en', 'Dr. Sarah van der Merwe', '1945-2020', 'Prominent South African archivist and historian. Pioneer in digital preservation.'),
(900002, 'en', 'James Nkosi', '1932-1998', 'Anti-apartheid activist and community organizer. Documented resistance movements.'),
(900003, 'en', 'Maria Santos', '1960-', 'Photographer and visual artist. Documented township life from 1985 onwards.'),
(900004, 'en', 'Prof. Willem Botha', '1938-2015', 'Academic historian specializing in colonial archives.'),
(900005, 'en', 'Thandi Mbeki', '1970-', 'Oral historian and cultural preservationist.'),
(900006, 'en', 'Robert Chen', '1955-', 'Documentary filmmaker and archival researcher.'),
(900007, 'en', 'Grace Dlamini', '1948-2010', 'Journalist and human rights documentarian.'),
(900008, 'en', 'Heinrich Mueller', '1920-1985', 'German-South African engineer. Extensive technical documentation.');

-- ============================================
-- STEP 4: CREATE ACTORS (Corporate Bodies)
-- ============================================

INSERT INTO actor (id, entity_type_id, source_culture) VALUES
(900010, @corporate_type, 'en'),
(900011, @corporate_type, 'en'),
(900012, @corporate_type, 'en'),
(900013, @corporate_type, 'en'),
(900014, @corporate_type, 'en'),
(900015, @corporate_type, 'en');

INSERT INTO actor_i18n (id, culture, authorized_form_of_name, dates_of_existence, history) VALUES
(900010, 'en', 'South African Heritage Foundation', '1975-', 'Non-profit organization dedicated to preserving South African cultural heritage.'),
(900011, 'en', 'Johannesburg Historical Society', '1920-', 'Oldest historical society in Gauteng. Extensive photographic collections.'),
(900012, 'en', 'Cape Archives Trust', '1985-2015', 'Private trust managing Western Cape historical collections.'),
(900013, 'en', 'Liberation Movement Documentation Centre', '1990-', 'University-affiliated research centre. Primary sources on apartheid resistance.'),
(900014, 'en', 'Pretoria Technical Institute', '1935-1998', 'Technical college with engineering and mining documentation.'),
(900015, 'en', 'African Oral History Project', '2000-', 'International collaboration documenting oral traditions across Africa.');

-- ============================================
-- STEP 5: CREATE ACTORS (Families)
-- ============================================

INSERT INTO actor (id, entity_type_id, source_culture) VALUES
(900020, @family_type, 'en'),
(900021, @family_type, 'en'),
(900022, @family_type, 'en');

INSERT INTO actor_i18n (id, culture, authorized_form_of_name, dates_of_existence, history) VALUES
(900020, 'en', 'Van der Berg Family', '1820-', 'Prominent Cape Dutch family. Farming and political legacy spanning 200 years.'),
(900021, 'en', 'Sisulu Family', '1900-', 'Family of activists and politicians central to liberation struggle.'),
(900022, 'en', 'Oppenheimer Family', '1880-', 'Mining dynasty with significant cultural and philanthropic collections.');

-- ============================================
-- STEP 6: CREATE EVENTS
-- ============================================

-- Events for pie_fonds (776) - multiple creators
INSERT INTO event (id, type_id, object_id, actor_id, start_date, end_date, source_culture) VALUES
(900100, @creation_type, 776, 900001, '1970-01-01', '2000-12-31', 'en'),
(900101, @creation_type, 776, 900020, '1950-01-01', '1990-12-31', 'en'),
(900102, @accumulation_type, 776, 900010, '2000-01-01', NULL, 'en');

-- Events for mob001 (768) - multiple creators and accumulators
INSERT INTO event (id, type_id, object_id, actor_id, start_date, end_date, source_culture) VALUES
(900110, @creation_type, 768, 900002, '1965-01-01', '1990-12-31', 'en'),
(900111, @creation_type, 768, 900003, '1980-01-01', '1995-12-31', 'en'),
(900112, @accumulation_type, 768, 900011, '1995-01-01', NULL, 'en'),
(900113, @accumulation_type, 768, 900013, '2000-01-01', NULL, 'en');

-- Events for eng_fonds (829)
INSERT INTO event (id, type_id, object_id, actor_id, start_date, end_date, source_culture) VALUES
(900120, @creation_type, 829, 900004, '1960-01-01', '1980-12-31', 'en'),
(900121, @creation_type, 829, 900008, '1955-01-01', '1985-12-31', 'en'),
(900122, @accumulation_type, 829, 900014, '1985-01-01', '1998-12-31', 'en');

-- Cross-fonds: Dr. Sarah van der Merwe worked on multiple collections
INSERT INTO event (id, type_id, object_id, actor_id, start_date, end_date, source_culture) VALUES
(900130, @accumulation_type, 776, 900001, '2000-01-01', '2010-12-31', 'en'),
(900131, @accumulation_type, 768, 900001, '2005-01-01', '2015-12-31', 'en'),
(900132, @accumulation_type, 829, 900001, '2008-01-01', NULL, 'en');

-- Liberation Movement Documentation Centre - central accumulator
INSERT INTO event (id, type_id, object_id, actor_id, start_date, end_date, source_culture) VALUES
(900140, @accumulation_type, 776, 900013, '1995-01-01', NULL, 'en'),
(900141, @accumulation_type, 768, 900013, '1995-01-01', NULL, 'en'),
(900142, @accumulation_type, 829, 900013, '1998-01-01', NULL, 'en');

-- ============================================
-- VERIFICATION
-- ============================================

SELECT '=== ACTORS CREATED ===' as info;
SELECT 
    ai.authorized_form_of_name as name,
    ti.name as entity_type
FROM actor a
JOIN actor_i18n ai ON a.id = ai.id AND ai.culture = 'en'
LEFT JOIN term_i18n ti ON a.entity_type_id = ti.id AND ti.culture = 'en'
WHERE a.id >= 900000
ORDER BY a.entity_type_id, a.id;

SELECT '=== EVENTS CREATED ===' as info;
SELECT 
    e.id,
    ioi.title as record,
    ai.authorized_form_of_name as creator,
    ti.name as event_type,
    e.start_date,
    e.end_date
FROM event e
JOIN information_object_i18n ioi ON e.object_id = ioi.id AND ioi.culture = 'en'
JOIN actor_i18n ai ON e.actor_id = ai.id AND ai.culture = 'en'
LEFT JOIN term_i18n ti ON e.type_id = ti.id AND ti.culture = 'en'
WHERE e.id >= 900000
ORDER BY e.object_id, e.id;

SELECT '=== SUMMARY ===' as info;
SELECT 
    (SELECT COUNT(*) FROM actor WHERE id >= 900000) as actors_created,
    (SELECT COUNT(*) FROM event WHERE id >= 900000) as events_created;

SELECT 'Test data population complete!' as message;