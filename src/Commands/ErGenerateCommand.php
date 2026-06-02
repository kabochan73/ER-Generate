<?php

namespace Kabochan73\ErGenerate\Commands;

use Illuminate\Console\Command;
use Kabochan73\ErGenerate\Generators\MermaidGenerator;
use Kabochan73\ErGenerate\Parsers\MigrationParser;

class ErGenerateCommand extends Command
{
    protected $signature = 'er:generate';

    protected $description = 'Generate a Mermaid ER diagram from Laravel migration files';

    public function handle(MigrationParser $parser, MermaidGenerator $generator): int
    {
        $migrationPath = $this->getLaravel()->databasePath('migrations');

        if (! is_dir($migrationPath)) {
            $this->error("Migration directory not found: {$migrationPath}");

            return self::FAILURE;
        }

        $files = glob($migrationPath . '/**/*.php') ?: [];
        $files = array_merge($files, glob($migrationPath . '/*.php') ?: []);
        sort($files);

        if (empty($files)) {
            $this->warn('No migration files found.');

            return self::SUCCESS;
        }

        $this->info('Parsing ' . count($files) . ' migration file(s)...');

        $tables  = $parser->parse($files);
        $diagram = $generator->generate($tables);

        file_put_contents('er.html', $this->wrapHtml($diagram));
        $this->info('ER diagram saved to: er.html');

        return self::SUCCESS;
    }

    private function wrapHtml(string $diagram): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>ER Diagram</title>
            <script type="module">
                import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.esm.min.mjs';
                mermaid.initialize({ startOnLoad: true });
            </script>
        </head>
        <body>
            <pre class="mermaid">
        {$diagram}
            </pre>
        </body>
        </html>
        HTML;
    }
}
