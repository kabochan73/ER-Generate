<?php

namespace Kabochan73\ErGenerate\Parsers;

class MigrationParser
{
    /**
     * @param  string[]  $filePaths
     * @return array<string, array{columns: array<string, string>, foreignKeys: array<array{column: string, referencedTable: string, referencedColumn: string}>}>
     */
    public function parse(array $filePaths): array
    {
        $tables = [];

        foreach ($filePaths as $filePath) {
            $content = file_get_contents($filePath);

            foreach ($this->extractSchemaBlocks($content) as $tableName => $blockContent) {
                $tables[$tableName] = [
                    'columns'     => $this->extractColumns($blockContent),
                    'foreignKeys' => $this->extractForeignKeys($blockContent),
                ];
            }
        }

        return $tables;
    }

    /**
     * @return array<string, string>
     */
    private function extractSchemaBlocks(string $content): array
    {
        $blocks = [];

        $pattern = "/Schema::create\s*\(\s*'([^']+)'\s*,\s*function[^{]*/";
        preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $i => $match) {
            $tableName  = $matches[1][$i][0];
            $searchFrom = $match[1] + strlen($match[0]);

            $braceStart = strpos($content, '{', $searchFrom);
            if ($braceStart === false) {
                continue;
            }

            $depth = 1;
            $pos   = $braceStart + 1;
            $len   = strlen($content);

            while ($pos < $len && $depth > 0) {
                if ($content[$pos] === '{') {
                    $depth++;
                } elseif ($content[$pos] === '}') {
                    $depth--;
                }
                $pos++;
            }

            $blocks[$tableName] = substr($content, $braceStart + 1, $pos - $braceStart - 2);
        }

        return $blocks;
    }

    /**
     * @return array<string, string>
     */
    private function extractColumns(string $blockContent): array
    {
        $columns = [];

        $pattern = '/\$table->(\w+)\s*\(([^)]*)\)/';
        preg_match_all($pattern, $blockContent, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $method = $match[1];
            $args   = $match[2];

            switch ($method) {
                case 'id':
                    $columns['id'] = 'bigint';
                    continue 2;
                case 'rememberToken':
                    $columns['remember_token'] = 'varchar';
                    continue 2;
                case 'timestamps':
                    $columns['created_at'] = 'timestamp';
                    $columns['updated_at'] = 'timestamp';
                    continue 2;
                case 'softDeletes':
                    $columns['deleted_at'] = 'timestamp';
                    continue 2;
            }

            if (in_array($method, $this->getNonColumnMethods(), true)) {
                continue;
            }

            if (preg_match("/'([^']+)'/", $args, $argMatch)) {
                $columns[$argMatch[1]] = $this->normalizeType($method);
            }
        }

        return $columns;
    }

    /**
     * @return array<array{column: string, referencedTable: string, referencedColumn: string}>
     */
    private function extractForeignKeys(string $blockContent): array
    {
        $foreignKeys = [];

        $pattern = '/\$table->foreignId\s*\(\s*\'([^\']+)\'\s*\)(?:[^;]*?->constrained\s*\(\s*(?:\'([^\']+)\')?\s*\))?/';
        preg_match_all($pattern, $blockContent, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $column          = $match[1];
            $referencedTable = ! empty($match[2]) ? $match[2] : $this->guessTableName($column);

            $foreignKeys[] = [
                'column'           => $column,
                'referencedTable'  => $referencedTable,
                'referencedColumn' => 'id',
            ];
        }

        $pattern = '/\$table->foreign\s*\(\s*\'([^\']+)\'\s*\)\s*->references\s*\(\s*\'([^\']+)\'\s*\)\s*->on\s*\(\s*\'([^\']+)\'\s*\)/';
        preg_match_all($pattern, $blockContent, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $foreignKeys[] = [
                'column'           => $match[1],
                'referencedTable'  => $match[3],
                'referencedColumn' => $match[2],
            ];
        }

        return $foreignKeys;
    }

    private function guessTableName(string $columnName): string
    {
        $base = preg_replace('/_id$/', '', $columnName);

        return $base . 's';
    }

    private function normalizeType(string $type): string
    {
        return match ($type) {
            'id', 'increments', 'bigIncrements', 'unsignedBigInteger', 'foreignId' => 'bigint',
            'integer', 'unsignedInteger', 'tinyInteger', 'smallInteger',
            'unsignedSmallInteger', 'unsignedTinyInteger', 'bigInteger'             => 'int',
            'string', 'char'                                                        => 'varchar',
            'text', 'mediumText', 'longText'                                        => 'text',
            'boolean'                                                               => 'boolean',
            'decimal', 'float', 'double'                                            => 'decimal',
            'date'                                                                  => 'date',
            'dateTime', 'timestamp'                                                 => 'datetime',
            'json', 'jsonb'                                                         => 'json',
            'uuid'                                                                  => 'uuid',
            default                                                                 => $type,
        };
    }

    private function getNonColumnMethods(): array
    {
        return [
            'primary', 'unique', 'index', 'foreign',
            'constrained', 'cascadeOnDelete', 'cascadeOnUpdate',
            'nullOnDelete', 'restrictOnDelete',
        ];
    }
}
