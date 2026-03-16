# ARTIFICIAL INTELLIGENCE AS COLLECTIONS STEWARD: ENABLING INCLUSIVE, SUSTAINABLE MUSEUM COLLECTIONS MANAGEMENT IN THE AFRICAN CONTEXT

Johan Pieterse

The Archive and Heritage Group (Pty) Ltd, South Africa

johan@theahg.co.za

---

**Abstract**

The intersection of artificial intelligence and museum collections management presents both opportunity and challenge for African memory institutions. Drawing on doctoral research in AI-assisted records management and the development of Heratio, an open-source collections management framework for the GLAM sector, this paper examines how AI reshapes collections stewardship in ways relevant to under-resourced African institutions. Four AI applications are presented: AI-powered object description for persons with disabilities, where natural language generation produces contextual descriptions advancing inclusive access; automated condition assessment using computer vision to detect damage typologies aligned with Spectrum 5.0; AI-assisted fixity verification supplementing checksum-based digital preservation; and AI metadata extraction including Named Entity Recognition for automated authority record creation. The paper argues that these capabilities, integrated into open-source infrastructure aligned with African legislative frameworks, can meaningfully close the digital capability gap facing South African museums. It positions AI as an amplifier of specialist knowledge rather than a replacement, enabling smaller institutions to meet international standards while adapting to local realities.

**Keywords:** artificial intelligence; collections management; condition assessment; accessibility; digital preservation; GLAM

---

## 1. Introduction

The research question guiding this paper is: How can artificial intelligence be integrated into open-source collections management infrastructure to address the specific operational challenges of under-resourced African museums, while maintaining alignment with professional standards and legislative compliance? The paper situates itself within the broader discourse on digital transformation in cultural heritage institutions (Cameron and Kenderdine 2007; Marty 2008), while drawing specifically on the emerging literature on AI applications in galleries, libraries, archives, and museums (Padilla 2019; Stein 2014).

South African museums face a persistent paradox. They are custodians of some of the world's most culturally significant collections, yet many operate with staffing levels and technology budgets that would be unrecognisable to their counterparts in Europe or North America. The South African Museums Association's surveys have repeatedly documented the challenges: understaffed registries, paper-based catalogues, uncatalogued backlogs stretching decades, and digital preservation that amounts, in many institutions, to little more than external hard drives stored in cupboards.

This is not a failure of will or knowledge. It is a structural reality shaped by decades of unequal funding, the inherited spatial and institutional geography of apartheid-era cultural policy, and the ongoing tension between the urgent demands of transformation and the quieter, less visible work of collections care. The result is a widening gap between what international standards expect --- comprehensive metadata, condition documentation, digital preservation, inclusive access --- and what most South African museums can practically deliver.

Into this gap, artificial intelligence arrives with both promise and risk. Promise, because AI can automate labour-intensive tasks that currently go undone. Risk, because poorly implemented AI can introduce errors at scale, embed biases into permanent records, and create dependencies on technologies that institutions cannot maintain. The question is not whether AI will enter museum collections management --- it already has --- but whether it can be deployed in ways that genuinely serve African institutions rather than simply replicating solutions designed for well-resourced Western museums.

This paper draws on the author's doctoral research (Pieterse 2026), which investigates the application of artificial intelligence in records management and archives within a South African state-owned company. That study, employing a qualitative exploratory design within an interpretivist paradigm, found that the primary challenge to records accessibility is not the absence of information but the absence of structure, governance, and consistent metadata (Pieterse 2026:194). The research proposed an AI/ML-enabled framework grounded in four complementary theories --- the Technology Acceptance Model, Diffusion of Innovations, Theory of Planned Behaviour, and the Records Continuum Model --- ensuring that human, behavioural, and governance dimensions inform implementation alongside technical capability. The practical output of this research is Heratio, an open-source collections management framework that integrates AI capabilities directly into the workflows that museum professionals use daily.

Heratio is built as a modular extension of the established Access to Memory (AtoM) platform, providing a two-layer architecture: a core framework offering modern database services, routing, and command-line tools, and a plugin layer of over eighty specialised modules covering museum cataloguing, digital preservation, compliance reporting, and AI-powered metadata extraction. The system is deployed across multiple South African institutions, managing collections that span archival fonds, museum objects, library materials, gallery works, and digital assets. This multi-sector scope is deliberate: many African institutions serve multiple collection types, and a system that forces artificial boundaries between archives, museums, and libraries does not serve them well.

