<?php

namespace SQLI\EzToolboxBundle\Services\Parameter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use SQLI\EzToolboxBundle\Entity\Doctrine\Parameter;
use SQLI\EzToolboxBundle\Exceptions\ParameterHandlerUnknownParameterValueException;
use Symfony\Component\Console\Output\OutputInterface;

abstract class ParameterHandlerAbstractEntity implements ParameterHandlerInterface
{
    public const PARAMETER_NAME = "";
    public const PARAMETER_ENABLED = "enabled";
    public const PARAMETER_DISABLED = "disabled";
    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function listParameters(): array
    {
        return [
            self::PARAMETER_ENABLED,
            self::PARAMETER_DISABLED
        ];
    }

    /**
     * @param $paramName
     * @param $paramValue
     * @param $contentIds
     * @param OutputInterface|null $output
     * @return bool
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ParameterHandlerUnknownParameterValueException
     */
    public function setParameter($paramName, $paramValue, $contentIds, OutputInterface $output = null): bool
    {
        if ($paramValue == self::PARAMETER_ENABLED || $paramValue == self::PARAMETER_DISABLED) {
            if (
                $parameter = $this->entityManager
                ->getRepository(Parameter::class)
                ->findOneByName(self::PARAMETER_NAME)
            ) {
                $parameter->setValue($paramValue);

                $this->entityManager->persist($parameter);
                $this->entityManager->flush();
                if (isset($output)) {
                    $output->writeln("  Status : " . $parameter->getValue());
                }

                return true;
            }
        }
        throw new ParameterHandlerUnknownParameterValueException("Unsupported value parameter $paramValue");
    }

    /**
     * @param $paramName
     * @param $paramValue
     * @param OutputInterface|null $output
     * @return mixed|void
     */
    public function showParameter($paramName, $paramValue, OutputInterface $output = null)
    {
        if (
            $parameter = $this->entityManager
            ->getRepository(Parameter::class)
            ->findOneByName(self::PARAMETER_NAME)
        ) {
            $output->writeln("  Status : " . $parameter->getValue());
        }
    }

    /**
     * @param array|null $params
     * @return bool
     */
    public function isEnabled($params = null): bool
    {
        if (
            $parameter = $this->entityManager
            ->getRepository(Parameter::class)
            ->findOneByName(self::PARAMETER_NAME)
        ) {
            return $parameter->getValue() == self::PARAMETER_ENABLED;
        }

        return false;
    }

    /**
     * @param                      $data
     * @param OutputInterface|null $output
     * @return bool
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function setData($data, OutputInterface $output = null): bool
    {
        $parameter = $this->entityManager
            ->getRepository(Parameter::class)
            ->findOneByName(self::PARAMETER_NAME);

        if (is_null($parameter)) {
            $parameter = new Parameter();
            $parameter->setName(self::PARAMETER_NAME);
            $parameter->setValue(self::PARAMETER_ENABLED);
        }

        // Check that $data is already serialized
        $dataUnserialize = @unserialize($data);
        if ($dataUnserialize === false) {
            $parameter->setParams($data);
        } else {
            $parameter->setParams($dataUnserialize);
        }

        $this->entityManager->persist($parameter);
        $this->entityManager->flush();

        return true;
    }

    /**
     * @param OutputInterface|null $output
     * @return string|true
     */
    public function showData(OutputInterface $output = null)
    {
        $paramValue = $this->getData($output);

        return print_r($paramValue, true);
    }

    /**
     * @param OutputInterface|null $output
     * @return mixed
     */
    public function getData(OutputInterface $output = null)
    {
        if (
            $parameter = $this->entityManager
            ->getRepository(Parameter::class)
            ->findOneByName(self::PARAMETER_NAME)
        ) {
            return $parameter->getParams();
        }
    }
}
