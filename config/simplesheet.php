<?php

use Nikazooz\Simplesheet\Simplesheet;

return [
    'exports' => [

        /*
        |--------------------------------------------------------------------------
        | Chunk size
        |--------------------------------------------------------------------------
        |
        | When using FromQuery, the query is automatically chunked.
        | Here you can specify how big the chunk should be.
        |
        */
        'chunk_size' => 1000,

        /*
        |--------------------------------------------------------------------------
        | Temporary path
        |--------------------------------------------------------------------------
        |
        | When exporting files, we use a temporary file, before storing
        | or downloading. Here you can customize that path.
        |
        */
        'temp_path' => sys_get_temp_dir(),

        /*
        |--------------------------------------------------------------------------
        | CSV Settings
        |--------------------------------------------------------------------------
        |
        | Configure e.g. delimiter, enclosure and line ending for CSV exports.
        |
        */
        'csv' => [
            'delimiter' => ',',
            'enclosure' => '"',
            'line_ending' => PHP_EOL,
            'use_bom' => false,
            'include_separator_line' => false,
            'excel_compatibility' => false,
        ],
    ],

    'imports' => [

        'read_only' => true,

        'heading_row' => [

            /*
            |--------------------------------------------------------------------------
            | Heading Row Formatter
            |--------------------------------------------------------------------------
            |
            | Configure the heading row formatter.
            | Available options: none|slug|custom
            |
            */
            'formatter' => 'slug',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Extension detector
    |--------------------------------------------------------------------------
    |
    | Configure here which writer type should be used when
    | the package needs to guess the correct type
    | based on the extension alone.
    |
    */
    'extension_detector' => [
        'xlsx' => Simplesheet::XLSX,
        'xlsm' => Simplesheet::XLSX,
        'xltx' => Simplesheet::XLSX,
        'xltm' => Simplesheet::XLSX,
        'ods' => Simplesheet::ODS,
        'ots' => Simplesheet::ODS,
        'csv' => Simplesheet::CSV,
        'tsv' => Simplesheet::TSV,
    ],
];
