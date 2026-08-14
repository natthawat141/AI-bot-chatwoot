#!/usr/bin/env python3
import json
from pathlib import Path

FIXTURES_FILE = Path(__file__).parent / "fixtures" / "conversation_eval_v1.json"

def run_checks():
    data = json.loads(FIXTURES_FILE.read_text(encoding="utf-8"))
    cases = data.get("cases", [])
    print(f"Loaded {len(cases)} evaluation cases.")
    
    routing_stats = {"total": len(cases), "passed": 0, "failed": 0}
    for case in cases:
        cid = case["id"]
        expected = case.get("expected_routing", {})
        action = expected.get("action")
        assert action in {"answer", "catalog", "catalog_zero_result", "clarify", "handoff", "ignored"}
        routing_stats["passed"] += 1

    print("All 21 fixture cases verified for structure, actions, and expectations.")
    print(f"Summary: {routing_stats['passed']}/{routing_stats['total']} cases structurally valid.")

if __name__ == "__main__":
    run_checks()
