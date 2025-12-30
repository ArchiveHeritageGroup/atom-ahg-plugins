#!/usr/bin/env python3
"""
AtoM to Records in Context (RiC) Extraction POC
================================================

Extracts archival descriptions from AtoM MySQL database and transforms
to RiC-O compliant JSON-LD with full Activity model.

Usage:
    python ric_extractor.py --list-fonds
    python ric_extractor.py --fonds-id 123 --output output.jsonld --pretty

Configuration via environment variables:
    ATOM_DB_HOST (default: localhost)
    ATOM_DB_USER (default: root)
    ATOM_DB_PASSWORD (required)
    ATOM_DB_NAME (default: atom_psis)
    RIC_BASE_URI (default: https://archives.theahg.co.za/ric)
    ATOM_INSTANCE_ID (default: atom-psis)
"""

import json
import os
import sys
import argparse
from datetime import datetime
from typing import Dict, List, Optional, Any

try:
    import mysql.connector
    from mysql.connector import Error
except ImportError:
    print("Error: mysql-connector-python required. Install with:")
    print("  pip install mysql-connector-python")
    sys.exit(1)


class RiCExtractor:
    """Extracts AtoM data and transforms to RiC-O JSON-LD."""
    
    # AtoM level of description to RiC entity mapping
    LEVEL_TO_RIC = {
        'fonds': 'RecordSet',
        'subfonds': 'RecordSet',
        'collection': 'RecordSet',
        'series': 'RecordSet',
        'subseries': 'RecordSet',
        'file': 'RecordSet',
        'item': 'Record',
        'part': 'RecordPart',
    }
    
    # AtoM actor entity type to RiC agent type
    ACTOR_TYPE_TO_RIC = {
        'corporate body': 'CorporateBody',
        'person': 'Person',
        'family': 'Family',
    }
    
    # AtoM event type to RiC activity type
    EVENT_TYPE_TO_RIC = {
        'creation': 'Production',
        'accumulation': 'Accumulation',
        'contribution': 'Production',  # Map to Production with role
    }
    
    def __init__(self, db_config: Dict[str, str], base_uri: str, instance_id: str):
        """
        Initialize extractor with database configuration.
        
        Args:
            db_config: MySQL connection parameters
            base_uri: Base URI for minting RiC entity URIs
            instance_id: Identifier for this AtoM instance
        """
        self.db_config = db_config
        self.base_uri = base_uri.rstrip('/')
        self.instance_id = instance_id
        self.connection = None
        self.cursor = None
        
        # Cache for extracted entities
        self.records = {}
        self.agents = {}
        self.activities = {}
        self.repository = None
        
    def connect(self):
        """Establish database connection."""
        try:
            self.connection = mysql.connector.connect(**self.db_config)
            self.cursor = self.connection.cursor(dictionary=True)
            print(f"Connected to database: {self.db_config['database']}")
        except Error as e:
            print(f"Database connection error: {e}")
            sys.exit(1)
            
    def close(self):
        """Close database connection."""
        if self.cursor:
            self.cursor.close()
        if self.connection:
            self.connection.close()
            
    def mint_uri(self, entity_type: str, entity_id: int) -> str:
        """
        Mint a stable URI for a RiC entity.
        
        Args:
            entity_type: RiC entity type (e.g., 'RecordSet', 'Person')
            entity_id: AtoM database ID
            
        Returns:
            Full URI for the entity
        """
        return f"{self.base_uri}/{self.instance_id}/{entity_type.lower()}/{entity_id}"
    
    def list_fonds(self) -> List[Dict]:
        """List all top-level fonds in the database."""
        query = """
            SELECT 
                io.id,
                io.identifier,
                ioi.title,
                (SELECT COUNT(*) FROM information_object d 
                 WHERE d.lft > io.lft AND d.rgt < io.rgt) as descendant_count
            FROM information_object io
            JOIN information_object_i18n ioi ON io.id = ioi.id AND ioi.culture = 'en'
            JOIN term_i18n ti ON io.level_of_description_id = ti.id AND ti.culture = 'en'
            WHERE io.parent_id = 1  -- QubitInformationObject::ROOT_ID
            AND LOWER(ti.name) = 'fonds'
            ORDER BY ioi.title
        """
        self.cursor.execute(query)
        return self.cursor.fetchall()
    
    def extract_fonds(self, fonds_id: int) -> Dict:
        """
        Extract a complete fonds with hierarchy, creators, and activities.
        
        Args:
            fonds_id: Database ID of the fonds to extract
            
        Returns:
            RiC-O compliant JSON-LD structure
        """
        # Get fonds boundaries for nested set query
        self.cursor.execute(
            "SELECT lft, rgt FROM information_object WHERE id = %s",
            (fonds_id,)
        )
        bounds = self.cursor.fetchone()
        if not bounds:
            raise ValueError(f"Fonds with ID {fonds_id} not found")
            
        # Extract all records in hierarchy
        self._extract_records(fonds_id, bounds['lft'], bounds['rgt'])
        
        # Extract all related agents
        self._extract_agents(bounds['lft'], bounds['rgt'])
        
        # Extract activities (creator events)
        self._extract_activities(bounds['lft'], bounds['rgt'])
        
        # Extract repository
        self._extract_repository(fonds_id)
        
        # Build JSON-LD output
        return self._build_jsonld()
    
    def _extract_records(self, fonds_id: int, lft: int, rgt: int):
        """Extract all records in the fonds hierarchy."""
        query = """
            SELECT 
                io.id,
                io.parent_id,
                io.lft,
                io.rgt,
                io.identifier,
                ioi.title,
                ioi.scope_and_content,
                ioi.arrangement,
                ioi.extent_and_medium,
                ti.name as level_of_description,
                io.source_culture
            FROM information_object io
            JOIN information_object_i18n ioi ON io.id = ioi.id 
                AND ioi.culture = COALESCE(io.source_culture, 'en')
            LEFT JOIN term_i18n ti ON io.level_of_description_id = ti.id 
                AND ti.culture = 'en'
            WHERE io.lft >= %s AND io.rgt <= %s
            ORDER BY io.lft
        """
        self.cursor.execute(query, (lft, rgt))
        
        for row in self.cursor.fetchall():
            level = (row['level_of_description'] or 'item').lower()
            ric_type = self.LEVEL_TO_RIC.get(level, 'RecordSet')
            
            self.records[row['id']] = {
                '@id': self.mint_uri(ric_type, row['id']),
                '@type': f'rico:{ric_type}',
                'rico:identifier': row['identifier'],
                'rico:title': row['title'],
                'rico:scopeAndContent': row['scope_and_content'],
                'rico:arrangement': row['arrangement'],
                'rico:extentAndMedium': row['extent_and_medium'],
                '_parent_id': row['parent_id'],
                '_lft': row['lft'],
                '_rgt': row['rgt'],
            }
            
    def _extract_agents(self, lft: int, rgt: int):
        """Extract all agents (creators) related to records in the fonds."""
        query = """
            SELECT DISTINCT
                a.id,
                a.entity_type_id,
                ai.authorized_form_of_name,
                ai.history,
                ai.places,
                ai.functions,
                ti.name as entity_type
            FROM actor a
            JOIN actor_i18n ai ON a.id = ai.id AND ai.culture = 'en'
            JOIN event e ON a.id = e.actor_id
            JOIN information_object io ON e.information_object_id = io.id
            LEFT JOIN term_i18n ti ON a.entity_type_id = ti.id AND ti.culture = 'en'
            WHERE io.lft >= %s AND io.rgt <= %s
        """
        self.cursor.execute(query, (lft, rgt))
        
        for row in self.cursor.fetchall():
            entity_type = (row['entity_type'] or 'corporate body').lower()
            ric_type = self.ACTOR_TYPE_TO_RIC.get(entity_type, 'Agent')
            
            self.agents[row['id']] = {
                '@id': self.mint_uri(ric_type, row['id']),
                '@type': f'rico:{ric_type}',
                'rico:hasAgentName': {
                    '@type': 'rico:AgentName',
                    'rico:textualValue': row['authorized_form_of_name'],
                    'rico:isOrWasAgentNameOf': self.mint_uri(ric_type, row['id']),
                },
                'rico:history': row['history'],
            }
            
    def _extract_activities(self, lft: int, rgt: int):
        """Extract creation/accumulation activities from event table."""
        query = """
            SELECT 
                e.id,
                e.information_object_id,
                e.actor_id,
                e.start_date,
                e.end_date,
                ti.name as event_type
            FROM event e
            JOIN information_object io ON e.information_object_id = io.id
            LEFT JOIN term_i18n ti ON e.type_id = ti.id AND ti.culture = 'en'
            WHERE io.lft >= %s AND io.rgt <= %s
            AND e.actor_id IS NOT NULL
        """
        self.cursor.execute(query, (lft, rgt))
        
        for row in self.cursor.fetchall():
            event_type = (row['event_type'] or 'creation').lower()
            ric_activity_type = self.EVENT_TYPE_TO_RIC.get(event_type, 'Activity')
            
            # Get record and agent URIs
            record = self.records.get(row['information_object_id'])
            agent = self.agents.get(row['actor_id'])
            
            if record and agent:
                activity_id = f"activity-{row['id']}"
                
                # Build date if available
                date_obj = None
                if row['start_date'] or row['end_date']:
                    date_obj = {
                        '@type': 'rico:DateRange',
                        'rico:expressedDate': f"{row['start_date'] or '?'} - {row['end_date'] or '?'}",
                    }
                    if row['start_date']:
                        date_obj['rico:beginningDate'] = row['start_date']
                    if row['end_date']:
                        date_obj['rico:endDate'] = row['end_date']
                
                self.activities[row['id']] = {
                    '@id': self.mint_uri(ric_activity_type, row['id']),
                    '@type': f'rico:{ric_activity_type}',
                    'rico:resultsOrResultedIn': {'@id': record['@id']},
                    'rico:hasOrHadParticipant': {'@id': agent['@id']},
                }
                
                if date_obj:
                    self.activities[row['id']]['rico:isOrWasAssociatedWithDate'] = date_obj
                    
    def _extract_repository(self, fonds_id: int):
        """Extract the repository holding the fonds."""
        query = """
            SELECT 
                r.id,
                ri.authorized_form_of_name,
                ri.history,
                ri.collecting_policies,
                ci.contact_person,
                ci.street_address,
                ci.city,
                ci.postal_code,
                ci.country
            FROM repository r
            JOIN repository_i18n ri ON r.id = ri.id AND ri.culture = 'en'
            LEFT JOIN contact_information ci ON r.id = ci.actor_id
            JOIN information_object io ON io.repository_id = r.id
            WHERE io.id = %s
            LIMIT 1
        """
        self.cursor.execute(query, (fonds_id,))
        row = self.cursor.fetchone()
        
        if row:
            self.repository = {
                '@id': self.mint_uri('CorporateBody', row['id']),
                '@type': 'rico:CorporateBody',
                'rico:hasAgentName': {
                    '@type': 'rico:AgentName',
                    'rico:textualValue': row['authorized_form_of_name'],
                },
                'rico:history': row['history'],
                'rico:hasOrHadLocation': {
                    '@type': 'rico:Place',
                    'rico:hasPlaceName': {
                        '@type': 'rico:PlaceName',
                        'rico:textualValue': ', '.join(filter(None, [
                            row['street_address'],
                            row['city'],
                            row['postal_code'],
                            row['country']
                        ])),
                    },
                },
                '_is_repository': True,
            }
            
    def _build_jsonld(self) -> Dict:
        """Build the complete JSON-LD document."""
        # Build hierarchical relations
        graph = []
        
        # Add records with hierarchy
        for record_id, record in self.records.items():
            parent_id = record.pop('_parent_id', None)
            record.pop('_lft', None)
            record.pop('_rgt', None)
            
            # Remove None values
            record = {k: v for k, v in record.items() if v is not None}
            
            # Add hierarchical relation
            if parent_id and parent_id in self.records:
                parent = self.records[parent_id]
                record['rico:isOrWasPartOf'] = {'@id': parent['@id']}
                
            # Link to repository
            if self.repository and record.get('@type') == 'rico:RecordSet':
                record['rico:isOrWasHeldBy'] = {'@id': self.repository['@id']}
                
            graph.append(record)
            
        # Add agents
        for agent in self.agents.values():
            agent_clean = {k: v for k, v in agent.items() if v is not None}
            graph.append(agent_clean)
            
        # Add activities
        for activity in self.activities.values():
            graph.append(activity)
            
        # Add repository
        if self.repository:
            repo_clean = {k: v for k, v in self.repository.items() 
                         if v is not None and not k.startswith('_')}
            graph.append(repo_clean)
            
        return {
            '@context': {
                'rico': 'https://www.ica.org/standards/RiC/ontology#',
                'rdf': 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
                'rdfs': 'http://www.w3.org/2000/01/rdf-schema#',
                'xsd': 'http://www.w3.org/2001/XMLSchema#',
            },
            '@graph': graph,
            '_metadata': {
                'extracted': datetime.utcnow().isoformat() + 'Z',
                'source': f'AtoM instance: {self.instance_id}',
                'records_count': len(self.records),
                'agents_count': len(self.agents),
                'activities_count': len(self.activities),
            }
        }


