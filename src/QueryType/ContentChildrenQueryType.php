<?php

namespace SQLI\EzToolboxBundle\QueryType;

use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Core\QueryType\OptionsResolverBasedQueryType;
use Ibexa\Core\QueryType\QueryType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContentChildrenQueryType extends OptionsResolverBasedQueryType implements QueryType
{
    /**
     * {@inheritdoc}
     */
    public static function getName()
    {
        return 'FetchContentChildren';
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetQuery(array $parameters)
    {
        $criteria =
            [
                new Query\Criterion\Visibility(Query\Criterion\Visibility::VISIBLE)
            ];
        if (isset($parameters['content_types'])) {
            $criteria[] = new Query\Criterion\ContentTypeIdentifier($parameters['content_types']);
        }

        if (isset($parameters['parent_location_id'])) {
            $criteria[] = new Query\Criterion\ParentLocationId($parameters['parent_location_id']);
        }

        return new LocationQuery([
            'filter' => new Query\Criterion\LogicalAnd($criteria),
            'sortClauses' =>
                [
                    new Query\SortClause\DatePublished()
                ],
            'limit' => $parameters['limit'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setDefined(['parent_location_id', 'content_types', 'limit']);
        $optionsResolver->setAllowedTypes('parent_location_id', 'int');
        $optionsResolver->setAllowedTypes('content_types', 'string');
        $optionsResolver->setAllowedTypes('limit', 'int');
        $optionsResolver->setDefault('limit', 10);
    }
}
