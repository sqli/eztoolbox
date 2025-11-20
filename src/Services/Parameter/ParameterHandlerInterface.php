<?php

namespace SQLI\EzToolboxBundle\Services\Parameter;

use Symfony\Component\Console\Output\OutputInterface;

interface ParameterHandlerInterface
{
    public function listParameters();

    /**
     * @param                      $paramName
     * @param                      $paramValue
     * @param                      $contentIds
     * @param OutputInterface|null $output
     * @return array|true Array of error messages or true if no error
     */
    public function setParameter($paramName, $paramValue, $contentIds, OutputInterface $output = null);

    /**
     * @param $paramName
     * @param $paramValue
     * @param OutputInterface|null $output
     * @return mixed
     */
    public function showParameter($paramName, $paramValue, OutputInterface $output = null);

    /**
     * @param mixed $data
     * @param OutputInterface|null $output
     * @return mixed
     */
    public function setData($data, OutputInterface $output = null);

    /**
     * @param OutputInterface|null $output
     * @return mixed
     */
    public function getData(OutputInterface $output = null);

    /**
     * @param OutputInterface|null $output
     * @return mixed
     */
    public function showData(OutputInterface $output = null);

    /**
     * @param array|null $params
     * @return bool
     */
    public function isEnabled($params = null): bool;
}
