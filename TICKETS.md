# Tickets

Complete these three tickets. Write tests for your changes and make sure all existing tests still pass.

---

## Ticket 1: Fix the `truncateWords` modifier bug

A user reported that `{this.description.truncateWords(3)}` produces incorrect results when the input contains multiple consecutive spaces or leading/trailing whitespace.

For example, with `truncateWords(3)`:
- `"The quick brown fox jumps"` → `"The quick brown..."` (works correctly)
- `"  hello   world  foo  bar  "` → should produce `"hello world foo..."` but doesn't

---

## Ticket 2: Add a `numberFormat(decimals, decimalSeparator, thousandsSeparator)` modifier

We need a modifier that formats numeric values with configurable decimals and separators.

Usage examples:
- `{this.price.numberFormat(2, ".", ",")}` → `"1,234.50"`
- `{this.price.numberFormat(0, ",", ".")}` → `"1.235"`

Acceptance criteria:
- Supports `numberFormat(decimals = 0, decimalSeparator = ".", thousandsSeparator = ",")`
- Accepts both numbers and numeric strings as input values
- Rounds correctly according to the provided decimal precision
- Returns the original value unchanged when input is not numeric
- Missing arguments use defaults

---

## Ticket 3: Add a `LinkBlock` across all 3 environments

We need a new block type that renders an `<a>` tag with `href` and `target` as first-class attributes (i.e., dedicated block attributes with their own defaults, like `level` on HeadingBlock — as opposed to the generic `htmlAttributes` bag that stores arbitrary HTML attributes like `id`, `class`, `style`). Dynamic expressions should work in both attributes (e.g., `{this.title}` for link text).

Acceptance criteria:
- Block name: `dg-interview/link`
- Attributes:
  - `href` (string, default `"#"`)
  - `target` (string, default `""`)
  - generic `htmlAttributes` bag (for `id`, `class`, `style`, etc.) like other blocks
- Supports inner blocks (for link content)
- Renders an `<a>` element with `href` and `target` coming from block attributes
- Dynamic expressions in `href` and `target` are preserved in saved content and resolve in runtime rendering (PHP + builder preview)
- If `href`/`target` are omitted, defaults are used

Example behaviors:
- With `href="/about"` and `target="_blank"` and inner content `About us`, output includes `<a href="/about" target="_blank">About us</a>`
- With `href="{this.slug}"`, rendered output resolves the expression at runtime
- With no `href` provided, output falls back to `href="#"`
