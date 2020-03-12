<?php
namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Basket;
use App\Entity\Product;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to manage blog contents in the public part of the site.
 *
 * @Route("/cart")
 *
 */

class BasketController extends AbstractController
{
    private $basket;

    public function __construct(EntityManagerInterface $objectManager)
    {
        $this->basket = new Basket($objectManager);
    }

     /**
     * @Route("/cart", methods="GET", name="cart_show")
     *
     */
    public function show()
    {
        $products = [];
        $totalPrice = 0;

        if ($this->basket->hasProducts()) {
            $products = $this->basket->getProducts();
            $totalPrice = $this->basket->totalPrice($products);

        }

        return $this->render('shop/basket.html.twig', [
            'products' => $products,
            'totalPrice' => $totalPrice,
        ]);
    }

    /**
    * @Route("/add/{id}", methods="GET", name="cart_add")
    *
    */
    public function add($id)
    {
        $product = $this->getDoctrine()
            ->getRepository(Product::class)
            ->find($id);

        if (!$product) {
            throw $this->createNotFoundException();
        }

        if ($product->hasStock()) {
            $this->basket->add($product);
        } else {
            $this->addFlash('primary', 'Le produit n\'est plus en stock');
        }

        $slug = $product->getSlug();

        return $this->redirectToRoute('shop_product', [
            'slug' => $slug,
        ]);
    }

    /**
    * @Route("/remove/{id}", methods="GET|POST", name="cart_remove")
    *
    */
    public function remove($id)
    {
        $product = $this->getDoctrine()
            ->getRepository(Product::class)
            ->find($id);

        if (!$product) {
            throw $this->createNotFoundException();
        }

        $this->basket->remove($product);

        return $this->redirectToRoute('cart_show');
    }

    /**
    * @Route("/update", methods="GET|POST", name="cart_update")
    *
    */
    public function update(Request $req)
    {
        $data = json_decode($req->getContent(), true);
        $id = (int) $data['id'];
        $quantity = (int) $data['quantity'];

        $product = $this->getDoctrine()
            ->getRepository(Product::class)
            ->find($id);

        if (!$product) {
            throw $this->createNotFoundException();
        }

        $this->basket->update($product, $quantity);

        $products = $this->basket->getProducts();
        $totalPrice = $this->basket->totalPrice($products);

        return new JsonResponse([
            'price' => $product->calcTotalPrice(),
            'totalPrice' => $totalPrice,
        ]);
    }

    public function productCount()
    {
        return new Response(count($this->basket));
    }
}
