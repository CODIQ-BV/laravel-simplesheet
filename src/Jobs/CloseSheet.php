<?php

namespace Nikazooz\Simplesheet\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Nikazooz\Simplesheet\Concerns\WithEvents;
use Nikazooz\Simplesheet\Files\TemporaryFile;
use Nikazooz\Simplesheet\Writer;

class CloseSheet implements ShouldQueue
{
    use Queueable, ProxyFailures;

    /**
     * @var object
     */
    private $sheetExport;

    /**
     * @var string
     */
    private $temporaryFile;

    /**
     * @var string
     */
    private $writerType;

    /**
     * @var int
     */
    private $sheetIndex;

    /**
     * @param  object  $sheetExport
     * @param  TemporaryFile  $temporaryFile
     * @param  string  $writerType
     * @param  int  $sheetIndex
     */
    public function __construct($sheetExport, TemporaryFile $temporaryFile, string $writerType, int $sheetIndex)
    {
        $this->sheetExport = $sheetExport;
        $this->temporaryFile = $temporaryFile;
        $this->writerType = $writerType;
        $this->sheetIndex = $sheetIndex;
    }

    /**
     * @param Writer $writer
     * @throws \Box\Spout\Common\Exception\IOException
     */
    public function handle(Writer $writer)
    {

    }
}
