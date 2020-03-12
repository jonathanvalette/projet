<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\HttpFoundation\Request;
use App\Form\ProductType;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Form\CategoryType;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Entity\Image;
use App\Service\Slugger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to manage shop contents in the backend.
 *
 *
 * @Route("/admin/product")
 * @IsGranted("ROLE_ADMIN")
 *
 */
class ProductController extends AbstractController
{
  /**
   * Lists all Product entities.
   *
   *
   * @Route("/", methods="GET", name="admin_product_index")
   */
    public function index(ProductRepository $products): Response
    {
      $products = $products->findAll();

      return $this->render('admin/shop/all_products.html.twig', ['products' => $products]);
    }
    /**
     * Creates a new Category entity.
     *
     * @Route("/newth", methods="GET|POST", name="admin_category_new")
     *
     */
    public function newth(Request $request): Response
    {
        $category = new Category();
        // See https://symfony.com/doc/current/form/multiple_buttons.html
        $form = $this->createForm(CategoryType::class, $category)
            ->add('saveAndCreateNew', SubmitType::class);

        $form->handleRequest($request);

        // the isSubmitted() method is completely optional because the other
        // isValid() method already checks whether the form is submitted.
        // However, we explicitly add it to improve code readability.
        // See https://symfony.com/doc/current/forms.html#processing-forms
        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($category);
            $em->flush();

            // Flash messages are used to notify the user about the result of the
            // actions. They are deleted automatically from the session as soon
            // as they are accessed.
            // See https://symfony.com/doc/current/controller.html#flash-messages
            $this->addFlash('success', 'category.created_successfully');

            if ($form->get('saveAndCreateNew')->isClicked()) {
                return $this->redirectToRoute('admin_category_new');
            }

            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('admin/shop/newth.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }
    /**
     * Creates a new Product entity.
     *
     * @Route("/new/", methods="GET|POST", name="admin_product_new")
     *
     */
    public function editor(Request $req, ProductRepository $products, Slugger $slugger)
    {
        $product = new Product();
        $title = 'Nouveau produit';

        $product->addImage(new Image());

        $form = $this->createForm(ProductType::class, $product);

        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($product->getImages() as $image) {
                if ($file = $image->getFile()) {
                    $filename = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
                    $filesize = filesize($file);
                    $image->setSize($filesize);
                    $image->setName($filename);
                    $file->move($this->getParameter('images_directory'), $filename);
                }
            }

            $slug = $slugger->slugify($product);
            $product->setSlug($slug);

            $em = $this->getDoctrine()->getManager();
            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Produit ajouté');

            return $this->redirect($this->generateUrl('admin_product_index', [
                'id' => $product->getId(),
            ]));
        }

        return $this->render('admin/shop/product_editor.html.twig', [
            'form' => $form->createView(),
            'title' => $title,
        ]);
    }


    /**
     * Deletes a Product entity.
     *
     * @Route("/{id}/delete", methods="POST", name="admin_product_delete")
     * @IsGranted("delete", subject="product")
     */
    public function delete($id)
    {
        $em = $this->getDoctrine()->getManager();

        $product = $em->getRepository(Product::class)->find($id);
        $product->setDeletedAt(new \Datetime());

        $em->persist($product);
        $em->flush();

        $this->addFlash('success', 'Produit supprimé');

        return $this->redirectToRoute('admin_index');
    }
}
