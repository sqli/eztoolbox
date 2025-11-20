<?php

namespace SQLI\EzToolboxBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use SQLI\EzToolboxBundle\Annotations\Annotation as SQLIAdmin;
use stdClass;

/**
 * @ORM\Table(name="eboutique_parameter")
 * @ORM\Entity(repositoryClass="SQLI\EzToolboxBundle\Repository\Doctrine\ParameterRepository")
 * @SQLIAdmin\Entity(update=true,create=true,delete=true,description="Paramètrage",tabname="Param")
 */
class Parameter
{
    /**
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @SQLIAdmin\EntityProperty(readonly=true)
     */
    private $id;
    /**
     * @var string
     * @ORM\Column(name="name", type="string", length=255)
     * @SQLIAdmin\EntityProperty(description="Nom du paramètre")
     */
    private $name;
    /**
     * @var string
     * @ORM\Column(name="value", type="string", length=255)
     * @SQLIAdmin\EntityProperty(
     *     choices={"Activé": "enabled", "Désactivé": "disabled"},
     *     description="Paramètre activé ou non ?")
     */
    private $value;
    /**
     * @var mixed
     *
     * @ORM\Column(name="params", type="object", nullable=true)
     * @SQLIAdmin\EntityProperty(
     *     visible=true,
     *     description="Données complémentaires sérialisées.
     * S'assurer de la validité avant sauvegarde avec https://fr.functions-online.com/unserialize.html")
     */
    private $params;

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Parameter
     */
    public function setId(int $id): Parameter
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Parameter
     */
    public function setName(string $name): Parameter
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return Parameter
     */
    public function setValue(string $value): Parameter
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param mixed $params
     * @return Parameter
     */
    public function setParams($params): Parameter
    {
        $this->params = $params;

        return $this;
    }
}
