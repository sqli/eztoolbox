<?php

namespace SQLI\EzToolboxBundle\QueryType;

use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Core\QueryType\QueryType;

class ChildrenQueryType implements QueryType
{
    /**
     * {@inheritdoc}
     */
    public static function getName(): string
    {
        return 'SQLI:LocationChildren';
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery(array $parameters = []): LocationQuery
    {
        $criteria = [
            new Query\Criterion\Visibility(Query\Criterion\Visibility::VISIBLE),
        ];

        if (!empty($parameters['parent_location_id'])) {
            $criteria[] = new Query\Criterion\ParentLocationId($parameters['parent_location_id']);
        } else {
            $criteria[] = new Query\Criterion\MatchNone();
        }

        if (!empty($parameters['included_content_type_identifier'])) {
            $criteria[] = new Query\Criterion\ContentTypeIdentifier($parameters['included_content_type_identifier']);
        }

        return new LocationQuery(
            [
                'filter' => new Query\Criterion\LogicalAnd($criteria),
                'sortClauses' => [
                    new Query\SortClause\Location\Priority(),
                    new Query\SortClause\DatePublished(Query::SORT_DESC)
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedParameters(): array
    {
        return [
            'parent_location_id',
            'included_content_type_identifier',
        ];
    }
}
