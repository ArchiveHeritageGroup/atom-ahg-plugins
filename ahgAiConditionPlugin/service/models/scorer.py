"""
Condition Scorer - Calculates overall condition score and grade.

Scoring algorithm:
  1. Start at 100 (perfect condition)
  2. For each detected damage:
     deduction = base_severity * area_factor * type_weight
  3. Clamp to 0-100 range
  4. Map to condition grade: excellent/good/fair/poor/critical
  5. Generate repair recommendations based on damages found
"""
import logging

import config

logger = logging.getLogger("ai-condition.scorer")

# Severity name -> base deduction points
SEVERITY_DEDUCTIONS = {
    "minor": 5,
    "moderate": 12,
    "severe": 25,
    "critical": 40,
}

# Damage-specific recommendations
DAMAGE_RECOMMENDATIONS = {
    "tear": "Repair tear using Japanese tissue and wheat starch paste. Document tear dimensions before repair.",
    "stain": "Assess stain composition. Consider aqueous treatment if paper is stable. Document before treatment.",
    "foxing": "Monitor for spread. Consider alkaline treatment. Store in climate-controlled environment (RH < 50%).",
    "fading": "Limit light exposure. Store in archival enclosures. Consider digital surrogate for access.",
    "water_damage": "Dry immediately if recent. Assess for mold risk. Consider freeze-drying for saturated materials.",
    "mold": "URGENT: Isolate from collection. Treat in controlled environment. HEPA vacuum visible mold. Consult conservator.",
    "pest_damage": "URGENT: Isolate and inspect surrounding materials. Consider anoxic treatment. Implement IPM program.",
    "abrasion": "Provide protective enclosure. Interleave with acid-free tissue. Handle with cotton gloves.",
    "brittleness": "Handle with extreme care. Consider deacidification. Create digital surrogate for access.",
    "loss": "Document missing areas photographically. Consider fill/inpainting only if structurally needed.",
    "discoloration": "Assess cause (acid migration, light damage, chemical). Store in buffered enclosures.",
    "warping": "Humidify gradually and flatten under weight. Use board supports for storage.",
    "cracking": "Stabilize with appropriate consolidant. Provide rigid support. Minimize handling.",
    "delamination": "Consolidate with appropriate adhesive. Provide padded support. Consult specialist.",
    "corrosion": "Isolate from other materials. Reduce humidity. Consider chemical stabilization.",
}

# General recommendations based on condition grade
GRADE_RECOMMENDATIONS = {
    "excellent": [
        "Continue current storage conditions.",
        "Maintain regular inspection schedule.",
    ],
    "good": [
        "Schedule condition review within 12 months.",
        "Ensure archival storage materials are used.",
    ],
    "fair": [
        "Prioritize for conservation treatment within 6 months.",
        "Create digital surrogate for access to reduce handling.",
        "Improve storage conditions (climate control, archival enclosures).",
    ],
    "poor": [
        "PRIORITY: Schedule conservation treatment as soon as possible.",
        "Restrict physical access; provide digital surrogates only.",
        "Improve environmental controls in storage area.",
    ],
    "critical": [
        "URGENT: Immediate conservation intervention required.",
        "Isolate from collection to prevent contamination.",
        "Engage professional conservator for treatment plan.",
        "Create high-resolution digital record before any handling.",
    ],
}


def _classify_severity(confidence: float, area_percentage: float) -> str:
    """
    Classify severity based on detection confidence and area coverage.
    """
    # Combined score: higher confidence + larger area = worse
    combined = (confidence * 0.4) + (min(area_percentage, 50) / 50 * 0.6)

    if combined < 0.15:
        return "minor"
    elif combined < 0.35:
        return "moderate"
    elif combined < 0.60:
        return "severe"
    else:
        return "critical"


