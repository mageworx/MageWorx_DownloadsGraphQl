<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\DownloadsGraphQl\Model;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\UrlInterface;
use MageWorx\Downloads\Helper\Data as HelperData;
use MageWorx\Downloads\Model\Attachment\Source\FileSize;
use MageWorx\Downloads\Model\Attachment;

class AttachmentsDataProvider
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * Url Builder
     *
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var int|null
     */
    protected $customerId;

    /**
     * @var int|null
     */
    protected $customerGroupId;

    /**
     * @var \MageWorx\Downloads\Api\AttachmentManagerInterface
     */
    protected $attachmentManager;

    /**
     * AttachmentsDataProvider constructor.
     *
     * @param HelperData $helperData
     * @param UrlInterface $urlBuilder
     * @param \MageWorx\Downloads\Api\AttachmentManagerInterface $attachmentManager
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        HelperData $helperData,
        UrlInterface $urlBuilder,
        \MageWorx\Downloads\Api\AttachmentManagerInterface $attachmentManager,
        ResourceConnection $resourceConnection
    ) {
        $this->helperData                  = $helperData;
        $this->urlBuilder                  = $urlBuilder;
        $this->attachmentManager           = $attachmentManager;
        $this->resourceConnection          = $resourceConnection;
    }

    /**
     * @param int $storeId
     * @param int $customerId
     * @param array|null $attachmentIds
     * @param array|null $sectionIds
     * @return array|null
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getData(
        int $storeId,
        int $customerId,
        ?array $attachmentIds = null,
        ?array $sectionIds = null
    ): ?array {
        $this->customerId = $customerId;
        $attachments      = $this->getAttachments($storeId, $attachmentIds, $sectionIds);

        if (empty($attachments)) {
            return null;
        }

        return $this->getPreparedData($attachments);
    }

    /**
     * @param int $productId
     * @param int $storeId
     * @param int $customerId
     * @return array|null
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getDataByProductId(int $productId, int $storeId, int $customerId): ?array
    {
        $this->customerId = $customerId;
        $attachments      = $this->getAttachments($storeId, null, null, $productId);

        if (empty($attachments)) {
            return null;
        }

        return $this->getPreparedData($attachments);
    }

    /**
     * @param array $attachments
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function getPreparedData(array $attachments): array
    {
        $data = [];

        /** @var Attachment $attachment */
        foreach ($attachments as $attachment) {
            $data[$attachment->getId()] = [
                'icon_type'        => $this->getIconType($attachment),
                'id'               => (int)$attachment->getId(),
                'name'             => (string)$attachment->getName(),
                'url'              => $attachment->getIsInGroup() ? $this->getAttachmentLink($attachment) : '',
                'size_str'         => $this->getSizeStr($attachment),
                'downloads_number' => $this->getDownloadsNumber($attachment),
                'description'      => (string)$attachment->getDescription(),
                'section_name'     => (string)$attachment->getSectionName(),
                'section_id'       => (int)$attachment->getSectionId()
            ];
        }

        return $data;
    }

    /**
     * @param int $storeId
     * @param array|null $attachmentIds
     * @param array|null $sectionIds
     * @param int|null $productId
     * @return array
     */
    protected function getAttachments(
        int $storeId,
        ?array $attachmentIds = null,
        ?array $sectionIds = null,
        ?int $productId = null
    ): array {
        $result = [];

        $attachments = $this->attachmentManager->getAttachments(
            $this->getCustomerGroupId(),
            $productId,
            (array)$attachmentIds,
            (array)$sectionIds
        );
        $inGroupIds  = array_keys($attachments);

        if (!$this->helperData->isHideFiles()) {
            $attachments = $this->attachmentManager->getAttachments(
                null,
                $productId,
                (array)$attachmentIds,
                (array)$sectionIds
            );
        }

        foreach ($attachments as $item) {
            if (in_array($item->getId(), $inGroupIds)) {
                $item->setIsInGroup(true);
            }

            $result[] = $item;
        }

        return $this->getAttachmentsSortedBySection($result);
    }

    /**
     * @param array $attachments
     * @return array
     */
    protected function getAttachmentsSortedBySection(array $attachments): array
    {
        $result = [];

        foreach ($this->getAttachmentsGroupedBySectionId($attachments) as $groupedAttachments) {
            $result = array_merge($result, $groupedAttachments);
        }

        return $result;
    }

    /**
     * @param array $attachments
     * @return array
     */
    public function getAttachmentsGroupedBySectionId(array $attachments): array
    {
        $result = [];

        foreach ($attachments as $attachment) {
            $result[$attachment->getSectionId()][] = $attachment;
        }

        return $result;
    }

    /**
     * @return int
     */
    protected function getCustomerGroupId(): int
    {
        if (isset($this->customerGroupId)) {
            return $this->customerGroupId;
        }

        if (!$this->customerId) {
            return $this->customerGroupId = \Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID;
        }

        $select = $this->resourceConnection->select();
        $select
            ->from('customer_entity', CustomerInterface::GROUP_ID)
            ->where('entity_id', $this->customerId);

        return $this->customerGroupId = (int)$this->resourceConnection->fetchOne($select);
    }

    /**
     * @param Attachment $attachment
     * @return string
     */
    protected function getIconType(Attachment $attachment): string
    {
        if ($attachment->getFiletype()) {
            return $attachment->getFiletype();
        }

        if ($attachment->getUrl()) {
            if (strripos($attachment->getUrl(), 'youtube.com')) {
                return 'youtube';
            }
        }

        return '';
    }

    /**
     * @param Attachment $attachment
     * @return string
     */
    protected function getAttachmentLink(Attachment $attachment): string
    {
        return $this->urlBuilder->getUrl('mwdownloads/download/link', ['id' => $attachment->getId()]);
    }

    /**
     * @param Attachment $attachment
     * @return string|null
     */
    protected function getSizeStr(Attachment $attachment): ?string
    {
        if (!$this->helperData->isDisplaySize() || !$attachment->isFileContent()) {
            return null;
        }

        return $this->getPrepareFileSize((int)$attachment->getSize());
    }

    /**
     * @param int $size
     * @return string
     */
    protected function getPrepareFileSize(int $size): string
    {
        $parsedSize = 0;
        $type       = '';
        $round      = 1;
        $b          = __('B');
        $kb         = __('KB');
        $mb         = __('MB');
        $kbSize     = 1024;
        $mbSize     = $kbSize * $kbSize;

        switch ($this->helperData->getSizePrecision()) {
            case FileSize::FILE_SIZE_PRECISION_AUTO:
                if ($size >= $kbSize && $size < $mbSize) {
                    $parsedSize = $size / $kbSize;
                    $type       = $kb;
                } elseif ($size >= $mbSize) {
                    $parsedSize = $size / $mbSize;
                    $type       = $mb;
                } else {
                    $parsedSize = $size;
                    $type       = $b;
                    $round      = 0;
                }
                break;

            case FileSize::FILE_SIZE_PRECISION_MEGA:
                $parsedSize = $size / $mbSize;
                $type       = $mb;
                $round      = 2;
                break;

            case FileSize::FILE_SIZE_PRECISION_KILO:
                $parsedSize = $size / $kbSize;
                $type       = $kb;
                break;

            default:
                $parsedSize = $size;
                $type       = $b;
                $round      = 0;
                break;
        }

        return round($parsedSize, $round) . ' ' . $type;
    }

    /**
     * @param Attachment $attachment
     * @return int|null
     */
    protected function getDownloadsNumber(Attachment $attachment): ?int
    {
        if (!$this->helperData->isDisplayDownloads() || !$attachment->isFileContent()) {
            return null;
        }

        return (int)$attachment->getDownloads();
    }
}
