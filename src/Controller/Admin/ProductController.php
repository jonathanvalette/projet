<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\HttpFoundation\Request;
use App\Form\ProductType;
use App\Entity\Product;
use App\Repository\ProductRepository;
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
    public function index(ProductRepository $products)
    {
      $products = $products->findAll();

      return $this->render('admin/shop/all_products.html.twig', ['products' => $products]);
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
