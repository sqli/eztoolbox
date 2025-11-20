<?php

namespace SQLI\EzToolboxBundle\Services\Parameter;

class ParameterHelper
{
    /** @var ParameterHandlerRepository */
    private $parameterHandlerRepository;

    public function __construct(ParameterHandlerRepository $parameterHandlerRepository)
    {
        $this->parameterHandlerRepository = $parameterHandlerRepository;
    }
}
