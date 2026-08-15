-- ahgSiteRecordPlugin - controlled vocabularies
--
-- Every list the legacy rock_forms application hardcoded in its HTML becomes a
-- row here, so it can be edited in AHG Settings > Dropdown Manager instead of in
-- a template. Values are taken from the RARI form; they are configuration from
-- here on, and an institution recording something other than rock art is expected
-- to change them.
--
-- Filed under the heritage_monuments section, which is where the Dropdown Manager
-- already groups site and monument vocabularies.
--
-- INSERT IGNORE throughout: the unique key is (taxonomy, code), so re-running the
-- seed never disturbs a value an institution has since edited.

-- Region and sub-region replace the legacy Province and District fields. Seeded
-- with the South African provinces because that is what RARI records, but the
-- field names carry no country, so another deployment reseeds its own.
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`) VALUES
('site_region', 'Site region', 'heritage_monuments', 'eastern_cape',  'Eastern Cape',  10),
('site_region', 'Site region', 'heritage_monuments', 'free_state',    'Free State',    20),
('site_region', 'Site region', 'heritage_monuments', 'gauteng',       'Gauteng',       30),
('site_region', 'Site region', 'heritage_monuments', 'kwazulu_natal', 'KwaZulu-Natal', 40),
('site_region', 'Site region', 'heritage_monuments', 'limpopo',       'Limpopo',       50),
('site_region', 'Site region', 'heritage_monuments', 'mpumalanga',    'Mpumalanga',    60),
('site_region', 'Site region', 'heritage_monuments', 'northern_cape', 'Northern Cape', 70),
('site_region', 'Site region', 'heritage_monuments', 'north_west',    'North West',    80),
('site_region', 'Site region', 'heritage_monuments', 'western_cape',  'Western Cape',  90);

-- Cultural tradition.
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`) VALUES
('site_tradition', 'Site tradition', 'heritage_monuments', 'san',       'San',        10),
('site_tradition', 'Site tradition', 'heritage_monuments', 'khoekhoen', 'Khoekhoen',  20),
('site_tradition', 'Site tradition', 'heritage_monuments', 'bantu',     'Bantu',      30),
('site_tradition', 'Site tradition', 'heritage_monuments', 'other',     'Other',      90);

-- Site type. "overhang" is here deliberately: it existed in the legacy form but
-- was missing from the processing map, so selecting it silently discarded the
-- value. As a row it cannot go missing.
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`) VALUES
('site_type', 'Site type', 'heritage_monuments', 'cave',      'Cave',      10),
('site_type', 'Site type', 'heritage_monuments', 'shelter',   'Shelter',   20),
('site_type', 'Site type', 'heritage_monuments', 'rock_face', 'Rock face', 30),
('site_type', 'Site type', 'heritage_monuments', 'boulder',   'Boulder',   40),
('site_type', 'Site type', 'heritage_monuments', 'overhang',  'Overhang',  50),
('site_type', 'Site type', 'heritage_monuments', 'open',      'Open',      60);

-- Observed damage.
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`) VALUES
('site_damage', 'Site damage', 'heritage_monuments', 'water',      'Water',      10),
('site_damage', 'Site damage', 'heritage_monuments', 'lichen',     'Lichen',     20),
('site_damage', 'Site damage', 'heritage_monuments', 'salts',      'Salts',      30),
('site_damage', 'Site damage', 'heritage_monuments', 'dust',       'Dust',       40),
('site_damage', 'Site damage', 'heritage_monuments', 'animals',    'Animals',    50),
('site_damage', 'Site damage', 'heritage_monuments', 'flaking',    'Flaking',    60),
('site_damage', 'Site damage', 'heritage_monuments', 'klipsweet',  'Klipsweet',  70),
('site_damage', 'Site damage', 'heritage_monuments', 'graffiti',   'Graffiti',   80),
('site_damage', 'Site damage', 'heritage_monuments', 'vegetation', 'Vegetation', 90);

