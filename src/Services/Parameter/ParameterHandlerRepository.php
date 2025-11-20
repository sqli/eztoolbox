<?php

namespace SQLI\EzToolboxBundle\Services\Parameter;

use SQLI\EzToolboxBundle\Exceptions\ParameterHandlerUnknownParameterNameException;

class ParameterHandlerRepository
{
    /** @var array<ParameterHandlerInterface> */
    private $handlers;

    public function __construct()
    {
        $this->handlers = array();
    }

    /**
     * @param ParameterHandlerInterface $handler
     */
    public function addHandler(ParameterHandlerInterface $handler)
    {
        $this->handlers[$handler::PARAMETER_NAME] = $handler;
    }

    /**
     * @return array
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    /**
     * @param $handlerName
     * @return ParameterHandlerInterface
     */
    public function getHandler($handlerName)
    {
        if (array_key_exists($handlerName, $this->handlers)) {
            return $this->handlers[$handlerName];
        } else {
            throw new ParameterHandlerUnknownParameterNameException("Parameter handler $handlerName unknown");
        }
    }
}
