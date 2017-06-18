<?php

namespace DiscountBundle\Controller;

use DiscountBundle\Entity\ShowProduct;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;use Symfony\Component\HttpFoundation\Request;

/**
 * Showproduct controller.
 *
 * @Route("showproduct")
 */
class ShowProductController extends Controller
{
    /**
     * Lists all showProduct entities.
     *
     * @Route("/", name="showproduct_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $showProducts = $em->getRepository('DiscountBundle:ShowProduct')->findAll();

        return $this->render('showproduct/index.html.twig', array(
            'showProducts' => $showProducts,
        ));
    }

    /**
     * Creates a new showProduct entity.
     *
     * @Route("/new", name="showproduct_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $showProduct = new Showproduct();
        $form = $this->createForm('DiscountBundle\Form\ShowProductType', $showProduct);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($showProduct);
            $em->flush();

            return $this->redirectToRoute('showproduct_show', array('id' => $showProduct->getId()));
        }

        return $this->render('showproduct/new.html.twig', array(
            'showProduct' => $showProduct,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a showProduct entity.
     *
     * @Route("/{id}", name="showproduct_show")
     * @Method("GET")
     */
    public function showAction(ShowProduct $showProduct)
    {
        $deleteForm = $this->createDeleteForm($showProduct);

        return $this->render('showproduct/show.html.twig', array(
            'showProduct' => $showProduct,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing showProduct entity.
     *
     * @Route("/{id}/edit", name="showproduct_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, ShowProduct $showProduct)
    {
        $deleteForm = $this->createDeleteForm($showProduct);
        $editForm = $this->createForm('DiscountBundle\Form\ShowProductType', $showProduct);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('showproduct_edit', array('id' => $showProduct->getId()));
        }

        return $this->render('showproduct/edit.html.twig', array(
            'showProduct' => $showProduct,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a showProduct entity.
     *
     * @Route("/{id}", name="showproduct_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, ShowProduct $showProduct)
    {
        $form = $this->createDeleteForm($showProduct);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($showProduct);
            $em->flush();
        }

        return $this->redirectToRoute('showproduct_index');
    }

    /**
     * Creates a form to delete a showProduct entity.
     *
     * @param ShowProduct $showProduct The showProduct entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(ShowProduct $showProduct)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('showproduct_delete', array('id' => $showProduct->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
