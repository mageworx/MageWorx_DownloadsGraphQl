<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\DownloadsGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageWorx\Downloads\Helper\Data as HelperData;
use MageWorx\DownloadsGraphQl\Model\AttachmentsDataProvider;

class FileDownloads implements ResolverInterface
{
    /**
     * @var AttachmentsDataProvider
     */
    protected $attachmentsDataProvider;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * FileDownloads constructor.
     *
     * @param AttachmentsDataProvider $attachmentsDataProvider
     * @param HelperData $helperData
     */
    public function __construct(AttachmentsDataProvider $attachmentsDataProvider, HelperData $helperData)
    {
        $this->helperData              = $helperData;
        $this->attachmentsDataProvider = $attachmentsDataProvider;
    }

    /**
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return Value|mixed
     * @throws LocalizedException
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $attachmentIds = [];
        $sectionIds    = [];

        if (!empty($args['attachmentIds']) && is_array($args['attachmentIds'])) {
            $attachmentIds = $args['attachmentIds'];
        }

        if (!empty($args['sectionIds']) && is_array($args['sectionIds'])) {
            $sectionIds = $args['sectionIds'];
        }

        $storeId    = (int)$context->getExtensionAttributes()->getStore()->getId();
        $customerId = $context->getExtensionAttributes()->getIsCustomer() ? (int)$context->getUserId() : 0;
        $items      = $this->attachmentsDataProvider->getData($storeId, $customerId, $attachmentIds, $sectionIds);

        return [
            'block_title'             => $this->helperData->getFileDownloadsTitle(),
            'is_group_by_section'     => $this->helperData->isGroupBySection(),
            'items'                   => $items,
            'how_to_download_message' => $this->helperData->getHowToDownloadMessage($storeId, true)
        ];
    }
}
