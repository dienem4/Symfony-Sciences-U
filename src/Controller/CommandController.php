<?php

namespace App\Controller;

use App\Entity\Command;
use App\Entity\Product;
use App\Form\CommandType;
use App\Form\PanierType;
use App\Repository\CommandRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use App\Helpers\FunctionsHelpers;

#[Route('/command')]
class CommandController extends AbstractController
{

    private $em;
    private $security;

    public function __construct(Security $security, EntityManagerInterface $em)
    {
        $this->security = $security;
        $this->em = $em;
        $this->fh = new FunctionsHelpers($security);
    }

    #[Route('/', name: 'app_command_index', methods: ['GET'])]
    public function index(CommandRepository $commandRepository): Response
    {
        $user = $this->security->getUser();
        if (in_array("ROLE_ADMIN", $user->getRoles())) {
            $commands = $commandRepository->findAll();
        } else {
            $commands = $commandRepository->findBy(["user" => $user->getId()]);
        }
        return $this->render('command/index.html.twig', [
            'commands' => $commands,
        ]);
    }

    #[Route('/new', name: 'app_command_new', methods: ['GET', 'POST'])]
    public function new(Request $request, CommandRepository $commandRepository): Response
    {
        $command = new Command();
        $form = $this->createForm(CommandType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commandRepository->add($command);
            return $this->redirectToRoute('app_command_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('command/new.html.twig', [
            'command' => $command,
            'form' => $form,
        ]);
    }

    #[Route('/show/{id}', name: 'app_command_show', methods: ['GET'])]
    public function show(Command $command): Response
    {
        return $this->render('command/show.html.twig', [
            'command' => $command,
        ]);
    }

    #[Route('/edit/{id}/edit', name: 'app_command_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Command $command, CommandRepository $commandRepository): Response
    {
        foreach ($command->getProducts() as $key => $product) {
            $command->removeProduct($product);
            if (isset($command->getQuantityArr()[$product->getId()])) {
                $product->setQuantity($command->getQuantityArr()[$product->getId()]);
            }
            $command->addProduct($product);
        }
        $form = $this->createForm(CommandType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $quantity = [];
            $price = 0;
            foreach ($command->getProducts() as $product) {
                $price += $product->getPrice() * $product->getQuantity();
                $quantity[$product->getId()] = $product->getQuantity();
                $command->addProduct($product);
            }

            $command->setPrice($price)
                ->setQuantityArr($quantity)
                ->setUser($this->security->getUser())
                ->setDate(new DateTime());

            if ($form['link']->getData()) {
                $file = $form['link']->getData();
                $filename = $this->fh->generateSlug(pathinfo($file->getClientOriginalName())["filename"]) . "." . pathinfo($file->getClientOriginalName())["extension"];
                $file->move("./images", $filename);
                $command->setLink(new File("./images/" . $filename));
            } else {
                $command->setLink(new File($command->getLink()));
            }

            $commandRepository->add($command);
            return $this->redirectToRoute('app_command_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('command/edit.html.twig', [
            'command' => $command,
            'form' => $form,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_command_delete', methods: ['POST'])]
    public function delete(Request $request, Command $command, CommandRepository $commandRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $command->getId(), $request->request->get('_token'))) {
            $commandRepository->remove($command);
        }

        return $this->redirectToRoute('app_command_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route("/panier", name: "see_panier")]
    public function panierAction(SessionInterface $session, Request $request, CommandRepository $commandRepository)
    {
        $command = new Command();
        $panier = $session->get("panier", []);
        foreach ($panier as $key => $product) {
            $command->addProduct($product);
        }
        $form = $this->createForm(PanierType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $command = new Command();
            $panier = $session->get('panier', []);
            $quantity = [];
            $price = 0;
            foreach ($panier as $key => $product) {
                $price += $product->getPrice() * $product->getQuantity();
                $db_product = $this->em->getRepository(Product::class)->find($key);
                $quantity[$key] = $product->getQuantity();
                $command->addProduct($db_product);
            }

            $session->set("panier", []);

            $command->setPrice($price)
                ->setQuantityArr($quantity)
                ->setUser($this->security->getUser())
                ->setDate(new DateTime());

            $commandRepository->add($command);
            return $this->redirectToRoute('app_command_index', [], Response::HTTP_SEE_OTHER);
        }

        $panier = $session->get('panier', []);
        return $this->renderForm("command/panier.html.twig", [
            "command"   => $command,
            "form"      => $form,
        ]);
    }
}
