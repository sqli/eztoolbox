<?php

namespace SQLI\EzToolboxBundle\Annotations;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use SQLI\EzToolboxBundle\Annotations\Annotation\Entity as SQLIEntityAnnotation;
use SQLI\EzToolboxBundle\Annotations\Attribute\Entity as SQLIEntityAttribute;
use SQLI\EzToolboxBundle\Annotations\Annotation\EntityProperty as SQLIEntityPropertyAnnotation;
use SQLI\EzToolboxBundle\Annotations\Attribute\EntityProperty as SQLIEntityPropertyAttribute;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class SQLIAnnotationManager
{
    /**
     * Classname of annotation
     */
    protected string $annotation;
    protected array $directories;
    protected AnnotationReader $annotationReader;
    /**
     * Project root directory
     */
    protected string $projectDir;

    public function __construct($annotation, $directories, $projectDir, AnnotationReader $annotationReader)
    {
        $this->annotation = $annotation;
        $this->directories = $directories;
        $this->projectDir = $projectDir;
        $this->annotationReader = $annotationReader;
    }

    /**
     * Returns all PHP classes annotated with annotation specified in service declaration (see services.yml)
     * @return array
     * @throws ReflectionException
     * @example service : sqli_admin_annotation_entities
     *
     */
    public function getAnnotatedClasses(): array
    {
        $annotations = $this->getSQLIAnnotations();

        // Only annotation in service declaration will be kept
        if (array_key_exists($this->annotation, $annotations)) {
            return $annotations[$this->annotation];
        }

        return [];
    }

    /**
     * Return all PHP classes annotated with an SQLIClassAnnotation
     * For each class, all properties will be defined
     *
     * @return array
     * @throws ReflectionException
     */
    protected function getSQLIAnnotations(): array
    {
        $annotatedClasses = [];

        // Scan all files into directories defined in configuration
        foreach ($this->directories as $entitiesMapping) {
            $directory = $entitiesMapping['directory'];
            $namespace = $entitiesMapping['namespace'];
            if (is_null($namespace)) {
                $namespace = str_replace('/', '\\', $directory);
            }

            $path = $this->projectDir . '/src/' . $directory;
            $finder = new Finder();
            $finder->depth(0)->files()->in($path);

            /** @var SplFileInfo $file */
            foreach ($finder as $file) {
                $className = $file->getBasename('.php');
                $classNamespace = "$namespace\\$className";
                // Create reflection class from generated namespace to read annotation
                $class = new ReflectionClass($classNamespace);

                // Search if $class use an SQLIEntityAttribute or SQLIEntityAnnotation
                $classAnnotation = $this
                    ->getClassAttribute($class, SQLIEntityAttribute::class);
                if (!$classAnnotation) {
                    // No attribute found, try with annotation
                    $classAnnotation = $this
                        ->annotationReader
                        ->getClassAnnotation($class, SQLIEntityAnnotation::class);
                }

                // Check if $class use Doctrine\Entity attribute/annotation
                $classDoctrineAnnotation = $this
                    ->getClassAttribute($class, Entity::class);
                if (!$classDoctrineAnnotation) {
                    $classDoctrineAnnotation = $this
                        ->annotationReader
                        ->getClassAnnotation($class, Entity::class);
                }

                if (!$classAnnotation || !$classDoctrineAnnotation) {
                    // No SQLIClassAnnotation or isn't an entity, ignore her
                    continue;
                }

                // Prepare properties
                $properties = [];
                $compoundPrimaryKey = [];

                $reflectionProperties = $class->getProperties();
                foreach ($reflectionProperties as $reflectionProperty) {
                    // Accessibility of each property
                    $accessibility = "public"; // public
                    if ($reflectionProperty->isPrivate()) {
                        $accessibility = "private"; // private
                    } elseif ($reflectionProperty->isProtected()) {
                        $accessibility = "protected"; // protected
                    }

                    // Try to get an SQLIPropertyAnnotation
                    $visible = true;
                    $readonly = false;
                    $required = true;
                    $columnType = "string";
                    $description = null;
                    $choices = null;
                    $extraLink = null;

                    $propertyAnnotation = $this
                        ->getPropertyAttribute($reflectionProperty, SQLIEntityPropertyAttribute::class);
                    if (!$propertyAnnotation) {
                        // No attribute found, try with annotation
                        $propertyAnnotation = $this
                            ->annotationReader
                            ->getPropertyAnnotation($reflectionProperty, SQLIEntityPropertyAnnotation::class);
                    }

                    if ($propertyAnnotation instanceof SQLIEntityProperty) {
                        // Check if a visibility information defined on entity's property thanks to 'visible' annotation
                        $visible = $propertyAnnotation->isVisible();
                        // Check if property must be only in readonly
                        $readonly = $propertyAnnotation->isReadonly();
                        // Get property description
                        $description = $propertyAnnotation->getDescription();
                        // Get choices
                        $choices = $propertyAnnotation->getChoices();
                        $extraLink = $propertyAnnotation->getExtraLink();
                    }

                    // Check if nullable is sets to true
                    $nullablePropertyAnnotation = $this->getPropertyAttribute($reflectionProperty, Column::class);
                    if (!$nullablePropertyAnnotation) {
                        $nullablePropertyAnnotation = $this
                            ->annotationReader
                            ->getPropertyAnnotation($reflectionProperty, Column::class);
                    }
                    // Column annotation/attribute found, check if type is boolean
                    // To determinate if nullable is allowed or not
                    if ($nullablePropertyAnnotation instanceof ReflectionAttribute) {
                        $columnType = $nullablePropertyAnnotation->getArguments();
                        $required = !($columnType['nullable'] ?? false);
                    } elseif ($nullablePropertyAnnotation instanceof Column) {
                        $columnType = $nullablePropertyAnnotation->type;
                        $required = $columnType == "boolean" ? false : !$nullablePropertyAnnotation->nullable;
                    }

                    $properties[$reflectionProperty->getName()] = [
                        'accessibility' => $accessibility,
                        'visible' => $visible,
                        'readonly' => $readonly,
                        'required' => $required,
                        'type' => $columnType,
                        'description' => $description,
                        'choices' => $choices,
                        'extra_link' => $extraLink,
                    ];

                    // Build primary key from Doctrine\Id annotation
                    $idPropertyAnnotation = $this
                        ->getPropertyAttribute($reflectionProperty, Id::class);
                    if (!$idPropertyAnnotation) {
                        $idPropertyAnnotation = $this
                            ->annotationReader
                            ->getPropertyAnnotation($reflectionProperty, Id::class);
                    }
                    if ($idPropertyAnnotation) {
                        $compoundPrimaryKey[] = $reflectionProperty->getName();
                    }
                }

                /** @var SQLIEntityAnnotation|SQLIEntityAttribute $classAnnotation */
                if ($classAnnotation instanceof ReflectionAttribute) {
                    $annotationFqcn = $classAnnotation->getName();
                } else {
                    $annotationFqcn = get_class($classAnnotation);
                }
                $annotationClassname = substr(strrchr($annotationFqcn, '\\'), 1);

                $annotatedClasses[$annotationClassname][$classNamespace] =
                    [
                        'classname' => $className,
                        'annotation' => $classAnnotation,
                        'properties' => $properties,
                        'primary_key' => $compoundPrimaryKey,
                    ];
            }
        }

        return $annotatedClasses;
    }

    protected function getClassAttribute(ReflectionClass $class, string $annotationName): ?SQLIEntityAttribute
    {
        $attributes = $class->getAttributes();
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === $annotationName) {
                // Prepare SQLIEntityAttribute instance
                $classAttribute = new SQLIEntityAttribute();
                $classAttribute->create = $attribute->getArguments()['create'] ?? false;
                $classAttribute->update = $attribute->getArguments()['update'] ?? false;
                $classAttribute->delete = $attribute->getArguments()['delete'] ?? false;
                $classAttribute->max_per_page = $attribute->getArguments()['max_per_page'] ?? 10;
                $classAttribute->description = $attribute->getArguments()['description'] ?? "";
                $classAttribute->csv_exportable = $attribute->getArguments()['csv_exportable'] ?? false;
                $classAttribute->tabname = $attribute->getArguments()['tabname'] ?? "default";

                return $classAttribute;
            }
        }
        return null;
    }

    protected function getPropertyAttribute(ReflectionProperty $property, string $annotationName): ?ReflectionAttribute
    {
        $attributes = $property->getAttributes();
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === $annotationName) {
                return $attribute;
            }
        }
        return null;
    }
}
