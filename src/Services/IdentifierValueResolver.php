<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use ReflectionClass;

class IdentifierValueResolver implements ValueResolverInterface
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $entityClass = $argument->getType();

        if (!class_exists($entityClass)) {
            return [];
        }

        $reflection = new ReflectionClass($entityClass);
        if (!$reflection->isSubclassOf('Doctrine\ORM\Mapping\ClassMetadata')) {
            return [];
        }

        $id = $request->attributes->get('id');
        if (!$id) {
            return [];
        }

        $entity = $this->entityManager->getRepository($entityClass)->find($id);
        if (!$entity) {
            throw new NotFoundHttpException("$entityClass not found");
        }

        return [$entity];
    }
}
