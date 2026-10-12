"""Translation resource for offline machine translation using Argos Translate."""

import json
import os
import subprocess
import sys
from pathlib import Path
from typing import Any, Dict, List, Optional, Tuple

# Argos Translate is optional - only import if available
try:
    import argostranslate.package
    import argostranslate.translate
    ARGOS_AVAILABLE = True
except ImportError:
    ARGOS_AVAILABLE = False


class TranslationService:
    """
    Offline machine translation service using Argos Translate.

    Supports translation between multiple languages without external API calls.
    Language packs are downloaded and stored locally.
    """

    # Supported language codes and their full names
    SUPPORTED_LANGUAGES = {
        "en": "English",
        "af": "Afrikaans",
        "fr": "French",
        "nl": "Dutch",
        "pt": "Portuguese",
        "es": "Spanish",
        "de": "German",
        "it": "Italian",
        "ar": "Arabic",
        "zh": "Chinese",
        "ja": "Japanese",
        "ru": "Russian",
    }

    def __init__(self, packages_path: Optional[str] = None) -> None:
        """
        Initialize the translation service.

        Args:
            packages_path: Custom path for storing language packages.
                          Defaults to ~/.local/share/argos-translate/packages
        """
        if not ARGOS_AVAILABLE:
            raise ImportError(
                "argostranslate is not installed. "
                "Install with: pip install 'atom-ahg[translation]'"
            )

        self._packages_path = packages_path
        if packages_path:
            os.environ["ARGOS_PACKAGES_DIR"] = packages_path

        self._installed_languages: Optional[List[Tuple[str, str]]] = None

    @staticmethod
    def is_available() -> bool:
        """Check if Argos Translate is available."""
        return ARGOS_AVAILABLE

    def get_installed_languages(self, refresh: bool = False) -> List[Tuple[str, str]]:
        """
        Get list of installed language pairs.

        Args:
            refresh: Force refresh of cached list.

        Returns:
            List of (from_code, to_code) tuples.
        """
        if self._installed_languages is None or refresh:
            # Use get_installed_packages() which is more reliable
            installed_packages = argostranslate.package.get_installed_packages()
            pairs = [(pkg.from_code, pkg.to_code) for pkg in installed_packages]
            self._installed_languages = pairs
        return self._installed_languages

    def is_pair_installed(self, from_code: str, to_code: str) -> bool:
        """
        Check if a language pair is installed.

        Args:
            from_code: Source language code (e.g., 'en').
            to_code: Target language code (e.g., 'af').

        Returns:
            True if the pair is installed.
        """
        return (from_code, to_code) in self.get_installed_languages()

    def get_available_packages(self) -> List[Dict[str, str]]:
        """
        Get list of available language packages from Argos repository.

        Returns:
            List of package info dicts with from_code, to_code, etc.
        """
        argostranslate.package.update_package_index()
        available = argostranslate.package.get_available_packages()
        return [
            {
                "from_code": pkg.from_code,
                "from_name": pkg.from_name,
                "to_code": pkg.to_code,
                "to_name": pkg.to_name,
                "package_version": pkg.package_version,
            }
            for pkg in available
        ]

    def install_package(self, from_code: str, to_code: str) -> bool:
        """
        Install a language package.

        Args:
            from_code: Source language code.
            to_code: Target language code.

        Returns:
            True if installation succeeded.
        """
        argostranslate.package.update_package_index()
        available = argostranslate.package.get_available_packages()

        # Find matching package
        package = next(
            (pkg for pkg in available
             if pkg.from_code == from_code and pkg.to_code == to_code),
            None
        )

        if package is None:
            raise ValueError(f"No package found for {from_code} -> {to_code}")

        # Download and install
        download_path = package.download()
        argostranslate.package.install_from_path(download_path)

        # Refresh cache
        self._installed_languages = None
        return True

    def translate(
        self,
        text: str,
        from_code: str,
        to_code: str,
        auto_install: bool = False
    ) -> str:
        """
        Translate text between languages.

        Args:
            text: Text to translate.
            from_code: Source language code.
            to_code: Target language code.
            auto_install: If True, automatically install missing packages.

        Returns:
            Translated text.

        Raises:
            ValueError: If the language pair is not installed.
        """
        if not self.is_pair_installed(from_code, to_code):
            if auto_install:
                self.install_package(from_code, to_code)
            else:
                raise ValueError(
                    f"Language pair {from_code} -> {to_code} not installed. "
                    f"Install with: translation.install_package('{from_code}', '{to_code}')"
                )

        return argostranslate.translate.translate(text, from_code, to_code)

    def translate_batch(
        self,
        texts: List[str],
        from_code: str,
        to_code: str,
        auto_install: bool = False
    ) -> List[str]:
        """
        Translate multiple texts.

        Args:
            texts: List of texts to translate.
            from_code: Source language code.
            to_code: Target language code.
            auto_install: If True, automatically install missing packages.

        Returns:
            List of translated texts.
        """
        # Ensure package is installed once
        if not self.is_pair_installed(from_code, to_code):
            if auto_install:
                self.install_package(from_code, to_code)
            else:
                raise ValueError(f"Language pair {from_code} -> {to_code} not installed.")

        return [
            argostranslate.translate.translate(text, from_code, to_code)
            for text in texts
        ]

    def translate_dict(
        self,
        data: Dict[str, str],
        from_code: str,
        to_code: str,
        fields: Optional[List[str]] = None,
        auto_install: bool = False
    ) -> Dict[str, str]:
        """
        Translate specific fields in a dictionary.

        Useful for translating i18n fields like title, scope_and_content, etc.

        Args:
            data: Dictionary with string values.
            from_code: Source language code.
            to_code: Target language code.
            fields: List of field names to translate. If None, translate all.
            auto_install: If True, automatically install missing packages.

        Returns:
            Dictionary with translated values.
        """
        result = data.copy()
        fields_to_translate = fields or list(data.keys())

        for field in fields_to_translate:
            if field in result and result[field]:
                result[field] = self.translate(
                    result[field], from_code, to_code, auto_install
                )

        return result


