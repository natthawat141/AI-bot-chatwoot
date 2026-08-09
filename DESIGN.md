# Management Design System

## Direction

Quiet dark workspace inspired by Notion: low-chroma surfaces, clear type hierarchy,
compact controls, and no decorative color. The interface should feel calm at the point
where an operator is editing business data.

## Color tokens

| Role | Token | Value |
| --- | --- | --- |
| App background | `--mg-bg` | `#09090b` |
| Secondary surface | `--mg-surface-1` | `#101012` |
| Primary surface | `--mg-surface-2` | `#18181b` |
| Raised surface | `--mg-surface-3` | `#202023` |
| Border | `--mg-border` | `#2f2f34` |
| Strong border | `--mg-border-strong` | `#414149` |
| Primary ink | `--mg-ink` | `#f4f4f5` |
| Secondary ink | `--mg-ink-muted` | `#b4b4bc` |
| Tertiary ink | `--mg-ink-subtle` | `#92929d` |
| Focus | `--mg-focus` | `#a1a1aa` |

Semantic colors remain reserved for feedback: red for errors and destructive actions,
amber for warnings, and green only for successful state. Blue is not used as product chrome.

## Typography

Use one family, Noto Sans Thai with system fallbacks. Keep headings compact, labels medium,
body text readable, and avoid all-caps UI copy. Thai and English labels share the same scale.

## Components

- Buttons: 40px minimum height, 6px radius, one primary treatment, clear disabled state.
- Inputs: 40px minimum height, 6px radius, dark surface, visible focus ring, inline error text.
- Cards and tables: 8px radius, border-led separation, no decorative drop shadow.
- Sidebar: secondary surface with one selected navigation row.
- Forms: grouped sections with consistent label, hint, field, and action spacing.

## Responsive behavior

The sidebar collapses to the existing mobile drawer below the large breakpoint. Form grids
collapse to one column on small screens. Tables retain horizontal scrolling rather than
shrinking text below readable sizes.

## Accessibility

Target WCAG 2.2 AA contrast, preserve visible keyboard focus, keep semantic status text in
addition to status color, and respect reduced-motion preferences.
