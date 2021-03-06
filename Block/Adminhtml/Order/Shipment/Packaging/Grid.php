<?php
/**
 * Dhl Shipping
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to
 * newer versions in the future.
 *
 * PHP version 7
 *
 * @package   Dhl\Shipping\Block
 * @author    Sebastian Ertner <sebastian.ertner@netresearch.de>
 * @copyright 2018 Netresearch GmbH & Co. KG
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.netresearch.de/
 */

namespace Dhl\Shipping\Block\Adminhtml\Order\Shipment\Packaging;

use Dhl\Shipping\Model\Attribute\Backend\ExportDescription;
use Dhl\Shipping\Model\Attribute\Backend\TariffNumber;
use Dhl\Shipping\Model\Attribute\Source\DGCategory;
use Dhl\Shipping\Model\Config\ModuleConfigInterface;
use Dhl\Shipping\Util\Escaper;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory as CountryCollectionFactory;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order\Shipment\Item;
use Magento\Sales\Model\Order\Shipment\ItemFactory;
use Magento\Shipping\Block\Adminhtml\Order\Packaging\Grid as MagentoGrid;

/**
 * Grid
 *
 * @package Dhl\Shipping\Block
 * @author  Sebastian Ertner <sebastian.ertner@netresearch.de>
 * @author  Max Melzer <max.melzer@netresearch.de>
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link    http://www.netresearch.de/
 */
class Grid extends MagentoGrid
{

    const BCS_GRID_TEMPLATE = 'Dhl_Shipping::order/packaging/grid/bcs.phtml';
    const GL_GRID_TEMPLATE  = 'Dhl_Shipping::order/packaging/grid/gl.phtml';
    const STANDARD_TEMPLATE = 'Magento_Shipping::order/packaging/grid.phtml';

    /**
     * @var ModuleConfigInterface
     */
    private $moduleConfig;

    /**
     * @var CountryCollectionFactory
     */
    private $countryCollectionFactory;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var string[]
     */
    private $countriesOfManufacture = [];

    /**
     * @var string[]
     */
    private $dangerousGoodsCategories = [];

    /**
     * @var string[]
     */
    private $tariffNumbers = [];

    /**
     * @var string[]
     */
    private $exportDescriptions = [];

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * Grid constructor.
     *
     * @param Context                  $context
     * @param ItemFactory              $shipmentItemFactory
     * @param Registry                 $registry
     * @param ModuleConfigInterface    $moduleConfig
     * @param CountryCollectionFactory $countryCollectionFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param Escaper                   $escaper
     * @param mixed[]                  $data
     */
    public function __construct(
        Context $context,
        ItemFactory $shipmentItemFactory,
        Registry $registry,
        ModuleConfigInterface $moduleConfig,
        CountryCollectionFactory $countryCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        Escaper $escaper,
        array $data = []
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->countryCollectionFactory = $countryCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->escaper = $escaper;

        parent::__construct(
            $context,
            $shipmentItemFactory,
            $registry,
            $data
        );
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        $originCountryId = $this->moduleConfig->getShipperCountry($this->getShipment()->getStoreId());
        $destCountryId = $this->getShipment()->getShippingAddress()->getCountryId();
        $bcsCountries = ['DE', 'AT'];

        $isCrossBorder = $this->moduleConfig->isCrossBorderRoute($destCountryId, $this->getShipment()->getStoreId());
        $usedTemplate = self::STANDARD_TEMPLATE;

        if ($isCrossBorder && in_array($originCountryId, $bcsCountries)) {
            $usedTemplate = self::BCS_GRID_TEMPLATE;
        } elseif ($isCrossBorder && !in_array($originCountryId, $bcsCountries)) {
            $usedTemplate = self::GL_GRID_TEMPLATE;
        }

        return $usedTemplate;
    }

    /**
     * Obtain the given product's tariff number
     *
     * @param int $productId
     *
     * @return string
     */
    public function getTariffNumber($productId)
    {
        if (empty($this->tariffNumbers)) {
            /** @var Item[] $items */
            $this->initItemAttributes();
        }

        return $this->tariffNumbers[$productId];
    }

    /**
     * Obtain the "Export Description" attribute value for a given product.
     *
     * @param int $productId
     *
     * @return string
     */
    public function getExportDescription($productId)
    {
        if (empty($this->exportDescriptions)) {
            /** @var Item[] $items */
            $this->initItemAttributes();
        }

        return $this->exportDescriptions[$productId];
    }

    /**
     * Obtain the given product's country of manufacture.
     *
     * @param int $productId
     *
     * @return string
     */
    public function getCountryOfManufacture($productId)
    {
        if (empty($this->countriesOfManufacture)) {
            /** @var Item[] $items */
            $this->initItemAttributes();
        }

        if (!isset($this->countriesOfManufacture[$productId])) {
            // fallback to shipper country
            return $this->moduleConfig->getShipperCountry($this->getShipment()->getStoreId());
        }

        return $this->countriesOfManufacture[$productId];
    }

    /**
     * Get countries for select field.
     *
     * @return array
     */
    public function getCountries()
    {
        $countryCollection = $this->countryCollectionFactory->create();
        return $countryCollection->toOptionArray();
    }

    /**
     * @param int $productId
     *
     * @return string
     */
    public function getDangerousGoodsCategory($productId)
    {
        if (empty($this->dangerousGoodsCategories)) {
            $this->initItemAttributes();
        }

        return $this->dangerousGoodsCategories[$productId];
    }

    /**
     * Initialize attribute maps
     */
    private function initItemAttributes()
    {
        $productIds = [];
        /** @var Item[] $items */
        $items = $this->getCollection();
        foreach ($items as $item) {
            $productIds[] = $item->getProductId();
        }

        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addStoreFilter($this->getShipment()->getStoreId())
            ->addFieldToFilter(
                'entity_id',
                ['in' => $productIds]
            )->addAttributeToSelect(
                'country_of_manufacture',
                true
            )->addAttributeToSelect(
                DGCategory::CODE,
                true
            )->addAttributeToSelect(
                TariffNumber::CODE,
                true
            )->addAttributeToSelect(
                ExportDescription::CODE,
                true
            )
        ;

        while ($product = $productCollection->fetchItem()) {
            $this->countriesOfManufacture[$product->getId()] = $product->getData('country_of_manufacture');
            $this->dangerousGoodsCategories[$product->getId()] = $product->getData(DGCategory::CODE);
            $this->tariffNumbers[$product->getId()] = $product->getData(TariffNumber::CODE);
            $this->exportDescriptions[$product->getId()] = $product->getData(ExportDescription::CODE);
        }
    }

    /**
     * Escape a string for the HTML attribute context.
     *
     * @param string  $string
     * @param boolean $escapeSingleQuote
     *
     * @return string
     */
    public function escapeHtmlAttr($string, $escapeSingleQuote = true)
    {
        return $this->escaper->escapeHtmlAttr($string, $escapeSingleQuote);
    }
}
