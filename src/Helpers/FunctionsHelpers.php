<?php

namespace App\Helpers;

use App\Entity\User;
use Symfony\Component\Security\Core\Security;

class FunctionsHelpers
{

    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function generateSlug($string)
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    }

    public function generateFileName($string)
    {
        return $this->generateSlug(pathinfo($string)["filename"]) . "." . pathinfo($string)["extension"];
    }

    public function checkIfAccessGranted($role)
    {
        $user = $this->security->getUser();
        if ($user) {
            if (in_array($role, $user->getRoles())) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
