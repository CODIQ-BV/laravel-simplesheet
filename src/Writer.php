<?php

namespace Nikazooz\Simplesheet;

use Nikazooz\Simplesheet\Concerns\WithEvents;
use Nikazooz\Simplesheet\Concerns\WithMultipleSheets;
use Nikazooz\Simplesheet\Events\BeforeExport;
use Nikazooz\Simplesheet\Events\BeforeWriting;
use Nikazooz\Simplesheet\Factories\WriterFactory;
use Nikazooz\Simplesheet\Files\RemoteTemporaryFile;
use Nikazooz\Simplesheet\Files\TemporaryFile;
use Nikazooz\Simplesheet\Files\TemporaryFileFactory;
use Nikazooz\Simplesheet\Writers\Sheet;
use Box\Spout\Reader\Common\Creator\ReaderFactory as SpoutReaderFactory;
use Box\Spout\Writer\Common\Creator\WriterFactory as SpoutWriterFactory;

class Writer
{
    use HasEventBus;

    /**
     * @var \Nikazooz\Simplesheet\Files\TemporaryFileFactory
     */
    protected $temporaryFileFactory;

    /**
     * @var \Box\Spout\Writer\WriterInterface
     */
    protected $spoutWriter;

    /**
     * @var int
     */
    protected $chunkSize;

    /**
     * @var object
     */
    protected $exportable;

    /**
     * New Writer instance.
     *
     * @param  \Nikazooz\Simplesheet\Files\TemporaryFileFactory  $temporaryFileFactory
     * @param  int  $chunkSize
     * @return void
     */
    public function __construct(TemporaryFileFactory $temporaryFileFactory, int $chunkSize)
    {
        $this->chunkSize = $chunkSize;
        $this->temporaryFileFactory = $temporaryFileFactory;
    }

    /**
     * @param object $export
     * @param string $writerType
     * @return TemporaryFile
     * @throws \Exception
     */
    public function export($export, string $writerType): TemporaryFile
    {
        $tempFile = $this->temporaryFileFactory->makeLocal(null, strtolower($writerType));
        $this->open($export, $writerType, $tempFile);

        $sheetExports = [$export];
        if ($export instanceof WithMultipleSheets) {
            $sheetExports = $export->sheets();
        }

        foreach ($sheetExports as $sheetIndex => $sheetExport) {
            $this->addNewSheet($sheetIndex)->export($sheetExport);
        }

        return $this->write($export, $tempFile, $writerType);
    }

    /**
     * @param  object  $export
     * @param  string  $writerType
     * @return $this
     */
    public function open($export, $writerType, TemporaryFile $temporaryFile)
    {
        $this->exportable = $export;

        if ($export instanceof WithEvents) {
            $this->registerListeners($export->registerEvents());
        }

        $this->exportable  = $export;

        $this->raise(new BeforeExport($this, $this->exportable));

        $this->spoutWriter = WriterFactory::make($writerType, $this->exportable);
        $this->spoutWriter->openToFile($temporaryFile->sync()->getLocalPath());

        return $this;
    }

    /**
     * @param TemporaryFile $tempFile
     * @param string $writerType
     *
     * @return Writer
     * @throws \Box\Spout\Common\Exception\IOException
     */
    public function reopen(TemporaryFile $tempFile, string $writerType, array $rowsToAppend = [], int $sheetIndexWhereRowWillBeAppend = null)
    {
        $extension = pathinfo($tempFile->getLocalPath(), PATHINFO_EXTENSION);

        $reader = SpoutReaderFactory::createFromFile($tempFile->getLocalPath());
        $reader->open($tempFile->getLocalPath());
        $reader->setShouldFormatDates(true);

        $newTempFile = $this->temporaryFileFactory->make($extension);

        $this->spoutWriter = SpoutWriterFactory::createFromType($writerType);
        $this->spoutWriter->openToFile($newTempFile->getLocalPath());

        foreach ($reader->getSheetIterator() as $sheetIndex => $sheet) {
            if ($sheetIndex !== 1) {
                $this->spoutWriter->addNewSheetAndMakeItCurrent();
            }

            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                $this->spoutWriter->addRow($row);
            }

            if ($sheetIndex === $sheetIndexWhereRowWillBeAppend + 1) {
                foreach ($rowsToAppend as $rowIndex => $row) {
                    $this->spoutWriter->addRow($row);
                }
            }
        }
        $reader->close();
        $this->spoutWriter->close();

        $tempFile->copyFrom($newTempFile->getLocalPath());

        return $this;
    }

    /**
     * @param  object  $export
     * @param  \Nikazooz\Simplesheet\Files\TemporaryFile  $temporaryFile
     * @param  string  $writerType
     * @return \Nikazooz\Simplesheet\Files\TemporaryFile
     */
    public function write($export, TemporaryFile $temporaryFile, string $writerType)
    {
        $this->throwExceptionIfWriterIsNotSet();

        $this->exportable = $export;

        $this->raise(new BeforeWriting($this, $this->exportable));

        $this->spoutWriter = WriterFactory::make($writerType, $export);

        $sheet = $this->getSheetByIndex(0);

        if ($temporaryFile instanceof RemoteTemporaryFile) {
            $temporaryFile->updateRemote();
        }

        return $temporaryFile;
    }

    /**
     * @param  \Nikazooz\Simplesheet\Concerns\WithMultipleSheets|object  $export
     * @return array
     */
    private function getSheetExports($export)
    {
        if ($export instanceof WithMultipleSheets) {
            return $export->sheets();
        }

        return [$export];
    }

    /**
     * @return void
     */
    public function cleanUp()
    {
        $this->spoutWriter->close();
        unset($this->spoutWriter);
    }

    /**
     * @param  int|null  $sheetIndex
     * @return \Nikazooz\Simplesheet\Writers\Sheet
     *
     * @throws \Exception
     */
    public function addNewSheet(int $sheetIndex = null)
    {
        $this->throwExceptionIfWriterIsNotSet();

        return new Sheet($this->spoutWriter, $sheetIndex, $this->chunkSize);
    }

    /**
     * @param int $sheetIndex
     *
     * @return Sheet
     */
    public function getSheetByIndex(int $sheetIndex)
    {
        return new Sheet($this->spoutWriter, $sheetIndex, $this->chunkSize);
    }

    /**
     * @param string $concern
     *
     * @return bool
     */
    public function hasConcern($concern): bool
    {
        return $this->exportable instanceof $concern;
    }

    /**
     * @throws \Exception
     */
    private function throwExceptionIfWriterIsNotSet()
    {
        if (! $this->spoutWriter) {
            throw new \Exception('Writer must be opened first!');
        }
    }
}
