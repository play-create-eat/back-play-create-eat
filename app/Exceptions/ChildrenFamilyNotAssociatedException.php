<?php

namespace App\Exceptions;

use App\Models\Child;
use App\Models\Family;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ChildrenFamilyNotAssociatedException extends HttpException
{
    protected $child;
    protected $currentFamily;

    public function __construct(Child $child, Family $currentFamily, $message = '', $statusCode = 403)
    {
        $this->child = $child;
        $this->currentFamily = $currentFamily;

        if (empty($message)) {
            $message = "The child is not associated with the current family.";
        }

        parent::__construct($statusCode, $message);
    }

    public function getChild(): Child
    {
        return $this->child;
    }

    public function getCurrentFamily(): Family
    {
        return $this->currentFamily;
    }
}
