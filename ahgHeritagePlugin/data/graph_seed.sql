-- ============================================================================
-- Heritage Knowledge Graph - Seed Data
-- Populates heritage_entity_cache with rich, interconnected entities
-- referencing real information_object records in the archive database.
--
-- After running: php bin/atom heritage:build-graph --rebuild
-- ============================================================================

-- Clear existing cache and graph data for a clean rebuild
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE heritage_entity_graph_object;
TRUNCATE TABLE heritage_entity_graph_edge;
TRUNCATE TABLE heritage_entity_graph_node;
TRUNCATE TABLE heritage_entity_cache;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- CLUSTER 1: South African Transition to Democracy
-- Objects: 768 (Mobrey Family Archive), 902403 (Women's orgs), 902404 (Long Walk)
-- ============================================================================

-- Object 768: Mobrey Family Archive - liberation struggle records
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(768, 'person', 'Nelson Mandela', 'nelson mandela', 0.99, 'scope_and_content', 'ner'),
(768, 'person', 'Walter Sisulu', 'walter sisulu', 0.95, 'scope_and_content', 'ner'),
(768, 'person', 'Oliver Tambo', 'oliver tambo', 0.95, 'scope_and_content', 'ner'),
(768, 'person', 'Albert Luthuli', 'albert luthuli', 0.92, 'scope_and_content', 'ner'),
(768, 'person', 'Desmond Tutu', 'desmond tutu', 0.97, 'scope_and_content', 'ner'),
(768, 'organization', 'African National Congress', 'african national congress', 0.99, 'scope_and_content', 'ner'),
(768, 'organization', 'South African Communist Party', 'south african communist party', 0.90, 'scope_and_content', 'ner'),
(768, 'organization', 'Pan Africanist Congress', 'pan africanist congress', 0.88, 'scope_and_content', 'ner'),
(768, 'organization', 'United Nations', 'united nations', 0.85, 'scope_and_content', 'ner'),
(768, 'place', 'Johannesburg', 'johannesburg', 0.98, 'scope_and_content', 'ner'),
(768, 'place', 'Soweto', 'soweto', 0.95, 'scope_and_content', 'ner'),
(768, 'place', 'Robben Island', 'robben island', 0.97, 'scope_and_content', 'ner'),
(768, 'place', 'South Africa', 'south africa', 0.99, 'scope_and_content', 'ner'),
(768, 'date', '1961', '1961', 0.95, 'date', 'pattern'),
(768, 'date', '1964', '1964', 0.90, 'scope_and_content', 'ner'),
(768, 'event', 'Rivonia Trial', 'rivonia trial', 0.93, 'scope_and_content', 'ner'),
(768, 'event', 'Sharpeville Massacre', 'sharpeville massacre', 0.91, 'scope_and_content', 'ner'),
(768, 'work', 'Freedom Charter', 'freedom charter', 0.90, 'scope_and_content', 'ner');

-- Object 902403: Women's organisations in South Africa
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(902403, 'person', 'Albertina Sisulu', 'albertina sisulu', 0.95, 'scope_and_content', 'ner'),
(902403, 'person', 'Winnie Madikizela-Mandela', 'winnie madikizela-mandela', 0.96, 'scope_and_content', 'ner'),
(902403, 'person', 'Helen Suzman', 'helen suzman', 0.94, 'scope_and_content', 'ner'),
(902403, 'person', 'Lillian Ngoyi', 'lillian ngoyi', 0.92, 'scope_and_content', 'ner'),
(902403, 'person', 'Ruth First', 'ruth first', 0.90, 'scope_and_content', 'ner'),
(902403, 'person', 'Nelson Mandela', 'nelson mandela', 0.85, 'scope_and_content', 'ner'),
(902403, 'person', 'Walter Sisulu', 'walter sisulu', 0.82, 'scope_and_content', 'ner'),
(902403, 'organization', 'African National Congress', 'african national congress', 0.97, 'scope_and_content', 'ner'),
(902403, 'organization', 'Federation of South African Women', 'federation of south african women', 0.96, 'scope_and_content', 'ner'),
(902403, 'organization', 'Black Sash', 'black sash', 0.94, 'scope_and_content', 'ner'),
(902403, 'organization', 'South African Communist Party', 'south african communist party', 0.88, 'scope_and_content', 'ner'),
(902403, 'place', 'Pretoria', 'pretoria', 0.95, 'scope_and_content', 'ner'),
(902403, 'place', 'Johannesburg', 'johannesburg', 0.93, 'scope_and_content', 'ner'),
(902403, 'place', 'Soweto', 'soweto', 0.90, 'scope_and_content', 'ner'),
(902403, 'place', 'South Africa', 'south africa', 0.99, 'scope_and_content', 'ner'),
(902403, 'date', '9 August 1956', '9 august 1956', 0.95, 'scope_and_content', 'ner'),
(902403, 'date', '1956', '1956', 0.90, 'scope_and_content', 'ner'),
(902403, 'event', 'Women\'s March to the Union Buildings', 'women\'s march to union buildings', 0.94, 'scope_and_content', 'ner'),
(902403, 'event', 'Sharpeville Massacre', 'sharpeville massacre', 0.85, 'scope_and_content', 'ner'),
(902403, 'work', 'Freedom Charter', 'freedom charter', 0.88, 'scope_and_content', 'ner');

-- Object 902404: Long Walk to Freedom
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(902404, 'person', 'Nelson Mandela', 'nelson mandela', 0.99, 'title', 'ner'),
(902404, 'person', 'F.W. de Klerk', 'f.w. de klerk', 0.97, 'scope_and_content', 'ner'),
(902404, 'person', 'Desmond Tutu', 'desmond tutu', 0.93, 'scope_and_content', 'ner'),
(902404, 'person', 'Thabo Mbeki', 'thabo mbeki', 0.90, 'scope_and_content', 'ner'),
(902404, 'person', 'Cyril Ramaphosa', 'cyril ramaphosa', 0.92, 'scope_and_content', 'ner'),
(902404, 'person', 'Walter Sisulu', 'walter sisulu', 0.88, 'scope_and_content', 'ner'),
(902404, 'person', 'Oliver Tambo', 'oliver tambo', 0.87, 'scope_and_content', 'ner'),
(902404, 'person', 'Mangosuthu Buthelezi', 'mangosuthu buthelezi', 0.85, 'scope_and_content', 'ner'),
(902404, 'organization', 'African National Congress', 'african national congress', 0.99, 'scope_and_content', 'ner'),
(902404, 'organization', 'National Party', 'national party', 0.97, 'scope_and_content', 'ner'),
(902404, 'organization', 'CODESA', 'codesa', 0.94, 'scope_and_content', 'ner'),
(902404, 'organization', 'Truth and Reconciliation Commission', 'truth and reconciliation commission', 0.93, 'scope_and_content', 'ner'),
(902404, 'organization', 'Inkatha Freedom Party', 'inkatha freedom party', 0.90, 'scope_and_content', 'ner'),
(902404, 'organization', 'United Nations', 'united nations', 0.80, 'scope_and_content', 'ner'),
(902404, 'place', 'Robben Island', 'robben island', 0.98, 'scope_and_content', 'ner'),
(902404, 'place', 'Cape Town', 'cape town', 0.95, 'scope_and_content', 'ner'),
(902404, 'place', 'Pretoria', 'pretoria', 0.94, 'scope_and_content', 'ner'),
(902404, 'place', 'South Africa', 'south africa', 0.99, 'scope_and_content', 'ner'),
(902404, 'place', 'Johannesburg', 'johannesburg', 0.90, 'scope_and_content', 'ner'),
(902404, 'place', 'Durban', 'durban', 0.85, 'scope_and_content', 'ner'),
(902404, 'date', '11 February 1990', '11 february 1990', 0.98, 'scope_and_content', 'ner'),
(902404, 'date', '27 April 1994', '27 april 1994', 0.98, 'scope_and_content', 'ner'),
(902404, 'date', '10 May 1994', '10 may 1994', 0.97, 'scope_and_content', 'ner'),
(902404, 'date', '1964', '1964', 0.90, 'scope_and_content', 'ner'),
(902404, 'event', 'First Democratic Election', 'first democratic election', 0.97, 'scope_and_content', 'ner'),
(902404, 'event', 'Rivonia Trial', 'rivonia trial', 0.95, 'scope_and_content', 'ner'),
(902404, 'event', 'Release from Prison', 'release from prison', 0.94, 'scope_and_content', 'ner'),
(902404, 'event', 'Inauguration as President', 'inauguration as president', 0.93, 'scope_and_content', 'ner'),
(902404, 'work', 'Long Walk to Freedom', 'long walk to freedom', 0.99, 'title', 'pattern'),
(902404, 'work', 'Constitution of South Africa', 'constitution of south africa', 0.88, 'scope_and_content', 'ner'),
(902404, 'work', 'Freedom Charter', 'freedom charter', 0.85, 'scope_and_content', 'ner');

-- ============================================================================
-- CLUSTER 2: Engelbrecht Family History (SA genealogy + church + Boer War)
-- Objects: 829, 837, 845
-- ============================================================================

-- Object 829: Engelbrecht Family Fonds
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(829, 'person', 'Hendrik Engelbrecht', 'hendrik engelbrecht', 0.98, 'scope_and_content', 'manual'),
(829, 'person', 'Debora Engelbrecht', 'debora engelbrecht', 0.98, 'scope_and_content', 'manual'),
(829, 'person', 'Rosa Engelbrecht', 'rosa engelbrecht', 0.95, 'scope_and_content', 'manual'),
(829, 'person', 'Prof. Willem Botha', 'prof. willem botha', 0.90, 'creator', 'taxonomy'),
(829, 'person', 'Heinrich Mueller', 'heinrich mueller', 0.88, 'creator', 'taxonomy'),
(829, 'organization', 'Dutch Reformed Church', 'dutch reformed church', 0.93, 'scope_and_content', 'ner'),
(829, 'organization', 'Pretoria Technical Institute', 'pretoria technical institute', 0.90, 'creator', 'taxonomy'),
(829, 'organization', 'Cape Archives Trust', 'cape archives trust', 0.85, 'scope_and_content', 'ner'),
(829, 'place', 'Pretoria', 'pretoria', 0.97, 'scope_and_content', 'ner'),
(829, 'place', 'Transvaal', 'transvaal', 0.93, 'scope_and_content', 'ner'),
(829, 'place', 'South Africa', 'south africa', 0.99, 'scope_and_content', 'ner'),
(829, 'place', 'Cape Colony', 'cape colony', 0.88, 'scope_and_content', 'ner'),
(829, 'date', '1885', '1885', 0.90, 'scope_and_content', 'ner'),
(829, 'date', '1902', '1902', 0.88, 'scope_and_content', 'ner'),
(829, 'event', 'Great Trek', 'great trek', 0.85, 'scope_and_content', 'ner'),
(829, 'event', 'Anglo-Boer War', 'anglo-boer war', 0.90, 'scope_and_content', 'ner'),
(829, 'work', 'Engelbrecht Family Bible', 'engelbrecht family bible', 0.95, 'scope_and_content', 'manual');

-- Object 837: Engelbrecht Family Bible
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(837, 'person', 'Hendrik Engelbrecht', 'hendrik engelbrecht', 0.99, 'title', 'manual'),
(837, 'person', 'Debora Engelbrecht', 'debora engelbrecht', 0.99, 'scope_and_content', 'manual'),
(837, 'person', 'Rosa Engelbrecht', 'rosa engelbrecht', 0.95, 'scope_and_content', 'manual'),
(837, 'person', 'Bernardth Papenvoes', 'bernardth papenvoes', 0.90, 'scope_and_content', 'manual'),
(837, 'organization', 'Dutch Reformed Church', 'dutch reformed church', 0.90, 'scope_and_content', 'ner'),
(837, 'place', 'Pretoria', 'pretoria', 0.95, 'scope_and_content', 'ner'),
(837, 'place', 'Transvaal', 'transvaal', 0.90, 'scope_and_content', 'ner'),
(837, 'place', 'South Africa', 'south africa', 0.99, 'scope_and_content', 'ner'),
(837, 'date', '1885', '1885', 0.92, 'scope_and_content', 'pattern'),
(837, 'date', '1920', '1920', 0.88, 'scope_and_content', 'pattern'),
(837, 'event', 'Great Trek', 'great trek', 0.80, 'scope_and_content', 'ner'),
(837, 'work', 'Engelbrecht Family Bible', 'engelbrecht family bible', 0.99, 'title', 'manual');

-- Object 845: Hendrik and Debora Engelbrecht
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(845, 'person', 'Hendrik Engelbrecht', 'hendrik engelbrecht', 0.99, 'title', 'manual'),
(845, 'person', 'Debora Engelbrecht', 'debora engelbrecht', 0.99, 'title', 'manual'),
(845, 'person', 'Rosa Engelbrecht', 'rosa engelbrecht', 0.88, 'scope_and_content', 'manual'),
(845, 'organization', 'Dutch Reformed Church', 'dutch reformed church', 0.85, 'scope_and_content', 'ner'),
(845, 'place', 'Pretoria', 'pretoria', 0.95, 'scope_and_content', 'ner'),
(845, 'place', 'Transvaal', 'transvaal', 0.92, 'scope_and_content', 'ner'),
(845, 'place', 'South Africa', 'south africa', 0.99, 'scope_and_content', 'ner'),
(845, 'date', '1860', '1860', 0.88, 'scope_and_content', 'pattern'),
(845, 'date', '1885', '1885', 0.90, 'scope_and_content', 'pattern'),
(845, 'event', 'Anglo-Boer War', 'anglo-boer war', 0.82, 'scope_and_content', 'ner');

-- ============================================================================
-- CLUSTER 3: Karoo & South African Documentary Heritage
-- Objects: 902405 (Beaufort-Wes), 902412 (Call of the Karroo), 878 (Binneman)
-- ============================================================================

-- Object 902405: BEAUTIFUL IN BEAUFORT-WES
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(902405, 'place', 'Beaufort West', 'beaufort west', 0.99, 'title', 'ner'),
(902405, 'place', 'Great Karoo', 'great karoo', 0.97, 'scope_and_content', 'ner'),
(902405, 'place', 'Cape Town', 'cape town', 0.95, 'scope_and_content', 'ner'),
(902405, 'place', 'Johannesburg', 'johannesburg', 0.93, 'scope_and_content', 'ner'),
(902405, 'place', 'South Africa', 'south africa', 0.99, 'scope_and_content', 'ner'),
(902405, 'place', 'Nelspoort', 'nelspoort', 0.88, 'scope_and_content', 'ner'),
(902405, 'person', 'Chris Barnard', 'chris barnard', 0.85, 'scope_and_content', 'ner'),
(902405, 'organization', 'Karoo National Park', 'karoo national park', 0.90, 'scope_and_content', 'ner'),
(902405, 'organization', 'South African National Roads Agency', 'south african national roads agency', 0.82, 'scope_and_content', 'ner'),
(902405, 'date', '2006', '2006', 0.95, 'title', 'pattern'),
(902405, 'event', 'First Heart Transplant', 'first heart transplant', 0.80, 'scope_and_content', 'ner'),
(902405, 'work', 'Beautiful in Beaufort-Wes', 'beautiful in beaufort-wes', 0.99, 'title', 'pattern');

-- Object 902412: THE CALL OF THE KARROO
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(902412, 'place', 'Karoo', 'karoo', 0.99, 'title', 'ner'),
(902412, 'place', 'Great Karoo', 'great karoo', 0.95, 'scope_and_content', 'ner'),
(902412, 'place', 'Beaufort West', 'beaufort west', 0.85, 'scope_and_content', 'ner'),
(902412, 'place', 'South Africa', 'south africa', 0.99, 'scope_and_content', 'ner'),
(902412, 'place', 'Cape Colony', 'cape colony', 0.80, 'scope_and_content', 'ner'),
(902412, 'organization', 'South African Broadcasting Corporation', 'south african broadcasting corporation', 0.90, 'scope_and_content', 'ner'),
(902412, 'date', '1954', '1954', 0.95, 'title', 'pattern'),
(902412, 'event', 'Sheep Farming in the Karoo', 'sheep farming in the karoo', 0.82, 'scope_and_content', 'ner'),
(902412, 'work', 'The Call of the Karroo', 'call of the karroo', 0.99, 'title', 'pattern');

-- Object 878: Binneman Fonds
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(878, 'person', 'Binneman Family', 'binneman family', 0.98, 'creator', 'taxonomy'),
(878, 'place', 'South Africa', 'south africa', 0.99, 'scope_and_content', 'ner'),
(878, 'place', 'Cape Colony', 'cape colony', 0.85, 'scope_and_content', 'ner'),
(878, 'place', 'Graaff-Reinet', 'graaff-reinet', 0.88, 'scope_and_content', 'ner'),
(878, 'place', 'Great Karoo', 'great karoo', 0.82, 'scope_and_content', 'ner'),
(878, 'date', '1908', '1908', 0.95, 'date', 'pattern'),
(878, 'organization', 'Dutch Reformed Church', 'dutch reformed church', 0.80, 'scope_and_content', 'ner'),
(878, 'event', 'Anglo-Boer War', 'anglo-boer war', 0.78, 'scope_and_content', 'ner');

-- ============================================================================
-- CLUSTER 4: Ancient Egyptian Artefacts (Museum collection)
-- Objects: 905191 (Thoth), 905207 (Rosetta Stone), 905228 (Egyptian Boat),
--          905245 (Ram of Amun), 900853 (Thoth c.644 BCE)
-- ============================================================================

-- Object 905207: The Rosetta Stone
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(905207, 'person', 'Ptolemy V', 'ptolemy v', 0.97, 'scope_and_content', 'ner'),
(905207, 'person', 'Jean-François Champollion', 'jean-françois champollion', 0.92, 'scope_and_content', 'ner'),
(905207, 'person', 'Napoleon Bonaparte', 'napoleon bonaparte', 0.88, 'scope_and_content', 'ner'),
(905207, 'organization', 'British Museum', 'british museum', 0.99, 'repository', 'taxonomy'),
(905207, 'organization', 'French Army', 'french army', 0.85, 'scope_and_content', 'ner'),
(905207, 'place', 'Egypt', 'egypt', 0.99, 'scope_and_content', 'ner'),
(905207, 'place', 'Rosetta', 'rosetta', 0.97, 'title', 'ner'),
(905207, 'place', 'Memphis', 'memphis', 0.90, 'scope_and_content', 'ner'),
(905207, 'place', 'London', 'london', 0.95, 'scope_and_content', 'ner'),
(905207, 'place', 'Paris', 'paris', 0.85, 'scope_and_content', 'ner'),
(905207, 'date', '196 BCE', '196 bce', 0.95, 'scope_and_content', 'ner'),
(905207, 'date', '1799', '1799', 0.93, 'scope_and_content', 'ner'),
(905207, 'date', '1822', '1822', 0.90, 'scope_and_content', 'ner'),
(905207, 'event', 'Decipherment of Hieroglyphs', 'decipherment of hieroglyphs', 0.95, 'scope_and_content', 'ner'),
(905207, 'event', 'French Expedition to Egypt', 'french expedition to egypt', 0.88, 'scope_and_content', 'ner'),
(905207, 'work', 'The Rosetta Stone', 'rosetta stone', 0.99, 'title', 'manual');

-- Object 905191: Thoth (bronze figure)
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(905191, 'person', 'Thoth', 'thoth', 0.95, 'title', 'ner'),
(905191, 'organization', 'Minneapolis Institute of Art', 'minneapolis institute of art', 0.99, 'repository', 'taxonomy'),
(905191, 'place', 'Egypt', 'egypt', 0.99, 'scope_and_content', 'ner'),
(905191, 'place', 'Nile Delta', 'nile delta', 0.85, 'scope_and_content', 'ner'),
(905191, 'date', 'c. 664 BCE - 30 CE', 'c. 664 bce - 30 ce', 0.90, 'date', 'pattern'),
(905191, 'event', 'Saite Dynasty', 'saite dynasty', 0.88, 'scope_and_content', 'ner'),
(905191, 'work', 'Bronze Figure of Thoth', 'bronze figure of thoth', 0.95, 'title', 'manual');

-- Object 900853: Thoth, c. 644 BCE - 30 CE
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(900853, 'person', 'Thoth', 'thoth', 0.95, 'title', 'ner'),
(900853, 'place', 'Egypt', 'egypt', 0.99, 'scope_and_content', 'ner'),
(900853, 'place', 'Memphis', 'memphis', 0.82, 'scope_and_content', 'ner'),
(900853, 'place', 'Nile Delta', 'nile delta', 0.80, 'scope_and_content', 'ner'),
(900853, 'organization', 'Minneapolis Institute of Art', 'minneapolis institute of art', 0.95, 'repository', 'taxonomy'),
(900853, 'date', 'c. 664 BCE - 30 CE', 'c. 664 bce - 30 ce', 0.92, 'date', 'pattern'),
(900853, 'event', 'Saite Dynasty', 'saite dynasty', 0.85, 'scope_and_content', 'ner');

-- Object 905228: Egyptian Boat
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(905228, 'place', 'Egypt', 'egypt', 0.99, 'scope_and_content', 'ner'),
(905228, 'place', 'Nile', 'nile', 0.95, 'scope_and_content', 'ner'),
(905228, 'place', 'Thebes', 'thebes', 0.88, 'scope_and_content', 'ner'),
(905228, 'place', 'London', 'london', 0.85, 'scope_and_content', 'ner'),
(905228, 'organization', 'British Museum', 'british museum', 0.99, 'repository', 'taxonomy'),
(905228, 'date', '12th Dynasty', '12th dynasty', 0.93, 'scope_and_content', 'ner'),
(905228, 'date', 'c. 1985-1795 BCE', 'c. 1985-1795 bce', 0.88, 'scope_and_content', 'ner'),
(905228, 'event', 'Middle Kingdom', 'middle kingdom', 0.90, 'scope_and_content', 'ner'),
(905228, 'work', 'Model Funeral Barge', 'model funeral barge', 0.95, 'title', 'manual');

-- Object 905245: Statue of the ram of Amun
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(905245, 'person', 'Taharqo', 'taharqo', 0.95, 'scope_and_content', 'ner'),
(905245, 'place', 'Egypt', 'egypt', 0.99, 'scope_and_content', 'ner'),
(905245, 'place', 'Kawa', 'kawa', 0.90, 'scope_and_content', 'ner'),
(905245, 'place', 'Nubia', 'nubia', 0.88, 'scope_and_content', 'ner'),
(905245, 'place', 'London', 'london', 0.85, 'scope_and_content', 'ner'),
(905245, 'organization', 'British Museum', 'british museum', 0.99, 'repository', 'taxonomy'),
(905245, 'date', '25th Dynasty', '25th dynasty', 0.93, 'scope_and_content', 'ner'),
(905245, 'date', 'c. 680 BCE', 'c. 680 bce', 0.88, 'scope_and_content', 'ner'),
(905245, 'event', 'Nubian Conquest of Egypt', 'nubian conquest of egypt', 0.85, 'scope_and_content', 'ner'),
(905245, 'work', 'Statue of the Ram of Amun', 'statue of ram of amun', 0.95, 'title', 'manual');

-- ============================================================================
-- CLUSTER 5: African Art & Ethnography
-- Objects: 905174 (Kagle mask), 905268 (Railing pillar)
-- ============================================================================

-- Object 905174: Kagle mask
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(905174, 'person', 'William Siegmann', 'william siegmann', 0.90, 'scope_and_content', 'ner'),
(905174, 'organization', 'Minneapolis Institute of Art', 'minneapolis institute of art', 0.99, 'repository', 'taxonomy'),
(905174, 'place', 'Liberia', 'liberia', 0.93, 'scope_and_content', 'ner'),
(905174, 'place', 'West Africa', 'west africa', 0.95, 'scope_and_content', 'ner'),
(905174, 'place', 'Ivory Coast', 'ivory coast', 0.88, 'scope_and_content', 'ner'),
(905174, 'event', 'Dan Masquerade Tradition', 'dan masquerade tradition', 0.85, 'scope_and_content', 'ner'),
(905174, 'work', 'Kagle Mask', 'kagle mask', 0.99, 'title', 'manual');

-- Object 905268: Railing pillar
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(905268, 'place', 'India', 'india', 0.97, 'scope_and_content', 'ner'),
(905268, 'place', 'Mathura', 'mathura', 0.90, 'scope_and_content', 'ner'),
(905268, 'place', 'Gandhara', 'gandhara', 0.82, 'scope_and_content', 'ner'),
(905268, 'organization', 'Minneapolis Institute of Art', 'minneapolis institute of art', 0.99, 'repository', 'taxonomy'),
(905268, 'date', '2nd century CE', '2nd century ce', 0.88, 'scope_and_content', 'ner'),
(905268, 'event', 'Kushan Period', 'kushan period', 0.85, 'scope_and_content', 'ner'),
(905268, 'work', 'Railing Pillar', 'railing pillar', 0.95, 'title', 'manual');

-- ============================================================================
-- CLUSTER 6: Maritime & European History
-- Objects: 902722 (AI Test 25 - Vasa), 902316 (AI Test 20 - Medieval)
-- ============================================================================

-- Object 902722: AI Test 25 - Vasa warship
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(902722, 'person', 'Gustav II Adolf', 'gustav ii adolf', 0.95, 'scope_and_content', 'ner'),
(902722, 'person', 'Anders Franzén', 'anders franzén', 0.90, 'scope_and_content', 'ner'),
(902722, 'organization', 'Vasa Museum', 'vasa museum', 0.95, 'scope_and_content', 'ner'),
(902722, 'organization', 'Swedish Navy', 'swedish navy', 0.90, 'scope_and_content', 'ner'),
(902722, 'place', 'Stockholm', 'stockholm', 0.98, 'scope_and_content', 'ner'),
(902722, 'place', 'Sweden', 'sweden', 0.99, 'scope_and_content', 'ner'),
(902722, 'place', 'Baltic Sea', 'baltic sea', 0.90, 'scope_and_content', 'ner'),
(902722, 'date', '10 August 1628', '10 august 1628', 0.97, 'scope_and_content', 'ner'),
(902722, 'date', '1961', '1961', 0.93, 'scope_and_content', 'ner'),
(902722, 'event', 'Sinking of the Vasa', 'sinking of the vasa', 0.97, 'scope_and_content', 'ner'),
(902722, 'event', 'Recovery of the Vasa', 'recovery of the vasa', 0.93, 'scope_and_content', 'ner'),
(902722, 'work', 'The Vasa Warship', 'vasa warship', 0.99, 'title', 'manual');

-- Object 902316: AI Test 20 - Medieval manuscripts / Hundred Years War
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(902316, 'person', 'Edward III', 'edward iii', 0.93, 'scope_and_content', 'ner'),
(902316, 'person', 'Philip VI', 'philip vi', 0.90, 'scope_and_content', 'ner'),
(902316, 'person', 'Joan of Arc', 'joan of arc', 0.95, 'scope_and_content', 'ner'),
(902316, 'organization', 'British Museum', 'british museum', 0.85, 'scope_and_content', 'ner'),
(902316, 'organization', 'Bibliothèque nationale de France', 'bibliothèque nationale de france', 0.88, 'scope_and_content', 'ner'),
(902316, 'place', 'France', 'france', 0.99, 'scope_and_content', 'ner'),
(902316, 'place', 'England', 'england', 0.98, 'scope_and_content', 'ner'),
(902316, 'place', 'Crécy', 'crécy', 0.95, 'scope_and_content', 'ner'),
(902316, 'place', 'Paris', 'paris', 0.93, 'scope_and_content', 'ner'),
(902316, 'place', 'London', 'london', 0.90, 'scope_and_content', 'ner'),
(902316, 'place', 'Orléans', 'orléans', 0.88, 'scope_and_content', 'ner'),
(902316, 'date', '15 March 1347', '15 march 1347', 0.93, 'scope_and_content', 'ner'),
(902316, 'date', '1337', '1337', 0.90, 'scope_and_content', 'ner'),
(902316, 'date', '1453', '1453', 0.90, 'scope_and_content', 'ner'),
(902316, 'event', 'Battle of Crécy', 'battle of crécy', 0.95, 'scope_and_content', 'ner'),
(902316, 'event', 'Hundred Years War', 'hundred years war', 0.97, 'scope_and_content', 'ner'),
(902316, 'event', 'Siege of Orléans', 'siege of orléans', 0.88, 'scope_and_content', 'ner'),
(902316, 'work', 'Froissart Chronicles', 'froissart chronicles', 0.85, 'scope_and_content', 'ner');

-- ============================================================================
-- CLUSTER 7: Music & Cultural Heritage
-- Objects: 901253 (AI Test 9 - Mozart), 903586 (MP3 audio)
-- ============================================================================

-- Object 901253: AI Test 9 - Mozart
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(901253, 'person', 'Wolfgang Amadeus Mozart', 'wolfgang amadeus mozart', 0.99, 'scope_and_content', 'ner'),
(901253, 'person', 'Leopold Mozart', 'leopold mozart', 0.90, 'scope_and_content', 'ner'),
(901253, 'person', 'Antonio Salieri', 'antonio salieri', 0.85, 'scope_and_content', 'ner'),
(901253, 'person', 'Ludwig van Beethoven', 'ludwig van beethoven', 0.80, 'scope_and_content', 'ner'),
(901253, 'organization', 'Salzburg Court', 'salzburg court', 0.88, 'scope_and_content', 'ner'),
(901253, 'place', 'Salzburg', 'salzburg', 0.98, 'scope_and_content', 'ner'),
(901253, 'place', 'Vienna', 'vienna', 0.97, 'scope_and_content', 'ner'),
(901253, 'place', 'Prague', 'prague', 0.85, 'scope_and_content', 'ner'),
(901253, 'date', '27 January 1756', '27 january 1756', 0.95, 'scope_and_content', 'ner'),
(901253, 'date', '5 December 1791', '5 december 1791', 0.95, 'scope_and_content', 'ner'),
(901253, 'event', 'Premiere of Don Giovanni', 'premiere of don giovanni', 0.85, 'scope_and_content', 'ner'),
(901253, 'work', 'The Magic Flute', 'magic flute', 0.90, 'scope_and_content', 'ner'),
(901253, 'work', 'Don Giovanni', 'don giovanni', 0.88, 'scope_and_content', 'ner'),
(901253, 'work', 'Requiem in D minor', 'requiem in d minor', 0.85, 'scope_and_content', 'ner');

-- Object 903586: MP3 - Rivonia audio
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(903586, 'person', 'Nelson Mandela', 'nelson mandela', 0.90, 'scope_and_content', 'ner'),
(903586, 'person', 'Walter Sisulu', 'walter sisulu', 0.85, 'scope_and_content', 'ner'),
(903586, 'organization', 'African National Congress', 'african national congress', 0.90, 'scope_and_content', 'ner'),
(903586, 'place', 'Rivonia', 'rivonia', 0.95, 'scope_and_content', 'ner'),
(903586, 'place', 'Johannesburg', 'johannesburg', 0.88, 'scope_and_content', 'ner'),
(903586, 'place', 'South Africa', 'south africa', 0.99, 'scope_and_content', 'ner'),
(903586, 'date', '1963', '1963', 0.93, 'scope_and_content', 'ner'),
(903586, 'event', 'Rivonia Trial', 'rivonia trial', 0.97, 'scope_and_content', 'ner');

-- ============================================================================
-- CLUSTER 8: South African Books & Literature
-- Objects: 900589, 900603, 900604, 900605, 900860
-- ============================================================================

-- Object 900603: Great Russian Short Stories
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(900603, 'person', 'Anton Chekhov', 'anton chekhov', 0.93, 'scope_and_content', 'ner'),
(900603, 'person', 'Leo Tolstoy', 'leo tolstoy', 0.92, 'scope_and_content', 'ner'),
(900603, 'person', 'Fyodor Dostoevsky', 'fyodor dostoevsky', 0.90, 'scope_and_content', 'ner'),
(900603, 'place', 'Russia', 'russia', 0.98, 'scope_and_content', 'ner'),
(900603, 'place', 'Moscow', 'moscow', 0.90, 'scope_and_content', 'ner'),
(900603, 'place', 'St. Petersburg', 'st. petersburg', 0.88, 'scope_and_content', 'ner'),
(900603, 'date', '19th century', '19th century', 0.85, 'scope_and_content', 'ner'),
(900603, 'work', 'Great Russian Short Stories', 'great russian short stories', 0.99, 'title', 'pattern');

-- Object 900860: Great French Short Stories
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(900860, 'person', 'Guy de Maupassant', 'guy de maupassant', 0.93, 'scope_and_content', 'ner'),
(900860, 'person', 'Émile Zola', 'émile zola', 0.90, 'scope_and_content', 'ner'),
(900860, 'person', 'Honoré de Balzac', 'honoré de balzac', 0.88, 'scope_and_content', 'ner'),
(900860, 'place', 'France', 'france', 0.98, 'scope_and_content', 'ner'),
(900860, 'place', 'Paris', 'paris', 0.95, 'scope_and_content', 'ner'),
(900860, 'date', '19th century', '19th century', 0.85, 'scope_and_content', 'ner'),
(900860, 'work', 'Great French Short Stories', 'great french short stories', 0.99, 'title', 'pattern');

-- ============================================================================
-- CLUSTER 9: Cross-linking entities for rich graph connections
-- Objects: 900186 (The Test Collection), 1162 (CCO museum), 1338 (3D object)
-- ============================================================================

-- Object 900186: The Test Collection (multi-creator, bridging SA clusters)
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(900186, 'person', 'Binneman Family', 'binneman family', 0.95, 'creator', 'taxonomy'),
(900186, 'person', 'Hendrik Engelbrecht', 'hendrik engelbrecht', 0.85, 'scope_and_content', 'ner'),
(900186, 'organization', 'Cape Archives Trust', 'cape archives trust', 0.90, 'creator', 'taxonomy'),
(900186, 'organization', 'Dutch Reformed Church', 'dutch reformed church', 0.82, 'scope_and_content', 'ner'),
(900186, 'place', 'Cape Town', 'cape town', 0.93, 'scope_and_content', 'ner'),
(900186, 'place', 'South Africa', 'south africa', 0.99, 'scope_and_content', 'ner'),
(900186, 'place', 'Pretoria', 'pretoria', 0.88, 'scope_and_content', 'ner'),
(900186, 'date', '1900', '1900', 0.85, 'scope_and_content', 'ner'),
(900186, 'event', 'Anglo-Boer War', 'anglo-boer war', 0.80, 'scope_and_content', 'ner');

-- Object 1162: CCO Museum item (bridging museum + SA clusters)
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(1162, 'organization', 'Minneapolis Institute of Art', 'minneapolis institute of art', 0.85, 'scope_and_content', 'ner'),
(1162, 'organization', 'British Museum', 'british museum', 0.82, 'scope_and_content', 'ner'),
(1162, 'place', 'South Africa', 'south africa', 0.80, 'scope_and_content', 'ner'),
(1162, 'place', 'London', 'london', 0.85, 'scope_and_content', 'ner'),
(1162, 'event', 'Cataloging Cultural Objects Standard', 'cataloging cultural objects standard', 0.90, 'scope_and_content', 'ner'),
(1162, 'work', 'CCO Standard Implementation', 'cco standard implementation', 0.95, 'title', 'manual');

-- Object 1338: 3D Object (bridge to museum)
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(1338, 'person', 'Heinrich Mueller', 'heinrich mueller', 0.90, 'creator', 'taxonomy'),
(1338, 'organization', 'British Museum', 'british museum', 0.80, 'scope_and_content', 'ner'),
(1338, 'place', 'Egypt', 'egypt', 0.85, 'scope_and_content', 'ner'),
(1338, 'place', 'London', 'london', 0.82, 'scope_and_content', 'ner');

-- ============================================================================
-- CLUSTER 10: DAM / Digital Photography (connecting digital objects)
-- Objects: 900663 (AI), 900523 (Nature), 900532 (Wildlife)
-- ============================================================================

-- Object 900663: Artificial intelligence
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(900663, 'person', 'Alan Turing', 'alan turing', 0.88, 'scope_and_content', 'ner'),
(900663, 'person', 'John McCarthy', 'john mccarthy', 0.85, 'scope_and_content', 'ner'),
(900663, 'organization', 'MIT', 'mit', 0.82, 'scope_and_content', 'ner'),
(900663, 'place', 'England', 'england', 0.80, 'scope_and_content', 'ner'),
(900663, 'date', '1950', '1950', 0.85, 'scope_and_content', 'ner'),
(900663, 'event', 'Turing Test', 'turing test', 0.88, 'scope_and_content', 'ner'),
(900663, 'work', 'Computing Machinery and Intelligence', 'computing machinery and intelligence', 0.82, 'scope_and_content', 'ner');

-- Object 900523: Nature
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(900523, 'place', 'South Africa', 'south africa', 0.95, 'scope_and_content', 'ner'),
(900523, 'place', 'Kruger National Park', 'kruger national park', 0.90, 'scope_and_content', 'ner'),
(900523, 'place', 'Great Karoo', 'great karoo', 0.80, 'scope_and_content', 'ner'),
(900523, 'organization', 'South African National Parks', 'south african national parks', 0.88, 'scope_and_content', 'ner'),
(900523, 'event', 'Wildlife Conservation', 'wildlife conservation', 0.82, 'scope_and_content', 'ner');

-- Object 900532: Wildlife
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(900532, 'place', 'South Africa', 'south africa', 0.95, 'scope_and_content', 'ner'),
(900532, 'place', 'Kruger National Park', 'kruger national park', 0.92, 'scope_and_content', 'ner'),
(900532, 'organization', 'South African National Parks', 'south african national parks', 0.90, 'scope_and_content', 'ner'),
(900532, 'event', 'Wildlife Conservation', 'wildlife conservation', 0.85, 'scope_and_content', 'ner');

-- ============================================================================
-- CLUSTER 11: Library / Books (connecting to SA literary heritage)
-- Objects: 900596 (Harry Potter), 900589 (Nutritional healing), 902707 (Library)
-- ============================================================================

-- Object 902707: Main Library
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(902707, 'organization', 'South African Library', 'south african library', 0.90, 'scope_and_content', 'ner'),
(902707, 'organization', 'Cape Archives Trust', 'cape archives trust', 0.85, 'scope_and_content', 'ner'),
(902707, 'place', 'Cape Town', 'cape town', 0.95, 'scope_and_content', 'ner'),
(902707, 'place', 'South Africa', 'south africa', 0.99, 'scope_and_content', 'ner'),
(902707, 'date', '1818', '1818', 0.82, 'scope_and_content', 'ner'),
(902707, 'event', 'Establishment of South African Library', 'establishment of south african library', 0.80, 'scope_and_content', 'ner');

-- ============================================================================
-- CLUSTER 12: Ingest test / additional bridging for graph density
-- Objects: 905977 (test ingest)
-- ============================================================================

-- Object 905977: test ingest
INSERT INTO heritage_entity_cache (object_id, entity_type, entity_value, normalized_value, confidence_score, source_field, extraction_method) VALUES
(905977, 'place', 'South Africa', 'south africa', 0.95, 'scope_and_content', 'ner'),
(905977, 'place', 'Pretoria', 'pretoria', 0.88, 'scope_and_content', 'ner'),
(905977, 'organization', 'South African Heritage Foundation', 'south african heritage foundation', 0.90, 'scope_and_content', 'ner'),
(905977, 'organization', 'African National Congress', 'african national congress', 0.78, 'scope_and_content', 'ner'),
(905977, 'person', 'Nelson Mandela', 'nelson mandela', 0.80, 'scope_and_content', 'ner'),
(905977, 'event', 'First Democratic Election', 'first democratic election', 0.78, 'scope_and_content', 'ner'),
(905977, 'date', '27 April 1994', '27 april 1994', 0.80, 'scope_and_content', 'ner');
