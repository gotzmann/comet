<?php

declare(strict_types=1);

namespace Meteor\Psr;

use Meteor\Stream;
use Meteor\Utils;
use InvalidArgumentException;
use RuntimeException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

class UploadedFile implements UploadedFileInterface
{
    private const ERRORS = [
        UPLOAD_ERR_OK,
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE,
        UPLOAD_ERR_PARTIAL,
        UPLOAD_ERR_NO_FILE,
        UPLOAD_ERR_NO_TMP_DIR,
        UPLOAD_ERR_CANT_WRITE,
        UPLOAD_ERR_EXTENSION,
    ];

    private ?string $file;

    private bool $moved = false;

    private ?StreamInterface $stream;

    public function __construct(
        private StreamInterface | string $streamOrFile,
        private readonly ?int $size,
        private readonly int $error,
        private readonly ?string $clientFilename = null,
        private readonly ?string $clientMediaType = null
    ) {
        if (false === in_array($this->error, self::ERRORS, true)) {
            throw new InvalidArgumentException(
                'Invalid error status for UploadedFile'
            );
        }

        if ($this->isOk()) {
            $this->setStreamOrFile($streamOrFile);
        }
    }

    private function setStreamOrFile(mixed $streamOrFile): void
    {
        if (is_string($streamOrFile)) {
            $this->file = $streamOrFile;
        } elseif (is_resource($streamOrFile)) {
            $this->stream = new Stream($streamOrFile);
        } elseif ($streamOrFile instanceof StreamInterface) {
            $this->stream = $streamOrFile;
        } else {
            throw new InvalidArgumentException(
                'Invalid stream or file provided for UploadedFile'
            );
        }
    }

    private function isStringNotEmpty($param): bool
    {
        return is_string($param) && false === empty($param);
    }

    /**
     * Return true if there is no upload error
     */
    private function isOk(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    public function isMoved(): bool
    {
        return $this->moved;
    }

    /**
     * @throws RuntimeException if is moved or not ok
     */
    private function validateActive(): void
    {
        if (false === $this->isOk()) {
            throw new RuntimeException('Cannot retrieve stream due to upload error');
        }

        if ($this->isMoved()) {
            throw new RuntimeException('Cannot retrieve stream after it has already been moved');
        }
    }

    public function getStream(): StreamInterface
    {
        $this->validateActive();

        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }

        /** @var string $file */
        $file = $this->file;

        return new LazyOpenStream($file, 'r+');
    }

    public function moveTo($targetPath): void
    {
        $this->validateActive();

        if (false === $this->isStringNotEmpty($targetPath)) {
            throw new InvalidArgumentException(
                'Invalid path provided for move operation; must be a non-empty string'
            );
        }

        if ($this->file) {
            $this->moved = PHP_SAPI === 'cli'
                ? rename($this->file, $targetPath)
                : move_uploaded_file($this->file, $targetPath);
        } else {
            Utils::copyToStream(
                $this->getStream(),
                new LazyOpenStream($targetPath, 'w')
            );

            $this->moved = true;
        }

        if (false === $this->moved) {
            throw new RuntimeException(
                sprintf('Uploaded file could not be moved to %s', $targetPath)
            );
        }
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }
}