def cli_main() -> None:
    """CLI entry point for translation operations."""
    import argparse

    parser = argparse.ArgumentParser(
        description="AtoM AHG Translation CLI - Offline machine translation"
    )
    subparsers = parser.add_subparsers(dest="command", help="Commands")

    # translate command
    translate_parser = subparsers.add_parser("translate", help="Translate text")
    translate_parser.add_argument("text", help="Text to translate")
    translate_parser.add_argument("--from", dest="from_code", required=True, help="Source language code")
    translate_parser.add_argument("--to", dest="to_code", required=True, help="Target language code")
    translate_parser.add_argument("--auto-install", action="store_true", help="Auto-install missing packages")

    # batch command (reads JSON from stdin)
    batch_parser = subparsers.add_parser("batch", help="Batch translate from JSON stdin")
    batch_parser.add_argument("--from", dest="from_code", required=True, help="Source language code")
    batch_parser.add_argument("--to", dest="to_code", required=True, help="Target language code")
    batch_parser.add_argument("--fields", help="Comma-separated field names to translate")
    batch_parser.add_argument("--auto-install", action="store_true", help="Auto-install missing packages")

    # install command
    install_parser = subparsers.add_parser("install", help="Install language package")
    install_parser.add_argument("--from", dest="from_code", required=True, help="Source language code")
    install_parser.add_argument("--to", dest="to_code", required=True, help="Target language code")

    # list command
    list_parser = subparsers.add_parser("list", help="List installed languages")
    list_parser.add_argument("--available", action="store_true", help="Show available packages")

    # check command
    check_parser = subparsers.add_parser("check", help="Check if argostranslate is available")

    args = parser.parse_args()

    if args.command == "check":
        if ARGOS_AVAILABLE:
            # Get version from package metadata if available
            try:
                from importlib.metadata import version
                argos_version = version("argostranslate")
            except Exception:
                argos_version = "unknown"
            print(json.dumps({"available": True, "version": argos_version}))
        else:
            print(json.dumps({"available": False, "error": "argostranslate not installed"}))
        return

    if not ARGOS_AVAILABLE:
        print(json.dumps({"error": "argostranslate not installed. Run: pip install argostranslate"}))
        sys.exit(1)

    service = TranslationService()

    if args.command == "translate":
        try:
            result = service.translate(
                args.text,
                args.from_code,
                args.to_code,
                auto_install=args.auto_install
            )
            print(json.dumps({"success": True, "translation": result}))
        except Exception as e:
            print(json.dumps({"success": False, "error": str(e)}))
            sys.exit(1)

    elif args.command == "batch":
        try:
            # Read JSON from stdin
            input_data = json.load(sys.stdin)
            fields = args.fields.split(",") if args.fields else None

            if isinstance(input_data, list):
                # List of dicts
                results = [
                    service.translate_dict(item, args.from_code, args.to_code, fields, args.auto_install)
                    for item in input_data
                ]
            else:
                # Single dict
                results = service.translate_dict(
                    input_data, args.from_code, args.to_code, fields, args.auto_install
                )

            print(json.dumps({"success": True, "results": results}))
        except Exception as e:
            print(json.dumps({"success": False, "error": str(e)}))
            sys.exit(1)

    elif args.command == "install":
        try:
            service.install_package(args.from_code, args.to_code)
            print(json.dumps({"success": True, "installed": f"{args.from_code} -> {args.to_code}"}))
        except Exception as e:
            print(json.dumps({"success": False, "error": str(e)}))
            sys.exit(1)

    elif args.command == "list":
        if args.available:
            packages = service.get_available_packages()
            print(json.dumps({"available_packages": packages}))
        else:
            pairs = service.get_installed_languages()
            print(json.dumps({"installed_pairs": [{"from": f, "to": t} for f, t in pairs]}))

    else:
        parser.print_help()


if __name__ == "__main__":
    cli_main()
