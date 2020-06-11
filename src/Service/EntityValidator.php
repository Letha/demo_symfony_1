<?php

namespace App\Service;

use App\Entity\EntityInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EntityValidator
{
    private $validator;

    function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param string[] $validationGroups
     */
    public function getValidationErrors(EntityInterface $entity, ?array $validationGroups = null)
    {
        if ($validationGroups === null) {
            $validationErrors = $this->validator->validate($entity);
        } else {
            $validationErrors = $this->validator->validate($entity, null, $validationGroups);
        }
        return $validationErrors;
    }

    /**
     * @param string[] $validationGroups
     */
    public function getValidationErrorsMessages(EntityInterface $entity, ?array $validationGroups = null)
    {
        $validationErrors = $this->getValidationErrors($entity, $validationGroups);
        $validationErrorsMessages = [];
        foreach ($validationErrors as $validationError) {
            $validationErrorsMessages[] = $validationError->getMessage();
        }
        return $validationErrorsMessages;
    }
}