The paper proceeds through four substantive sections examining specific AI applications: inclusive object description (Section 3), condition assessment (Section 4), digital preservation fixity (Section 5), and metadata extraction (Section 6). These are preceded by contextual background (Section 2) and followed by implementation reflections (Section 7), ethical considerations (Section 8), and conclusions (Section 9).

## 2. Background

### 2.1 Collections management systems in South African institutions

The landscape of collections management in South African museums is fragmented. A handful of national institutions --- Iziko Museums, Ditsong Museums, the National Museum in Bloemfontein --- operate commercial or semi-commercial systems, though even these institutions report challenges with system maintenance, version upgrades, and staff capacity to use systems fully. Provincial and municipal museums, which collectively hold the majority of South Africa's distributed cultural heritage, frequently rely on spreadsheets, paper registers, or no system at all.

The cost barriers are significant. Commercial collections management systems designed for the GLAM sector --- products such as Axiell Collections, Vernon CMS, or The Museum System --- carry licensing costs that can exceed the annual operating budget of a small South African museum. Even ostensibly free systems require server infrastructure, technical staff for installation and maintenance, and ongoing training. Technology adoption is often opportunistic rather than strategic: a system is installed when grant funding permits, then gradually falls into disuse as the grant period ends and the staff member who understood the system moves on. Zorich (2008) documented this cycle of adoption and abandonment in her survey of digital cultural heritage initiatives, finding that sustainability concerns were the primary barrier to long-term success. In the South African context, where institutional memory is frequently disrupted by staff turnover and restructuring, this cycle is particularly acute.

### 2.2 AI in collections environments

Artificial intelligence, as discussed in this paper, refers not to general artificial intelligence or autonomous decision-making systems, but to specific machine learning and natural language processing techniques applied to defined collections management tasks. These include computer vision (the ability of software to analyse and interpret images), natural language processing (the ability to extract structured information from unstructured text), and large language models (systems that can generate human-readable text from contextual inputs).

The promise of these technologies for museums is straightforward: they can perform, at speed and at scale, tasks that currently require specialist human labour. A computer vision system can scan a thousand photographs for condition damage in the time it takes a conservator to assess a single object. A natural language processing pipeline can extract the names of people, places, and organisations from a box of uncatalogued correspondence in hours rather than weeks.

The risks are equally concrete. AI systems trained on Western museum collections may misidentify or mislabel African cultural objects. Automated descriptions generated without cultural context may flatten or distort the significance of objects. Errors introduced by AI --- a misidentified entity, an incorrect condition rating --- can propagate through systems and become embedded in institutional records. As Padilla (2019) cautions, the application of machine learning in cultural heritage institutions requires 'responsible operations' that acknowledge both the capabilities and limitations of automated systems, and that maintain transparency about the provenance of machine-generated data.

### 2.3 Legislative and standards context

Any AI deployment in South African museum collections must navigate several legislative and standards frameworks. The Protection of Personal Information Act (POPIA, 2013) governs the processing of personal information, directly relevant when AI systems extract names, biographical details, or facial images from collection materials. POPIA requires lawful justification for processing, data minimisation, and appropriate security measures. The National Archives and Records Service of South Africa (NARSSA) sets standards for records management in government institutions, including retention schedules, security classification, and audit trail requirements that AI systems must support rather than circumvent. GRAP 103 (Heritage Assets) requires South African public sector institutions to recognise, measure, and report on heritage assets, and AI-assisted condition assessment and valuation directly support this compliance. Spectrum 5.0, the international standard for museum collections management procedures increasingly adopted in South Africa, defines twenty-one primary procedures, several of which --- condition checking, object entry, location and movement control --- can be enhanced through AI assistance.

## 3. AI for inclusive access: describing objects for persons with disabilities

### 3.1 The accessibility gap in digital collections

The digitisation of museum collections, while expanding access in important ways, has paradoxically created new barriers for persons with disabilities. A high-resolution photograph of a museum object, displayed on a website without adequate descriptive text, is invisible to a visually impaired researcher using a screen reader. The Web Content Accessibility Guidelines (WCAG 2.1) require that all non-text content have text alternatives serving the equivalent purpose. Yet in most museums, alt-text, when it exists at all, is limited to the object title --- 'Ceramic vessel' rather than a description conveying visual characteristics, condition, cultural context, and significance.

