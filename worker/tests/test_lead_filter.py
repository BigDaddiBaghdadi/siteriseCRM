import unittest

from audit_worker.discoverer import _audit_has_redesign_lead_potential


class LeadFilterTest(unittest.TestCase):
    def test_skips_polished_upgrade_only_site(self):
        audit = {
            "scores": {"overall": 89, "redesign": 46, "seo": 100},
            "issues": [],
            "recommendations": ["Make the contact page easy to find"],
        }

        qualified, reason = _audit_has_redesign_lead_potential(audit)

        self.assertFalse(qualified)
        self.assertIn("healthy", reason)

    def test_skips_soft_optimization_only_issues(self):
        audit = {
            "scores": {"overall": 89, "redesign": 46, "seo": 100},
            "issues": [
                "No obvious analytics tracking was detected, so the business may not know which pages generate leads",
                "The site platform was not obvious, which can make maintenance and future changes harder to qualify quickly",
            ],
        }

        qualified, reason = _audit_has_redesign_lead_potential(audit)

        self.assertFalse(qualified)
        self.assertIn("healthy", reason)

    def test_qualifies_high_redesign_score(self):
        audit = {
            "scores": {"overall": 75, "redesign": 60},
            "issues": [],
        }

        qualified, reason = _audit_has_redesign_lead_potential(audit)

        self.assertTrue(qualified)
        self.assertIn("redesign score", reason)

    def test_qualifies_low_overall_score(self):
        audit = {
            "scores": {"overall": 80, "redesign": 55},
            "issues": [],
        }

        qualified, reason = _audit_has_redesign_lead_potential(audit)

        self.assertTrue(qualified)
        self.assertIn("overall score", reason)

    def test_qualifies_concrete_pitch_issue(self):
        audit = {
            "scores": {"overall": 92, "redesign": 43},
            "issues": ["Primary call to action is unclear"],
        }

        qualified, reason = _audit_has_redesign_lead_potential(audit)

        self.assertTrue(qualified)
        self.assertIn("qualifying issue", reason)


if __name__ == "__main__":
    unittest.main()
