<?php
declare(strict_types=1);

namespace V4U\GraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * CMS Pages field resolver
 */
class CmsPages implements ResolverInterface
{
    public function __construct(
        \Magento\Cms\Api\PageRepositoryInterface $pageRepositoryInterface,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->pageRepositoryInterface = $pageRepositoryInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $pagesData = $this->getPagesData();
        return $pagesData;
    }

    /**
     * @return array
     * @throws GraphQlNoSuchEntityException
     */
    private function getPagesData(): array
    {
        try {
            /* filter for all the pages */
            $searchCriteria = $this->searchCriteriaBuilder->addFilter('page_id', 1,'gteq')->create();
            $pages = $this->pageRepositoryInterface->getList($searchCriteria)->getItems();
            $cmsPages['allPages'] = [];
            foreach($pages as $page) {
                $cmsPages['allPages'][$page->getId()]['identifier'] = $page->getIdentifier();
                $cmsPages['allPages'][$page->getId()]['name'] = $page->getTitle();
                $cmsPages['allPages'][$page->getId()]['page_layout'] = $page->getPageLayout();
                $cmsPages['allPages'][$page->getId()]['content'] = $page->getContent();
            }
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
        return $cmsPages;
    }
}