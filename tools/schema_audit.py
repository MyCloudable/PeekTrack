#!/usr/bin/env python3
"""
Schema audit utility for PeekTrack AI sprints.

Usage:
    python3 schema_audit.py /path/to/code.php [/path/to/another.php ...]

What it checks:
  1. Every DB::table('xxx') reference points to a table that exists
  2. Every ->where('col', ...) and ->select(['col']) column exists on the table
     (when the table can be inferred from the immediate context)
  3. Insert/update payloads use real column names
  4. Common camelCase vs snake_case mistakes flagged

What it CANNOT check:
  - Eloquent model relationships (require model parsing)
  - Dynamic table or column names (variables instead of literals)
  - Joins with aliased tables (alias resolution is tricky)

Treat output as guidance, not absolute truth. Eyeball the warnings and
verify against the schema dump.
"""

import json
import re
import sys
from pathlib import Path

SCHEMA_PATH = Path(__file__).parent / "peektrack_schema.json"


def load_schema():
    with open(SCHEMA_PATH) as f:
        schema = json.load(f)
    return {
        table: {col['name'] for col in info['columns']}
        for table, info in schema.items()
    }


def scan_php(path: Path, schema: dict) -> list:
    """Return list of (severity, message) tuples."""
    issues = []
    code = path.read_text()

    # Strip comments and strings to reduce false positives
    no_block = re.sub(r'/\*.*?\*/', '', code, flags=re.DOTALL)
    no_line = re.sub(r'//[^\n]*', '', no_block)

    # Check 1: tables referenced via DB::table('xxx')
    referenced_tables = set()
    for m in re.finditer(r"DB::table\(\s*'([a-z_][a-z0-9_]*)'\s*\)", no_line):
        tbl = m.group(1)
        referenced_tables.add(tbl)
        if tbl not in schema:
            issues.append(('ERROR', f"Unknown table: '{tbl}'"))

    # Check 2: column references in where/select/update/insert calls
    # We pair each table with the immediately-following chained calls.
    # This is heuristic — only catches `DB::table('x')->where(...)` style.
    for m in re.finditer(
        r"DB::table\(\s*'([a-z_]\w*)'\s*\)([^;]+);",
        no_line,
        re.DOTALL,
    ):
        tbl = m.group(1)
        chain = m.group(2)
        if tbl not in schema:
            continue
        valid_cols = schema[tbl]

        # Pull column names from common methods
        for col_match in re.finditer(
            r"->\s*(?:where|whereIn|whereNotIn|whereNull|whereNotNull|orWhere|orderBy|select|pluck|value)"
            r"\(\s*'([a-z_]\w*)'",
            chain,
        ):
            col = col_match.group(1)
            if col not in valid_cols:
                # Suggest closest match
                suggestion = closest_match(col, valid_cols)
                hint = f" (did you mean '{suggestion}'?)" if suggestion else ""
                issues.append((
                    'WARN',
                    f"{tbl}.{col} not in schema{hint}"
                ))

        # Insert/update arrays: ->insert([... 'col' => ...])
        for arr_match in re.finditer(
            r"->\s*(?:insert|insertGetId|update|updateOrInsert|upsert)\s*\(\s*\[(.*?)\]",
            chain,
            re.DOTALL,
        ):
            arr_body = arr_match.group(1)
            for k_match in re.finditer(r"'([a-z_]\w*)'\s*=>", arr_body):
                col = k_match.group(1)
                if col not in valid_cols:
                    suggestion = closest_match(col, valid_cols)
                    hint = f" (did you mean '{suggestion}'?)" if suggestion else ""
                    issues.append((
                        'WARN',
                        f"{tbl} insert/update uses unknown column '{col}'{hint}"
                    ))

    # Check 3: object-property access patterns (e.g. $card->columnName)
    # When a variable is fetched from a known table via DB::table(...)->first()
    # or ->get(), subsequent $var->prop accesses should map to real columns.
    # This is heuristic — we look for specific known-bad patterns rather than
    # full-program data flow analysis.

    # Pattern: variable assigned from jobentries fetch, then accessed via ->prop
    je_fetched_vars = set()
    selectraw_vars = set()  # variables fetched via selectRaw with custom aliases — skip strict check

    # Find the assignment + entire RHS (terminator is `;`)
    for m in re.finditer(
        r"\$(\w+)\s*=\s*(DB::table\(\s*'jobentries'\s*\)[^;]*?->(?:first|find|value)\([^;]*?);",
        no_line,
        re.DOTALL,
    ):
        var = m.group(1)
        chain = m.group(2)
        je_fetched_vars.add(var)
        if 'selectRaw' in chain:
            selectraw_vars.add(var)

    je_cols = schema.get('jobentries', set())

    for var in je_fetched_vars:
        if var in selectraw_vars:
            continue  # selectRaw aliases — properties are custom, not column names
        # Find every $var->something access
        for prop_match in re.finditer(rf"\${var}->(\w+)", no_line):
            prop = prop_match.group(1)
            if prop not in je_cols:
                suggestion = closest_match(prop, je_cols)
                hint = f" (did you mean '{suggestion}'?)" if suggestion else ""
                issues.append((
                    'ERROR' if prop in {'user_id', 'notes'} else 'WARN',
                    f"${var}->{prop} accessed but '{prop}' not in jobentries schema{hint}"
                ))

    # Pattern-blind: catch the two specific bugs we already hit
    if 'jobentries' in schema:
        if re.search(r"->where\(\s*'user_id'", no_line):
            issues.append((
                'ERROR',
                "->where('user_id', ...) — jobentries column is `userId` (camelCase)"
            ))
        # $card->user_id when card was fetched from jobentries (heuristic — any $var->user_id is suspicious)
        if re.search(r"\$\w+->user_id\b", no_line):
            issues.append((
                'WARN',
                "$var->user_id usage detected — verify the variable isn't from jobentries (column is `userId`)"
            ))
        # $card->notes — column doesn't exist on jobentries
        for nm in re.finditer(r"\$(\w+)->notes\b", no_line):
            issues.append((
                'WARN',
                f"${nm.group(1)}->notes accessed — jobentries has no `notes` column. "
                "Use `equipment_only_reason_text` or join `job_notes` table."
            ))

    return issues


