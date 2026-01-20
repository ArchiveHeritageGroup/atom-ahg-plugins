#!/usr/bin/env python3
"""
PDF Redaction Service for AtoM AHG Privacy Plugin

Redacts specified text from PDF documents while preserving document structure.
Uses PyMuPDF (fitz) for efficient text finding and redaction.

Usage:
    python3 pdf_redactor.py <input_pdf> <output_pdf> <terms_json>

    terms_json: JSON array of terms to redact, e.g., '["John Smith", "john@email.com"]'

Or as a module:
    from pdf_redactor import redact_pdf
    redact_pdf('/path/to/input.pdf', '/path/to/output.pdf', ['term1', 'term2'])
"""

import sys
import json
import os
import fitz  # PyMuPDF
import re
from typing import List, Optional, Dict, Any
import tempfile
import shutil


class PdfRedactor:
    """PDF Redaction Service"""

    # Redaction styling
    REDACT_FILL_COLOR = (0, 0, 0)  # Black fill
    REDACT_TEXT_COLOR = (1, 1, 1)  # White text
    REDACT_FONT_SIZE = 8

    def __init__(self, case_sensitive: bool = False):
        """
        Initialize the PDF redactor.

        Args:
            case_sensitive: Whether term matching should be case-sensitive
        """
        self.case_sensitive = case_sensitive
        self.stats = {
            'pages_processed': 0,
            'terms_found': 0,
            'redactions_applied': 0
        }

    def redact_pdf(
        self,
        input_path: str,
        output_path: str,
        terms: List[str],
        replacement_text: str = "[REDACTED]"
    ) -> Dict[str, Any]:
        """
        Redact specified terms from a PDF document.

        Args:
            input_path: Path to the input PDF
            output_path: Path for the redacted output PDF
            terms: List of text strings to redact
            replacement_text: Text to show in place of redacted content

        Returns:
            Dictionary with redaction statistics
        """
        if not os.path.exists(input_path):
            raise FileNotFoundError(f"Input PDF not found: {input_path}")

        if not terms:
            # No terms to redact, just copy the file
            shutil.copy(input_path, output_path)
            return {'success': True, 'redactions': 0, 'message': 'No terms to redact'}

        # Reset stats
        self.stats = {
            'pages_processed': 0,
            'terms_found': 0,
            'redactions_applied': 0,
            'terms_by_page': {}
        }

        try:
            # Open the PDF
            doc = fitz.open(input_path)

            # Process each page
            for page_num, page in enumerate(doc):
                self.stats['pages_processed'] += 1
                page_redactions = 0

                for term in terms:
                    if not term or len(term.strip()) == 0:
                        continue

                    # Find all instances of the term
                    flags = 0 if self.case_sensitive else fitz.TEXT_PRESERVE_WHITESPACE
                    text_instances = page.search_for(term, flags=flags)

                    if text_instances:
                        self.stats['terms_found'] += len(text_instances)

                        for rect in text_instances:
                            # Add redaction annotation
                            page.add_redact_annot(
                                rect,
                                text=replacement_text,
                                fontsize=self.REDACT_FONT_SIZE,
                                fill=self.REDACT_FILL_COLOR,
                                text_color=self.REDACT_TEXT_COLOR
                            )
                            page_redactions += 1

                # Apply all redactions for this page
                if page_redactions > 0:
                    page.apply_redactions()
                    self.stats['redactions_applied'] += page_redactions
                    self.stats['terms_by_page'][page_num + 1] = page_redactions

            # Save the redacted PDF
            doc.save(output_path, garbage=4, deflate=True)
            doc.close()

            return {
                'success': True,
                'input': input_path,
                'output': output_path,
                'pages_processed': self.stats['pages_processed'],
                'terms_searched': len(terms),
                'instances_found': self.stats['terms_found'],
                'redactions_applied': self.stats['redactions_applied'],
                'terms_by_page': self.stats['terms_by_page']
            }

        except Exception as e:
            return {
                'success': False,
                'error': str(e),
                'input': input_path
            }

    def redact_pdf_to_bytes(
        self,
        input_path: str,
        terms: List[str],
        replacement_text: str = "[REDACTED]"
    ) -> Optional[bytes]:
        """
        Redact PDF and return as bytes (for streaming).

        Args:
            input_path: Path to the input PDF
            terms: List of text strings to redact
            replacement_text: Text to show in place of redacted content

        Returns:
            Redacted PDF as bytes, or None on error
        """
        with tempfile.NamedTemporaryFile(suffix='.pdf', delete=False) as tmp:
            tmp_path = tmp.name

        try:
            result = self.redact_pdf(input_path, tmp_path, terms, replacement_text)
            if result['success']:
                with open(tmp_path, 'rb') as f:
                    return f.read()
            return None
        finally:
            if os.path.exists(tmp_path):
                os.unlink(tmp_path)


def redact_pdf(
    input_path: str,
    output_path: str,
    terms: List[str],
    replacement_text: str = "[REDACTED]",
    case_sensitive: bool = False
) -> Dict[str, Any]:
    """
    Convenience function to redact a PDF.

    Args:
        input_path: Path to input PDF
        output_path: Path for output PDF
        terms: List of terms to redact
        replacement_text: Replacement text for redacted areas
        case_sensitive: Whether matching is case-sensitive

    Returns:
        Dictionary with redaction results
    """
    redactor = PdfRedactor(case_sensitive=case_sensitive)
    return redactor.redact_pdf(input_path, output_path, terms, replacement_text)


def main():
    """CLI entry point"""
    if len(sys.argv) < 4:
        print(json.dumps({
            'success': False,
            'error': 'Usage: pdf_redactor.py <input_pdf> <output_pdf> <terms_json>'
        }))
        sys.exit(1)

    input_pdf = sys.argv[1]
    output_pdf = sys.argv[2]

    try:
        terms = json.loads(sys.argv[3])
        if not isinstance(terms, list):
            terms = [terms]
    except json.JSONDecodeError as e:
        print(json.dumps({
            'success': False,
            'error': f'Invalid JSON for terms: {e}'
        }))
        sys.exit(1)

    # Optional replacement text
    replacement_text = sys.argv[4] if len(sys.argv) > 4 else "[REDACTED]"

    result = redact_pdf(input_pdf, output_pdf, terms, replacement_text)
    print(json.dumps(result))

    sys.exit(0 if result['success'] else 1)


if __name__ == '__main__':
    main()
