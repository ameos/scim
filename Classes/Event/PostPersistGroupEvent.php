<?php

declare(strict_types=1);

namespace Ameos\Scim\Event;

use Ameos\Scim\Enum\Context;
use Ameos\Scim\Enum\PostPersistMode;

final class PostPersistGroupEvent
{
    /**
     * @param array $mapping
     * @param array $payload
     * @param array $record
     * @param PostPersistMode $mode
     * @param Context $context
     */
    public function __construct(
        private readonly array $mapping,
        private readonly array $payload,
        private readonly array $record,
        private readonly PostPersistMode $mode,
        private readonly Context $context
    ) {
    }

    /**
     * return mapping
     *
     * @return array
     */
    public function getMapping(): array
    {
        return $this->mapping;
    }

    /**
     * return payload
     *
     * @return array
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * return record
     *
     * @return array
     */
    public function getRecord(): array
    {
        return $this->record;
    }

    /**
     * return mode
     *
     * @return PostPersistMode
     */
    public function getMode(): PostPersistMode
    {
        return $this->mode;
    }

    /**
     * return context
     *
     * @return Context
     */
    public function getContext(): Context
    {
        return $this->context;
    }
}