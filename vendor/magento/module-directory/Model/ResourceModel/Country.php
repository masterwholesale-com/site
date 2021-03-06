<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Directory\Model\ResourceModel;

/**
 * Country Resource Model
 *
 * @api
 * @since 100.0.2
 */
class Country extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('directory_country', 'country_id');
    }

    /**
     * Load country by ISO code
     *
     * @param \Magento\Directory\Model\Country $country
     * @param string $code
     * @return \Magento\Directory\Model\ResourceModel\Country
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadByCode(\Magento\Directory\Model\Country $country, $code)
    {
        switch (strlen($code)) {
            case 2:
                $field = 'iso2_code';
                break;

            case 3:
                $field = 'iso3_code';
                break;

			default:
            	// 2020-05-22 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
				// "«Please correct the country code: .»
				// at vendor/magento/module-directory/Model/ResourceModel/Country.php:46":
				// https://github.com/masterwholesale-com/site/issues/2
            	df_log_l($this, ['code' => $code]);
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Please correct the country code: %1.', htmlspecialchars($code))
                );
        }

        return $this->load($country, $code, $field);
    }
}