The barrier is practical, not conceptual. Writing rich, multi-layered descriptions for every object in a digital collection is extraordinarily labour-intensive. A museum with ten thousand digitised objects would require months of dedicated curatorial time to produce adequate descriptions. The doctoral research found that at a rate of five hundred documents per day --- a reasonable professional pace --- processing 2.5 million items would consume more than four years of full-time staff effort (Pieterse 2026:244). For under-resourced institutions, this work simply does not happen.

### 3.2 AI-generated object descriptions in Heratio

Heratio addresses this gap through an AI description module that generates contextually rich object descriptions using large language models. The system operates on a human-in-the-loop principle: AI generates draft descriptions, which museum staff then review, edit, and approve before publication.

When a description is requested, the system gathers all available contextual information about the object: its title, level of description, the repository holding it, the creator or maker drawn from creation events recorded in the system, date ranges, existing scope and content notes, extent and medium descriptions, archival history, physical characteristics, and any OCR text extracted from associated documents or labels. This contextual bundle is passed to a large language model with a system prompt instructing the model to generate a description following established archival standards.

Heratio supports three categories of language model provider: cloud-based commercial models from Anthropic and OpenAI, and locally hosted open-source models via the Ollama framework, such as Meta's Llama or Mistral. The choice is configurable per institution, allowing organisations with data sovereignty concerns to use entirely local, on-premises AI processing.

The generated description is not immediately applied to the collection record. It is stored as a suggestion with a pending status, awaiting human review. The review interface presents the existing description alongside the AI-generated suggestion, enabling side-by-side comparison. Staff can approve the suggestion as written, edit it before applying, or reject it entirely. Unreviewed suggestions expire automatically after a configurable period, defaulting to thirty days.

The module generates multiple description layers for a single object: visual description (what the object looks like), contextual description (its place in the collection, provenance, cultural significance), and technical description (conservation-relevant information from condition reports and preservation metadata). These layers can be presented selectively: a visually impaired visitor receives the visual description, while a researcher may access all layers.

### 3.3 Ethical considerations in AI-generated descriptions

The generation of descriptions by AI raises questions about authority and accuracy. Models trained predominantly on Western museum descriptions may describe an African ceremonial object in terms prioritising aesthetic qualities over spiritual significance, or use terminology unfamiliar or inappropriate in the object's cultural context. The human-in-the-loop review provides a safeguard, but staff reviewing descriptions must themselves possess the cultural knowledge to identify and correct inappropriate framing. The paper returns to this question in Section 8.

## 4. AI-assisted condition assessment

### 4.1 The conservation staffing crisis

The shortage of trained conservators in South Africa is well documented. Outside a small number of national institutions and university-based conservation programmes, most South African museums have no access to professional conservation services. Objects deteriorate without assessment, damage goes unrecorded, and conservation priorities are set reactively --- responding to visible crises rather than proactively managing collection condition.

Spectrum 5.0 defines condition checking as a primary museum procedure, requiring that the condition of objects be assessed and recorded at key points: on entry to the museum, before and after loan, before and after exhibition, and at regular intervals during storage. For institutions without conservators, meeting this standard is effectively impossible through traditional means.

### 4.2 Computer vision for damage detection

Heratio's AI condition assessment module uses computer vision to analyse photographs of collection objects, identifying and categorising visible damage. The system employs a two-stage approach. The first stage uses a YOLO (You Only Look Once) object detection model trained to identify regions of interest within photographs --- areas that may contain damage. The second stage uses an EfficientNet image classification model to categorise detected damage into specific typologies. The system recognises fifteen damage categories drawn from conservation practice:

- Physical damage: tears, creases, losses, warping, adhesive damage
- Biological damage: mould growth, insect damage, rodent damage
- Chemical damage: fading, foxing, acid burn, staining, yellowing
- Environmental damage: water damage, humidity deterioration, light damage

Each detected instance is recorded with a bounding box identifying its location in the image, a confidence score indicating the system's certainty, and a severity classification: minor (non-structural, not affecting stability), moderate (affecting usability or display suitability), or severe (requiring immediate intervention).

