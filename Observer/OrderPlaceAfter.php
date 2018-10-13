<?php
/**
 * Copyright © Swarming Technology, LLC. All rights reserved.
 */
namespace Buzzi\PublishCartPurchase\Observer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Buzzi\PublishCartPurchase\Model\DataBuilder;

class OrderPlaceAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Buzzi\Publish\Model\Config\Events
     */
    private $configEvents;

    /**
     * @var \Buzzi\Publish\Api\QueueInterface
     */
    private $queue;

    /**
     * @var \Buzzi\PublishCartPurchase\Model\DataBuilder
     */
    private $dataBuilder;

    /**
     * @var \Buzzi\Publish\Helper\ExceptsMarketing
     */
    private $exceptsMarketingHelper;

    /**
     * @param \Buzzi\Publish\Model\Config\Events $configEvents
     * @param \Buzzi\Publish\Api\QueueInterface $queue
     * @param \Buzzi\PublishCartPurchase\Model\DataBuilder $dataBuilder
     * @param \Buzzi\Publish\Helper\ExceptsMarketing|null $exceptsMarketingHelper
     */
    public function __construct(
        \Buzzi\Publish\Model\Config\Events $configEvents,
        \Buzzi\Publish\Api\QueueInterface $queue,
        \Buzzi\PublishCartPurchase\Model\DataBuilder $dataBuilder,
        \Buzzi\Publish\Helper\ExceptsMarketing $exceptsMarketingHelper = null
    ) {
        $this->configEvents = $configEvents;
        $this->queue = $queue;
        $this->dataBuilder = $dataBuilder;
        $this->exceptsMarketingHelper = $exceptsMarketingHelper ?: ObjectManager::getInstance()->get(\Buzzi\Publish\Helper\ExceptsMarketing::class);
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getData('order');
        $storeId = $order->getStoreId();

        if (!$this->configEvents->isEventEnabled(DataBuilder::EVENT_TYPE, $storeId)
            || !$this->exceptsMarketingHelper->isExcepts(DataBuilder::EVENT_TYPE, $storeId)
        ) {
            return;
        }

        $payload = $this->dataBuilder->getPayload($order);

        if ($this->configEvents->isCron(DataBuilder::EVENT_TYPE, $storeId)) {
            $this->queue->add(DataBuilder::EVENT_TYPE, $payload, $storeId);
        } else {
            $this->queue->send(DataBuilder::EVENT_TYPE, $payload, $storeId);
        }
    }
}