def main():
    parser = argparse.ArgumentParser(
        description='Extract AtoM data to RiC-O JSON-LD'
    )
    parser.add_argument('--list-fonds', action='store_true',
                        help='List available fonds in database')
    parser.add_argument('--fonds-id', type=int,
                        help='ID of fonds to extract')
    parser.add_argument('--output', '-o', type=str, default='output.jsonld',
                        help='Output file path')
    parser.add_argument('--pretty', action='store_true',
                        help='Pretty-print JSON output')
    
    args = parser.parse_args()
    
    # Database configuration from environment
    db_config = {
        'host': os.environ.get('ATOM_DB_HOST', 'localhost'),
        'user': os.environ.get('ATOM_DB_USER', 'root'),
        'password': os.environ.get('ATOM_DB_PASSWORD', 'Merlot@123'),
        'database': os.environ.get('ATOM_DB_NAME', 'atom'),
    }
    
    base_uri = os.environ.get('RIC_BASE_URI', 'https://archives.theahg.co.za/ric')
    instance_id = os.environ.get('ATOM_INSTANCE_ID', 'atom-psis')
    
    if not db_config['password']:
        print("Warning: ATOM_DB_PASSWORD not set")
        
    extractor = RiCExtractor(db_config, base_uri, instance_id)
    
    try:
        extractor.connect()
        
        if args.list_fonds:
            fonds_list = extractor.list_fonds()
            print("\nAvailable fonds in database:\n")
            print(f"{'ID':<8} {'Identifier':<20} {'Title':<50} {'Descendants':<10}")
            print("-" * 90)
            for f in fonds_list:
                print(f"{f['id']:<8} {(f['identifier'] or ''):<20} "
                      f"{(f['title'] or '')[:48]:<50} {f['descendant_count']:<10}")
                      
        elif args.fonds_id:
            print(f"\nExtracting fonds ID: {args.fonds_id}")
            result = extractor.extract_fonds(args.fonds_id)
            
            indent = 2 if args.pretty else None
            with open(args.output, 'w', encoding='utf-8') as f:
                json.dump(result, f, indent=indent, ensure_ascii=False)
                
            print(f"\nExtraction complete:")
            print(f"  Records: {result['_metadata']['records_count']}")
            print(f"  Agents: {result['_metadata']['agents_count']}")
            print(f"  Activities: {result['_metadata']['activities_count']}")
            print(f"  Output: {args.output}")
            
        else:
            parser.print_help()
            
    finally:
        extractor.close()


if __name__ == '__main__':
    main()