### 4.3 Human-in-the-loop confirmation

The AI condition assessment is explicitly designed as a decision-support tool, not an autonomous assessor. The output is a draft condition report that must be reviewed by museum staff. This human-in-the-loop approach catches errors --- the AI may misidentify a design feature as damage, or fail to recognise visually subtle deterioration --- and simultaneously builds institutional capacity. Staff who regularly review AI-generated assessments develop their own observational skills through structured comparison with the system's findings.

Early deployment experience suggests that the system is most valuable not for objects in obviously good or obviously poor condition, where human assessment is straightforward, but for objects in the middle range where damage may be subtle or where multiple damage types interact. A photograph showing faint foxing alongside possible mould growth, for example, benefits from systematic analysis that identifies both conditions separately, even when a human observer might attend to only the more prominent of the two. The system's consistent attention to all fifteen damage categories provides a completeness check that supplements human observation rather than replacing it.

### 4.4 Integration with collections workflows

Condition data integrates directly with Heratio's broader collections management workflows. Loan management uses condition reports generated before dispatch and after return to document changes during the loan period. Exhibition planning automatically excludes objects flagged with severe damage or active biological infestation. Conservation queuing prioritises objects using a scoring algorithm considering damage severity, object significance, and treatment feasibility. Heritage asset valuation processes required under GRAP 103 draw on condition data. Assessment context types align directly with Spectrum 5.0: acquisition, loan out, loan in, loan return, exhibition, storage, conservation treatment, routine check, incident response, insurance, and deaccession.

## 5. AI and digital preservation fixity

### 5.1 Traditional checksum-based fixity

Digital preservation rests on verifying that digital objects remain unchanged over time. This verification uses checksums --- mathematical fingerprints computed from file content. If today's checksum matches the value recorded at ingest, the file is unchanged; if they differ, corruption or modification has occurred.

Heratio implements checksum-based fixity using four cryptographic algorithms: MD5 and SHA-1 (retained for backward compatibility), SHA-256 (the recommended default), and SHA-512. Checksums are computed at ingest and stored alongside the algorithm used, file size, and verification status. Checks can run on automated schedules or be triggered manually, producing results recorded as PREMIS events creating an immutable audit trail.

### 5.2 Beyond checksums: format risk and integrity assurance

Traditional checksum verification answers a binary question: has this file changed? It does not address whether a file format is at risk of obsolescence, or whether patterns in fixity failures suggest systemic storage problems.

Heratio extends traditional fixity through a preservation format registry cataloguing over two hundred file formats with risk assessments. Each format is classified by risk level --- low, medium, high, or critical --- and assigned a preservation action: none, monitor, migrate, or normalise. Format identification uses Siegfried, matching files against the PRONOM technical registry maintained by The National Archives (UK).

The integrity assurance module adds scheduled verification with escalation. Objects failing three consecutive fixity checks are automatically escalated to a dead-letter queue for human investigation, distinguishing transient storage errors from genuine data loss. Verification schedules support scoping by repository or collection hierarchy, with batch sizes and throttling configurable to institutional server capacity.

### 5.3 Preservation in under-resourced environments

For many South African museums, the primary digital preservation challenge is not algorithm sophistication but the absence of any systematic practice. Digital objects sit on local drives without checksums, format identification, or scheduled integrity checking.

Heratio's approach makes basic preservation automatic. When a digital object enters the system, a SHA-256 checksum is computed without user action. Format identification runs as a background process. Fixity checks execute on schedule. As Harvey and Weatherburn (2018) argue, the greatest risk to digital collections is not the failure of sophisticated preservation strategies but the complete absence of any strategy at all. The most effective AI in under-resourced institutions is often AI that operates quietly in the background, maintaining standards the institution could not maintain manually.

This design philosophy --- making good practice automatic rather than optional --- draws on Conway's (2010) observation that the primary preservation challenge in the digital age is not technical but organisational. Institutions that lack dedicated digital preservation staff need systems that embed preservation into routine workflows rather than requiring separate, specialist processes. Heratio's automated fixity checking exemplifies this principle: the museum professional who ingests a digitised photograph does not need to know what SHA-256 means, but the photograph's integrity is nonetheless protected from that point forward.