def closest_match(target: str, candidates: set, max_distance: int = 3) -> str | None:
    """Return the closest column name by Levenshtein distance, if within threshold."""
    best = None
    best_dist = max_distance + 1
    for c in candidates:
        d = levenshtein(target, c)
        if d < best_dist:
            best_dist = d
            best = c
    return best if best_dist <= max_distance else None


def levenshtein(a: str, b: str) -> int:
    if len(a) < len(b):
        a, b = b, a
    if not b:
        return len(a)
    prev = list(range(len(b) + 1))
    for i, ca in enumerate(a, 1):
        cur = [i]
        for j, cb in enumerate(b, 1):
            cur.append(min(
                prev[j] + 1,
                cur[j-1] + 1,
                prev[j-1] + (ca != cb),
            ))
        prev = cur
    return prev[-1]


def main():
    if len(sys.argv) < 2:
        print("Usage: python3 schema_audit.py <file.php> [<file.php> ...]")
        sys.exit(1)

    schema = load_schema()
    total_issues = 0

    for arg in sys.argv[1:]:
        path = Path(arg)
        if not path.exists():
            print(f"  ✗ Not found: {arg}")
            continue
        issues = scan_php(path, schema)
        if not issues:
            print(f"  ✓ {path.name}")
        else:
            print(f"  ⚠ {path.name}")
            for severity, msg in issues:
                print(f"      [{severity}] {msg}")
            total_issues += len(issues)

    print()
    print(f"Total issues: {total_issues}")
    sys.exit(0 if total_issues == 0 else 1)


if __name__ == '__main__':
    main()
