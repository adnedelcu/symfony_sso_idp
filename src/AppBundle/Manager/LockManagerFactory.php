<?php

namespace AppBundle\Manager;

use Everlution\Redlock\Adapter\PredisAdapter;
use Everlution\Redlock\KeyGenerator\DefaultKeyGenerator;
use Everlution\Redlock\Manager\LockTypeManager;
use Everlution\Redlock\Quorum\HalfPlusOneQuorum;

/**
 * Class LockManagerFactory.
 */
class LockManagerFactory
{
    /**
     * @var \Predis\Client
     */
    private $predis;

    /**
     * @param \Predis\Client $predis
     */
    public function __construct(\Predis\Client $predis)
    {
        $this->predis = $predis;
    }

    /**
     * @param int $validity 60 secs default lock validity time
     * @param int $retries  retries
     * @param int $delay    max delay before retry
     *
     * @return \Everlution\Redlock\Manager\LockManager
     */
    public function getLockManager($validity = 60, $retries = 3, $delay = 10)
    {
        $lockManager = new \Everlution\Redlock\Manager\LockManager(
            new HalfPlusOneQuorum(),
            new DefaultKeyGenerator(),
            new LockTypeManager(),
            $validity,
            $retries,
            $delay
        );

        $lockManager->addAdapter(new PredisAdapter($this->predis));

        return $lockManager;
    }
}