## 6. AI metadata extraction and authority control

### 6.1 The metadata backlog

The metadata backlog --- the gap between material held and material adequately described --- is perhaps the most pervasive challenge facing African museums. Collections acquired through donations or transfers arrive with minimal documentation. Items catalogued under now-superseded standards have incomplete descriptions. A single cataloguer working at professional pace describes several hundred complex objects per year. An institution with a backlog of fifty thousand items faces decades of work.

### 6.2 Embedded metadata extraction

For photographic and digital media collections, substantial metadata is already embedded within files. Heratio's metadata extraction module uses ExifTool to harvest EXIF, IPTC, and XMP metadata from digital objects across image, document, and video formats. Extracted data covers camera and capture settings, resolution, colour space, creation dates, keywords, copyright notices, captions, and GPS coordinates. This automated extraction immediately populates catalogue records that would otherwise require manual entry or remain empty.

### 6.3 Named Entity Recognition

Named Entity Recognition (NER) automatically identifies and classifies named entities within unstructured text. Heratio's implementation uses a spaCy-based natural language processing pipeline on a dedicated GPU server, extracting four entity types: persons, organisations, geopolitical entities (places), and dates. Extracted entities are stored with confidence scores; the default threshold for storage is set high (0.95) to minimise false positives, while downstream applications can lower this threshold (0.70) for improved recall.

The practical value for museum collections is substantial. Consider a museum holding a collection of historical correspondence. Manual cataloguing typically records the correspondent and recipient, but names mentioned within the letter text go unindexed. NER processes the full text, extracting every named entity and making the entire corpus searchable --- without requiring a cataloguer to read every document individually.

### 6.4 Automated authority record suggestions

Beyond extraction, Heratio links recognised entities to authority records. When the NER system identifies a person's name, it checks whether an authority record already exists. Matches are linked; unmatched entities generate suggestions for new authority records following ISAAR(CPF) standards, reviewed and confirmed by museum staff. Over time, the authority file grows organically, creating a network of relationships between people, organisations, places, and collection objects.

### 6.5 Multilingual considerations

South Africa's eleven official languages present specific challenges for AI-based extraction. NER models trained primarily on English perform poorly on Afrikaans, isiZulu, or isiXhosa. Heratio addresses multilingual support through Argos Translate, an offline translation framework supporting over two hundred language pairs without sending data to external services, and TrOCR handwritten text recognition operating in specialised modes for general handwriting, dates, digits, and letters. Integration of NLLB-200, Meta's two-hundred-language model, represents a future direction for improving recognition quality in South African languages.

## 7. Implementation realities

### 7.1 Open source as infrastructure strategy

Building Heratio as open-source software is a deliberate infrastructure strategy with implications that extend beyond cost savings. Given and McTavish (2010) document the reconvergence of libraries, archives, and museums in the digital age, arguing that shared infrastructure reduces duplication and enables collaboration. Open-source collections management directly supports this convergence by allowing institutions to modify and extend shared tools for their specific needs.

For African institutions specifically, open-source provides three critical advantages. First, institutional autonomy: an institution using open-source software is not dependent on the commercial viability of a vendor. If the development community shrinks, the institution retains the source code and the right to modify it. This is not a theoretical concern --- South African museums have experienced vendor abandonment, where commercial systems are discontinued leaving data trapped in proprietary formats. Second, adaptability: South African museums operate within legislative frameworks and professional standards that differ from those assumed by systems designed for North American or European markets. Open-source software can be modified to accommodate these requirements without waiting for a vendor to prioritise what it considers a marginal market. Third, capacity building: when institutions can examine, modify, and extend their software, they develop technical capabilities that serve them beyond any single system. This is a form of digital sovereignty with particular resonance in the African post-colonial context.

### 7.2 Infrastructure requirements and data sovereignty

AI workloads require computational resources exceeding typical museum hardware. Heratio uses a split-server architecture: a standard web server for the collections application, and a compute server equipped with a GPU for AI processing. The current deployment uses an NVIDIA RTX 3080, a mid-range consumer graphics card costing approximately ZAR 15,000. Tasks are dispatched asynchronously, meaning AI processing does not slow daily system use.

