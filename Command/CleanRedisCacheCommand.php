<?php

namespace SQLI\EzToolboxBundle\Command;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanRedisCacheCommand extends Command
{
    /** @var CacheItemPoolInterface */
    private $cachePool;

    public function __construct(CacheItemPoolInterface $cachePool)
    {
        $this->cachePool = $cachePool;
        parent::__construct('sqli:clear_redis_cache');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Clear Redis persistence cache');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // To clear all cache
        $this->cachePool->clear();

        return self::SUCCESS;
    }
}