class ConditionScorer:
    """
    Scores the overall condition of an archival item based on detected damages.
    """

    def score(self, damages: list[dict]) -> dict:
        """
        Calculate overall condition score from detected damages.

        Args:
            damages: List of damage dicts from detector/classifier, each with:
              - damage_type: str
              - confidence: float
              - area_percentage: float

        Returns:
            Dict with:
              - overall_score: float (0-100)
              - condition_grade: str
              - recommendations: list[str]
              - damages_scored: list[dict] (damages with severity and deduction info)
        """
        if not damages:
            return {
                "overall_score": 100.0,
                "condition_grade": "excellent",
                "recommendations": GRADE_RECOMMENDATIONS["excellent"],
                "damages_scored": [],
            }

        score = 100.0
        damages_scored = []
        seen_damage_types = set()

        for damage in damages:
            damage_type = damage.get("damage_type", "unknown")
            confidence = damage.get("confidence", 0.5)
            area_pct = damage.get("area_percentage", 1.0)

            # Classify severity
            severity = _classify_severity(confidence, area_pct)

            # Calculate deduction
            base_deduction = SEVERITY_DEDUCTIONS.get(severity, 10)
            type_weight = config.DAMAGE_TYPE_WEIGHTS.get(damage_type, 1.0)

            # Area factor: larger damage areas cause more deduction
            # Capped at 3x for very large areas
            area_factor = min(1.0 + (area_pct / 20.0), 3.0)

            deduction = base_deduction * type_weight * area_factor

            score -= deduction
            seen_damage_types.add(damage_type)

            # Add scoring details to damage entry
            scored = dict(damage)
            scored.update({
                "severity": severity,
                "deduction": round(deduction, 2),
                "type_weight": type_weight,
                "area_factor": round(area_factor, 2),
                "description": self._describe_damage(damage_type, severity),
            })
            damages_scored.append(scored)

        # Clamp score to 0-100
        overall_score = max(0.0, min(100.0, round(score, 1)))

        # Determine grade
        condition_grade = self._score_to_grade(overall_score)

        # Build recommendations
        recommendations = self._build_recommendations(
            seen_damage_types, condition_grade
        )

        return {
            "overall_score": overall_score,
            "condition_grade": condition_grade,
            "recommendations": recommendations,
            "damages_scored": damages_scored,
        }

    def _score_to_grade(self, score: float) -> str:
        """Map numeric score to condition grade."""
        for grade, (low, high) in config.CONDITION_GRADES.items():
            if low <= score <= high:
                return grade
        return "critical"

    def _describe_damage(self, damage_type: str, severity: str) -> str:
        """Generate a human-readable description of a damage finding."""
        descriptions = {
            "tear": f"{severity.capitalize()} tear detected in material",
            "stain": f"{severity.capitalize()} staining visible on surface",
            "foxing": f"{severity.capitalize()} foxing (brown spots) present",
            "fading": f"{severity.capitalize()} fading or color loss detected",
            "water_damage": f"{severity.capitalize()} water damage evident",
            "mold": f"{severity.capitalize()} mold or fungal growth detected",
            "pest_damage": f"{severity.capitalize()} insect or pest damage found",
            "abrasion": f"{severity.capitalize()} surface abrasion or wear",
            "brittleness": f"{severity.capitalize()} brittleness or material degradation",
            "loss": f"{severity.capitalize()} material loss or missing sections",
            "discoloration": f"{severity.capitalize()} discoloration or yellowing",
            "warping": f"{severity.capitalize()} warping or distortion of material",
            "cracking": f"{severity.capitalize()} cracking in material structure",
            "delamination": f"{severity.capitalize()} delamination or layer separation",
            "corrosion": f"{severity.capitalize()} corrosion or chemical degradation",
        }
        return descriptions.get(
            damage_type,
            f"{severity.capitalize()} {damage_type} detected",
        )

    def _build_recommendations(
        self, damage_types: set, condition_grade: str
    ) -> list[str]:
        """
        Build a prioritized list of recommendations based on detected damages
        and overall condition grade.
        """
        recommendations = []

        # Add grade-level recommendations first
        grade_recs = GRADE_RECOMMENDATIONS.get(condition_grade, [])
        recommendations.extend(grade_recs)

        # Add damage-specific recommendations
        # Priority order: mold and pest first (urgent), then structural, then surface
        priority_order = [
            "mold", "pest_damage", "water_damage",
            "loss", "delamination", "cracking", "corrosion",
            "tear", "brittleness", "warping", "abrasion",
            "foxing", "stain", "fading", "discoloration",
        ]

        for damage_type in priority_order:
            if damage_type in damage_types:
                rec = DAMAGE_RECOMMENDATIONS.get(damage_type)
                if rec and rec not in recommendations:
                    recommendations.append(rec)

        return recommendations
