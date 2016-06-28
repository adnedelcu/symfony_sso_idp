<?php

namespace AppBundle\Manager;

use Everlution\Redlock\Manager\LockManager as RedLockManager;
use Everlution\Redlock\Model\Lock;
use Everlution\Redlock\Model\LockType;

use Psr\Log\LoggerInterface;

/**
 * Class LockManager.
 */
class LockManager
{
    /**
     * @var LockManagerFactory
     */
    private $lockFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LockManagerFactory $lockFactory
     */
    public function __construct(LockManagerFactory $lockFactory)
    {
        $this->lockFactory = $lockFactory;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger($logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * @param int $validity 60 secs default lock validity time
     * @param int $retries  retries
     * @param int $delay    max delay before retry
     *
     * @return RedLockManager
     */
    public function getLockManager($validity = 60, $retries = 3, $delay = 10)
    {
        return $this->lockFactory->getLockManager($validity, $retries, $delay);
    }

    /**
     * @param RedLockManager $lockManager
     * @param string $lockType
     * @param $resource
     * @param int $attempts
     *
     * @return Lock|null
     */
    public function lockOrWait(RedLockManager $lockManager, $lockType = LockType::EXCLUSIVE, $resource, $attempts = 10)
    {
        $count = 1;
        $lock = new Lock($resource, $lockType, uniqid());

        while (!$lockManager->acquireLock($lock) && $count <= $attempts) {
            if ($this->logger !== null) {
                $this->logger->critical(sprintf(
                    'Can\'t acquire lock for resource "%s" at %d attempt.',
                    $resource,
                    $count
                ));
            }

            usleep(500000);
            $count++;
        }

        if ($count >= $attempts) {
            return null;
        }

        return $lock;
    }
}
