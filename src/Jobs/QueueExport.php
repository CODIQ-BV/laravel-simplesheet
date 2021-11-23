<?php

namespace Nikazooz\Simplesheet\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Nikazooz\Simplesheet\Files\TemporaryFile;
use Nikazooz\Simplesheet\Writer;
use Throwable;
use Nikazooz\Simplesheet\Concerns\WithMultipleSheets;

class QueueExport implements ShouldQueue
{
    use ExtendedQueueable, Dispatchable;

    /**
     * @var object
     */
    private $export;

    /**
     * @var TemporaryFile
     */
    private $temporaryFile;

    /**
     * @var string
     */
    private $writerType;

    /**
     * @param  object  $export
     * @param  TemporaryFile  $temporaryFile
     * @param  string  $writerType
     * @return void
     */
    public function __construct($export, TemporaryFile $temporaryFile, string $writerType)
    {
        $this->export = $export;
        $this->writerType = $writerType;
        $this->temporaryFile = $temporaryFile;
    }

    /**
     * Get the middleware the job should be dispatched through.
     *
     * @return array
     */
    public function middleware()
    {
        return (method_exists($this->export, 'middleware')) ? $this->export->middleware() : [];
    }

    /**
     * @param \Nikazooz\Simplesheet\Writer $writer
     * @return void
     * @throws \Exception
     */
    public function handle(Writer $writer)
    {
        $writer->open($this->export, $this->writerType);

        $sheetExports = [$this->export];
        if ($this->export instanceof WithMultipleSheets) {
            $sheetExports = $this->export->sheets();
        }

        // Pre-create the worksheets
        foreach ($sheetExports as $sheetIndex => $sheetExport) {
            //$writer->reopen($this->temporaryFile, $this->writerType);
            $sheet = $writer->addNewSheet($sheetIndex);
            $sheet->open($sheetExport);
        }

        // Write to temp file with empty sheets.
        $writer->write($sheetExport, $this->temporaryFile, $this->writerType);
    }

    /**
     * @param  \Throwable  $e
     * @return  void
     */
    public function failed(Throwable $e)
    {
        if (method_exists($this->export, 'failed')) {
            $this->export->failed($e);
        }
    }
}
