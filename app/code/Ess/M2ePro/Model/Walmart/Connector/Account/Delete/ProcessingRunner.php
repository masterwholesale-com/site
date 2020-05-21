<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Account\Delete;

/**
 * Class ProcessingRunner
 * @package Ess\M2ePro\Model\Walmart\Connector\Account\Delete
 */
class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner\Single
{
    //########################################

    protected function setLocks()
    {
        parent::setLocks();

        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'Account',
            $params['account_id']
        );

        $account->addProcessingLock(null, $this->getProcessingObject()->getId());
        $account->addProcessingLock('server_synchronize', $this->getProcessingObject()->getId());
        $account->addProcessingLock('deleting_from_server', $this->getProcessingObject()->getId());
    }

    protected function unsetLocks()
    {
        parent::unsetLocks();

        $params = $this->getParams();

        try {
            /** @var \Ess\M2ePro\Model\Account $account */
            $account = $this->parentFactory->getCachedObjectLoaded(
                \Ess\M2ePro\Helper\Component\Walmart::NICK,
                'Account',
                $params['account_id']
            );

            $account->deleteProcessingLocks(null, $this->getProcessingObject()->getId());
            $account->deleteProcessingLocks('server_synchronize', $this->getProcessingObject()->getId());
            $account->deleteProcessingLocks('deleting_from_server', $this->getProcessingObject()->getId());
        } catch (\Exception $exception) {
        }
    }

    //########################################
}
