<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractJSONAPIController extends AbstractController
{
    protected $validator;

    function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    protected function isJson($varToCheck): bool
    {
        $jsonConstraint = new Assert\Json();
        $errors = $this->validator->validate($varToCheck, $jsonConstraint);
        if (count($errors) > 0) {
            return false;
        }
        return true;
    }
}