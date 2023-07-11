<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/product')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository, SessionInterface $session): Response
    {

        $panier = $session->get("panier", []);

        return $this->render('product/index.html.twig', [
            'products'  => $productRepository->findAll(),
            "panier"    => $panier,
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ProductRepository $productRepository): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $productRepository->add($product);
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, ProductRepository $productRepository): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $productRepository->add($product);
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, ProductRepository $productRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            $productRepository->remove($product);
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @param $productId
     * @param SessionInterface $session
     * @return string
     */
    #[Route("/panier/ajouter/{product}", name: "addPanier")]
    public function ajouterPanierAction(Product $product, ProductRepository $productRepository, SessionInterface $session)
    {
        // $em = $this->getDoctrine()->getManager();

        $panier = $session->get('panier', []);


        if (isset($panier[$product->getId()])) {
            $product->setQuantity($panier[$product->getId()]->getQuantity() + 1);
        } else {
            $product->setQuantity(1);
        }
        $panier[$product->getId()] = $product;

        $session->set("panier", $panier);

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @param $productId
     * @param SessionInterface $session
     * @return string
     */
    #[Route("/panier/retirer/{product}", name: "removePanier")]
    public function retirerPanierAction(Product $product, ProductRepository $productRepository, SessionInterface $session)
    {
        // $em = $this->getDoctrine()->getManager();

        $panier = $session->get('panier', []);
        if (isset($panier[$product->getId()])) {
            unset($panier[$product->getId()]);
        }

        $session->set("panier", $panier);

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }
}
