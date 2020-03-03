<?php

namespace Nikazooz\Simplesheet\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Nikazooz\Simplesheet\Concerns\WithEvents;
use Nikazooz\Simplesheet\Events\ImportFailed;
use Nikazooz\Simplesheet\Files\TemporaryFile;
use Nikazooz\Simplesheet\Reader;
use Throwable;

class QueueImport implements ShouldQueue
{
    use ExtendedQueueable, Dispatchable;

    /**
     * @var object
     */
    public $import;

    /**
     * @var \Nikazooz\Simplesheet\Files\TemporaryFile
     */
    public $temporaryFile;

    /**
     * @var string
     */
    public $readerType;

    /**
     * @param  object  $import
     * @param  \Nikazooz\Simplesheet\Files\TemporaryFile  $temporaryFile
     * @param  string  $readerType
     * @return void
     */
    public function __construct($import, TemporaryFile $temporaryFile, string $readerType)
    {
        $this->import = $import;
        $this->readerType = $readerType;
        $this->temporaryFile = $temporaryFile;
    }

    /**
     * @param  \Nikazooz\Simplesheet\Reader  $reader
     * @return void
     */
    public function handle(Reader $reader)
    {
        $reader->readNow($this->import, $this->temporaryFile->sync(), $this->readerType);
    }

    /**
     * @param  \Throwable  $e
     * @return void
     */
    public function failed(Throwable $e)
    {
        if ($this->import instanceof WithEvents) {
            $this->registerListeners($this->import->registerEvents());
            $this->raise(new ImportFailed($e));

            if (method_exists($this->import, 'failed')) {
                $this->import->failed($e);
            }
        }
    }
}