-- Surface contents.
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`) VALUES
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'stone_tools',       'Stone tools',        10),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'pottery',           'Pottery',            20),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'marine_shells',     'Marine shells',      30),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'glass',             'Glass',              40),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'ochre',             'Ochre',              50),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'stone_walling',     'Stone walling',      60),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'bones',             'Bones',              70),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'ostrich_egg_shell', 'Ostrich egg shells', 80),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'ash',               'Ash',                90),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'metals',            'Metals',            100),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'grindstone',        'Grindstone',        110),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'beads',             'Beads',             120),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'bedding',           'Bedding',           130),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'dung',              'Dung',              140),
('site_surface_content', 'Site surface contents', 'heritage_monuments', 'other',             'Other',             900);

-- Excavation potential.
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`) VALUES
('site_excavation_potential', 'Site excavation potential', 'heritage_monuments', 'high',   'High',   10),
('site_excavation_potential', 'Site excavation potential', 'heritage_monuments', 'medium', 'Medium', 20),
('site_excavation_potential', 'Site excavation potential', 'heritage_monuments', 'low',    'Low',    30);

-- Mineral and rock contents. "silcrete" is seeded with a real label: the legacy
-- processing map gave it an empty string, so it stored a blank.
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`) VALUES
('site_mineral_content', 'Site mineral and rock contents', 'heritage_monuments', 'quartz',    'Quartz',    10),
('site_mineral_content', 'Site mineral and rock contents', 'heritage_monuments', 'quartzite', 'Quartzite', 20),
('site_mineral_content', 'Site mineral and rock contents', 'heritage_monuments', 'chert',     'Chert',     30),
('site_mineral_content', 'Site mineral and rock contents', 'heritage_monuments', 'hornfels',  'Hornfels',  40),
('site_mineral_content', 'Site mineral and rock contents', 'heritage_monuments', 'ccs',       'CCS',       50),
('site_mineral_content', 'Site mineral and rock contents', 'heritage_monuments', 'silcrete',  'Silcrete',  60);

-- Deposit depth and contents.
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`) VALUES
('site_deposit_depth', 'Site deposit depth', 'heritage_monuments', '0_10cm',   '0-10 cm',   10),
('site_deposit_depth', 'Site deposit depth', 'heritage_monuments', '10_20cm',  '10-20 cm',  20),
('site_deposit_depth', 'Site deposit depth', 'heritage_monuments', '20_50cm',  '20-50 cm+', 30);

INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`) VALUES
('site_deposit_content', 'Site deposit contents', 'heritage_monuments', 'esa',        'ESA',        10),
('site_deposit_content', 'Site deposit contents', 'heritage_monuments', 'msa',        'MSA',        20),
('site_deposit_content', 'Site deposit contents', 'heritage_monuments', 'lsa',        'LSA',        30),
('site_deposit_content', 'Site deposit contents', 'heritage_monuments', 'burial',     'Burial',     40),
('site_deposit_content', 'Site deposit contents', 'heritage_monuments', 'historical', 'Historical', 50),
('site_deposit_content', 'Site deposit contents', 'heritage_monuments', 'other',      'Other',      900);

-- Aspect. Free text in the legacy form, which made it unreportable.
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`) VALUES
('site_aspect', 'Site aspect', 'heritage_monuments', 'n',  'North',      10),
('site_aspect', 'Site aspect', 'heritage_monuments', 'ne', 'North-east', 20),
('site_aspect', 'Site aspect', 'heritage_monuments', 'e',  'East',       30),
('site_aspect', 'Site aspect', 'heritage_monuments', 'se', 'South-east', 40),
('site_aspect', 'Site aspect', 'heritage_monuments', 's',  'South',      50),
('site_aspect', 'Site aspect', 'heritage_monuments', 'sw', 'South-west', 60),
('site_aspect', 'Site aspect', 'heritage_monuments', 'w',  'West',       70),
('site_aspect', 'Site aspect', 'heritage_monuments', 'nw', 'North-west', 80),
('site_aspect', 'Site aspect', 'heritage_monuments', 'var','Variable',   90);

-- Recorder role.
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `taxonomy_section`, `code`, `label`, `sort_order`) VALUES
('site_recorder_role', 'Site recorder role', 'heritage_monuments', 'recorder',     'Recorder',     10),
('site_recorder_role', 'Site recorder role', 'heritage_monuments', 'photographer', 'Photographer', 20),
('site_recorder_role', 'Site recorder role', 'heritage_monuments', 'surveyor',     'Surveyor',     30),
('site_recorder_role', 'Site recorder role', 'heritage_monuments', 'assistant',    'Assistant',    40);
