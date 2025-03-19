<?php

namespace App\Exceptions;

use Exception;
use App\Models\Child;
use App\Models\Family;

class ChildrenFamilyNotAssociatedException extends Exception
{
    protected $child;
    protected $currentFamily;

    public function __construct(Child $child, Family $currentFamily, $message = '', $code = 0)
    {
        $this->child = $child;
        $this->currentFamily = $currentFamily;

        if (empty($message)) {
            $message = "The child is not associated with the current family.";
        }

        parent::__construct($message, $code);
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
