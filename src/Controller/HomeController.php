<?php

namespace App\Controller;

use App\Entity\Command;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class HomeController extends AbstractController
{
    private $em;
    public function __construct(EntityManagerInterface $em, Security $security)
    {
        $this->em = $em;
        $this->security = $security;
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('c')
            ->from('App\Entity\Command', 'c')
            ->where('c.date > :datecourant')
            ->setParameter('datecourant', new \DateTime('1 month ago'))
            ->orderBy('c.date', 'DESC');

        $user = $this->security->getUser();
        $last_commands = $qb->getQuery()
            ->getResult();

        $commands = [];
        if (!in_array("ROLE_ADMIN", $user->getRoles())) {
            foreach ($last_commands as $command) {
                if ($command->getUser()->getId() == $user->getId()) {
                    $commands[] = $command;
                }
            }
        } else {
            $commands = $last_commands;
        }
        return $this->render('home/index.html.twig', [
            "commands"  => $commands
        ]);
    }
}
