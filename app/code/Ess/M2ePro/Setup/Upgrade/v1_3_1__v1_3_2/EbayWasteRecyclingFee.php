<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_3_1__v1_3_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class EbayWasteRecyclingFee extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return [];
    }

    public function execute()
    {
        $this->getTableModifier('ebay_order_item')
             ->addColumn('waste_recycling_fee', 'DECIMAL(12, 4) NOT NULL', '0.0000', 'final_fee');
    }

    //########################################
}