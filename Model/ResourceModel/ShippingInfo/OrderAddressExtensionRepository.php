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
 * @package   Dhl\Shipping\Model
 * @author    Benjamin Heuer <benjamin.heuer@netresearch.de>
 * @copyright 2018 Netresearch GmbH & Co. KG
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.netresearch.de/
 */
namespace Dhl\Shipping\Model\ResourceModel\ShippingInfo;

use Dhl\Shipping\Api\Data\OrderAddressExtensionInterface;
use Dhl\Shipping\Api\Data\ShippingInfoInterface;
use Dhl\Shipping\Api\OrderAddressExtensionRepositoryInterface;
use Dhl\Shipping\Model\ShippingInfo\AbstractAddressExtension;
use Dhl\Shipping\Model\ShippingInfo\OrderAddressExtensionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;

//use Dhl\Shipping\Api\Data\OrderAddressExtensionInterfaceFactory;

/**
 * Repository for DHL Shipping Quote Address Extension
 *
 * @package  Dhl\Shipping\Model
 * @author   Benjamin Heuer <benjamin.heuer@netresearch.de>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link     http://www.netresearch.de/
 */
class OrderAddressExtensionRepository implements OrderAddressExtensionRepositoryInterface
{
    /**
     * @var OrderAddressExtension
     */
    private $resource;

    /**
     * @var OrderAddressExtensionFactory
     */
    private $addressExtensionFactory;

    /**
     * OrderAddressExtensionRepository constructor.
     * @param OrderAddressExtension $resource
     * @param OrderAddressExtensionFactory $addressExtensionFactory
     */
    public function __construct(
        OrderAddressExtension $resource,
        OrderAddressExtensionFactory $addressExtensionFactory
    ) {
        $this->resource = $resource;
        $this->addressExtensionFactory = $addressExtensionFactory;
    }

    /**
     * Save Shipping Address Extension. PK equals Order Address ID.
     *
     * @param OrderAddressExtensionInterface|AbstractModel $addressExtension
     * @return OrderAddressExtensionInterface
     * @throws CouldNotSaveException
     */
    public function save(OrderAddressExtensionInterface $addressExtension)
    {
        try {
            $this->resource->save($addressExtension);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $addressExtension;
    }

    /**
     * Retrieve DHL Shipping Info by Address id.
     *
     * @param int $addressId Order Address ID or Quote Address ID
     * @return OrderAddressExtensionInterface
     * @throws NoSuchEntityException
     */
    public function getById($addressId)
    {
        $addressExtension = $this->addressExtensionFactory->create();
        $this->resource->load($addressExtension, $addressId);

        if (!$addressExtension->getId()) {
            throw new NoSuchEntityException(__('Order address with id "%1" does not exist.', $addressId));
        }

        return $addressExtension;
    }

    /**
     * Delete Shipping Address Extension
     *
     * @param OrderAddressExtensionInterface|AbstractModel $addressExtension
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(OrderAddressExtensionInterface $addressExtension)
    {
        try {
            $this->resource->delete($addressExtension);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * Delete Shipping Address Extension using Order Address ID.
     *
     * @param int $addressId
     * @return bool
     */
    public function deleteById($addressId)
    {
        $addressExtension = $this->getById($addressId);
        return $this->delete($addressExtension);
    }

    /**
     * @param int $addressId Order Address ID
     * @return ShippingInfoInterface|null
     * @throws NoSuchEntityException
     */
    public function getShippingInfo($addressId)
    {
        /** @var AbstractAddressExtension $addressExtension */
        $addressExtension = $this->getById($addressId);
        return $addressExtension->getShippingInfo();
    }
}
