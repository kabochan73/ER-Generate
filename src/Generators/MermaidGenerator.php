<?php

namespace Kabochan73\ErGenerate\Generators;

class MermaidGenerator
{
    /**
     * @param  array<string, array{columns: array<string, string>, foreignKeys: array<array{column: string, referencedTable: string, referencedColumn: string}>}>  $tables
     */
    public function generate(array $tables): string
    {
        $lines = ['erDiagram'];

        foreach ($tables as $tableName => $tableInfo) {
            $lines[] = "    {$tableName} {";

            foreach ($tableInfo['columns'] as $columnName => $columnType) {
                $lines[] = "        {$columnType} {$columnName}";
            }

            $lines[] = '    }';
        }

        foreach ($tables as $tableName => $tableInfo) {
            foreach ($tableInfo['foreignKeys'] as $fk) {
                $referencedTable = $fk['referencedTable'];

                if (! isset($tables[$referencedTable])) {
                    continue;
                }

                $lines[] = "    {$referencedTable} ||--o{ {$tableName} : \"\"";
            }
        }

        return implode("\n", $lines) . "\n";
    }
}