Under POPIA, transferring personal information across borders requires legal justification. Heratio's default configuration processes all sensitive tasks locally on institution-controlled hardware. NER, face detection, condition assessment, and fixity checking run on-premises. Translation operates entirely offline. Only non-personal text generation tasks are optionally routable to cloud services, with this routing configurable by the institution.

### 7.3 The human-AI collaboration model

The most important lesson from deployment is that AI succeeds not when it replaces human judgement but when it structures and supports it. The doctoral research confirmed this finding empirically: participants 'rejected full automation, in favour of augmented intelligence, where AI accelerates processing, whilst human expertise validates high-risk decisions' (Pieterse 2026:184). Mosqueira-Rey, Alonso-Rios, Bobes-Bascaran and Fernandez-Leal (2023:3005) similarly establish that the most effective AI systems are those integrating human expertise at strategic decision points. In a records management context operating under NARSSA, POPIA, and PAIA, this collaborative model is not optional but a statutory and governance imperative (Pieterse 2026:184).

For museum collections specifically, the stakes are cultural as well as legal. An AI-generated condition report performs a technical function; the staff member who reviews it performs an act of professional responsibility. AI ensures that responsibility is exercised in a structured, consistent, and well-informed way.

## 8. Ethical dimensions

The doctoral research identified that privacy and confidentiality must be treated as design requirements from the outset, 'especially when automated classification and content questioning might be used to consider individual or confidential data' (Pieterse 2026:iv). This principle applies with equal force in the museum context, where AI processing may encounter sensitive cultural material, personal information in donor records, or indigenous knowledge subject to community protocols.

AI language models reproduce the perspectives of their training data. Models trained predominantly on English-language museum documentation will reproduce Western classificatory frameworks and aesthetic valuations. Applied to African cultural objects, these models may prioritise Western aesthetic categories over culturally appropriate terminology, miss significance not apparent from visual inspection, or apply conservation priorities that differ from community values regarding the appropriate state of cultural objects.

Training data provenance raises further questions. As institutions develop local training data by correcting and improving AI outputs, issues of consent arise. Whose descriptions train the model? Do communities whose cultural objects appear in training data have a say in how that data is used? These questions require institutional policies and community engagement, not technical solutions alone. Heratio provides infrastructure to track training data provenance, but governance frameworks must be established by institutions and their stakeholder communities.

Transparency is essential for research integrity. Researchers using museum collections have a right to know whether metadata was generated by a human cataloguer, an AI system, or some combination. A description generated by a large language model, even one reviewed and approved by museum staff, has a different epistemic status from a description written by a specialist curator with decades of experience. Heratio flags AI-generated descriptions with records of the model used, generation date, and the staff member who reviewed the output, allowing researchers to make informed judgements about metadata authority. This transparency aligns with Prescott and Hughes' (2018) argument for 'slow digitisation' --- an approach that values the quality and provenance of digital information over the speed of its production.

## 9. Conclusion

The central argument of this paper is that AI, deployed thoughtfully within open-source infrastructure, can serve as an amplifier of existing institutional capacity rather than a replacement for specialist knowledge. The doctoral research concluded that 'AI/ML is not seen as an alternative to professional records judgement, but as a complement of the records team, allowing them to take back scale, increase transparency and to achieve the same service delivery goals in digital high-volume settings' (Pieterse 2026:vi). A museum with one part-time cataloguer and an AI-assisted system can produce more consistent catalogue records than one with neither. A museum without a conservator but with AI-assisted condition assessment is better positioned to identify conservation priorities.

This amplification is particularly significant for African institutions, where the gap between international standards and institutional capacity is widest. The doctoral research documented that only 6.5% of approximately 2.5 million documents within the studied organisation fell under formal records management control (Pieterse 2026:250). While this figure pertains to a corporate records environment, the parallel to museum collections --- where uncatalogued backlogs routinely exceed catalogued holdings --- is striking. AI does not close this gap entirely --- no technology substitutes for adequate staffing, funding, and institutional support --- but it narrows it meaningfully.

Drawing on the doctoral framework's four-phase implementation model (Pieterse 2026:231), recommendations for South African museums considering AI adoption include: starting with metadata extraction as the lowest-risk, highest-return application (Phase 1: assessment and discovery); adopting human-in-the-loop workflows where AI outputs are never published without professional review; investing in a mid-range GPU server for local processing that is more cost-effective and privacy-compliant than cloud alternatives; establishing an AI governance structure --- even a small one --- before technical deployment begins; engaging with communities in developing culturally appropriate descriptions; and transparently documenting AI provenance in all metadata.

