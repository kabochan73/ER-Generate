# er-generate

A Laravel package that generates [Mermaid](https://mermaid.js.org/) ER diagrams from your migration files.

## Requirements

- PHP 8.1+
- Laravel 10, 11, 12, or 13

## Installation

```bash
composer require kabochan73/er-generate
```

The service provider is auto-discovered by Laravel — no manual registration needed.

## Usage

```bash
php artisan er:generate
```

Generates `er.html` in the current directory. Open it in any browser to view the diagram.

```bash
# macOS
open er.html

# Linux
xdg-open er.html

# Windows
start er.html
```

## Output

**er.html** — a standalone HTML file with an interactive Mermaid ER diagram.

```
users ||--o{ posts : ""
```

Relationships are automatically detected from `foreignId()->constrained()` and `foreign()->references()->on()` syntax.

## License

MIT
