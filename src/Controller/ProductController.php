<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Repository\TagRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Comment;
use App\Events\CommentCreatedEvent;
use App\Form\CommentType;



/**
 * Controller used to manage shop contents in the public part of the site.
 *
 * @Route("/shop")
 *
 */
class ProductController extends AbstractController
{
    /**
    * @Route("/", defaults={"page": "1", "_format"="html"}, methods="GET", name="shop_index")
    * @Route("/page/{page<[1-9]\d*>}", defaults={"_format"="html"}, methods="GET", name="shop_index_paginated")
    * @Cache(smaxage="10")
    *
    */
    public function index(Request $request, int $page, string $_format, ProductRepository $products, TagRepository $tags): Response
    {
        $tag = null;
        if ($request->query->has('tag')) {
            $tag = $tags->findOneBy(['name' => $request->query->get('tag')]);
        }
        $allProducts = $products->findLatest($page, $tag);
        $latestProducts = $products->findLatestOne(3);

        return $this->render('shop/index.'.$_format.'.twig', [
            'paginator' => $allProducts,
            'latest_products' => $latestProducts,
        ]);
    }

    /**
     * @Route("/products/{slug}", methods="GET", name="shop_product")
     *
     */
    public function productShow(Product $product): Response
    {
        // Symfony's 'dump()' function is an improved version of PHP's 'var_dump()' but
        // it's not available in the 'prod' environment to prevent leaking sensitive information.
        // It can be used both in PHP files and Twig templates, but it requires to
        // have enabled the DebugBundle. Uncomment the following line to see it in action:
        //
        // dump($product, $this->getUser(), new \DateTime());

        return $this->render('shop/product_show.html.twig', ['product' => $product]);
    }


    /**
     * @Route("/comment/{productSlug}/new", methods="POST", name="comment_new")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @ParamConverter("product", options={"mapping": {"productSlug": "slug"}})
     *
     */
    public function commentNew(Request $request, Product $product, EventDispatcherInterface $eventDispatcher): Response
    {
        $comment = new Comment();
        $product->addComment($comment);

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($comment);
            $em->flush();

            // When an event is dispatched, Symfony notifies it to all the listeners
            // and subscribers registered to it. Listeners can modify the information
            // passed in the event and they can even modify the execution flow, so
            // there's no guarantee that the rest of this controller will be executed.
            // See https://symfony.com/doc/current/components/event_dispatcher.html
            $eventDispatcher->dispatch(new CommentCreatedEvent($comment));

            return $this->redirectToRoute('shop_product', ['slug' => $product->getSlug()]);
        }

        return $this->render('shop/comment_form_error.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

      /**
       * This controller is called directly via the render() function in the
       * blog/post_show.html.twig template. That's why it's not needed to define
       * a route name for it.
       *
       * The "id" of the Post is passed in and then turned into a Post object
       * automatically by the ParamConverter.
       */
      public function commentForm(Product $product): Response
      {
          $form = $this->createForm(CommentType::class);

          return $this->render('shop/_comment_form.html.twig', [
              'product' => $product,
              'form' => $form->createView(),
          ]);
      }
      /**
       * @Route("/search", methods="GET", name="shop_search")
       */
      public function search(Request $request, ProductRepository $products): Response
      {
          if (!$request->isXmlHttpRequest()) {
              return $this->render('shop/search.html.twig');
          }

          $query = $request->query->get('q', '');
          $limit = $request->query->get('l', 10);
          $foundProducts = $products->findBySearchQuery($query, $limit);

          $results = [];
          foreach ($foundProducts as $product) {
              $results[] = [
                  'name' => htmlspecialchars($product->getName(), ENT_COMPAT | ENT_HTML5),
                  'description' => htmlspecialchars($product->getDescription(), ENT_COMPAT | ENT_HTML5),
              ];
          }

          return $this->json($results);
      }
}