Future directions include multilingual AI models specifically trained on South African languages, 3D object description from photogrammetric scans, community-trained models producing culturally appropriate descriptions, and federated learning enabling collaborative model improvement while preserving institutional data sovereignty. The doctoral research's proposed future research map (Pieterse 2026:266) identifies a progression from immediate framework validation (0--1 year) through multi-institutional testing (1--3 years) to advanced NLP integration (3--5 years) and global benchmarking with AI ethics research (5+ years) --- a trajectory equally applicable to the museum sector.

The future of AI in African museum collections management is not predetermined. It will be shaped by the choices that institutions, professionals, and communities make about how these technologies are governed, deployed, and evaluated. This paper has attempted to demonstrate that those choices can be made wisely, producing tools that genuinely serve the stewardship of Africa's cultural heritage.

---

## References

Bearman, D 2012, 'Representing museum knowledge', in *Museums and the web 2012*, Archives & Museum Informatics, Silver Spring.

Cameron, F & Kenderdine, S (eds) 2007, *Theorizing digital cultural heritage: a critical discourse*, MIT Press, Cambridge.

Collections Trust 2017, *Spectrum 5.0: the UK collections management standard*, Collections Trust, London.

Conway, P 2010, 'Preservation in the age of Google: digitization, digital preservation, and dilemmas', *The library quarterly*, vol. 80, no. 1, pp. 61-79.

Department of Justice and Constitutional Development 2013, *Protection of personal information act 4 of 2013*, Republic of South Africa, Pretoria.

Given, LM & McTavish, L 2010, 'What's old is new again: the reconvergence of libraries, archives, and museums in the digital age', *The library quarterly*, vol. 80, no. 1, pp. 7-32.

Harvey, R & Weatherburn, J 2018, *Preserving digital materials*, 3rd edn, Rowman & Littlefield, Lanham.

International Council on Archives 2000, *ISAD(G): general international standard archival description*, 2nd edn, ICA, Ottawa.

International Council on Archives 2004, *ISAAR(CPF): international standard archival authority record for corporate bodies, persons, and families*, 2nd edn, ICA, Paris.

International Council of Museums 2022, 'Museum definition', adopted at the ICOM extraordinary general assembly, Prague, 24 August.

Library of Congress 2012, *PREMIS data dictionary for preservation metadata*, version 2.2, Library of Congress, Washington.

Marty, PF 2008, 'Museum websites and museum visitors: digital museum resources and their use', *Museum management and curatorship*, vol. 23, no. 1, pp. 81-99.

Mosqueira-Rey, E, Alonso-Rios, D, Bobes-Bascaran, J & Fernandez-Leal, A 2023, 'A human-in-the-loop perspective on autoML: a survey', *Journal of artificial intelligence research*, vol. 77, pp. 2999-3042.

National Treasury, Republic of South Africa 2014, *GRAP 103: heritage assets*, Accounting Standards Board, Pretoria.

Padilla, T 2019, 'Responsible operations: data science, machine learning, and AI in libraries', OCLC research position paper, OCLC, Dublin.

Pieterse, JJ 2026, *Utilising artificial intelligence to enhance records accessibility within a state-owned company in South Africa*, DPhil thesis, University of South Africa, Pretoria.

Prescott, A & Hughes, L 2018, 'Why do we digitize? the case for slow digitization', *Archive journal*, September.

Ridge, M (ed.) 2014, *Crowdsourcing our cultural heritage*, Ashgate, Farnham.

Stein, R 2014, 'Museums... so what?', in *Museums and the web 2014*, Archives & Museum Informatics, Silver Spring.

Van Garderen, P 2011, 'Archivematica: using micro-services and open-source software to deliver a comprehensive digital curation solution', in *Proceedings of the 7th international conference on preservation of digital objects*, Vienna.

World Wide Web Consortium 2018, *Web content accessibility guidelines (WCAG) 2.1*, W3C recommendation, W3C, Cambridge.

Zorich, DM 2008, *A survey of digital cultural heritage initiatives and their sustainability concerns*, Council on Library and Information Resources, Washington.
