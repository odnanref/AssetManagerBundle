<?php

/**
 * This file belongs to the Far/AssetManagerBundle
 *
 * Copyright (C) 2013  Fernando André <netriver at gmail dot com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace Far\AssetManagerBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Far\AssetManagerBundle\Entity\Item;
use Far\AssetManagerBundle\Form\ItemType;
use Far\AssetManagerBundle\Service\ExportCsv;

/**
 * Item controller.
 *
 * @Route("/item")
 */
class ItemController extends Controller
{

    /**
     * Finds and displays a Item entity.
     *
     * @Route("/export/{type}/{conditions}", name="item_export")
     * @Method("POST")
     */
    public function exportAction($type, $conditions)
    {
        if ($conditions != "none" ) {
            $conditions = unserialize(urldecode($conditions));
        } else {
            $conditions = array();
        }

        // Conditions
        switch($type) {
            case 'csv':
                $em = $this->getDoctrine()->getManager();
                $entities = $em->getRepository('FarAssetManagerBundle:Item')
                    ->search($conditions);
                //
                $export = new ExportCsv($entities);
                $out = $export->getOutput();
                break;
            case 'pdf':
                $export = new \StdClass();
                $export->filename = date("Y-m-d") .'_export.pdf';
                $out = $this->exportPdfLabels($conditions);
                if (!is_readable($out)) {
                    throw new \Exception("file is not readable $out ");
                }
                $out = file_get_contents($out);
                break;
        }

        $now = gmdate("D, d M Y H:i:s");
        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT"); // data estupida
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");

        // force download  
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        //
        // disposition / encoding on response body
        header("Content-Disposition: attachment;filename={$export->filename}");
        header("Content-Transfer-Encoding: binary");
        //
        print $out;
        // exit
        exit();
    }

    /**
     * Export labels has PDF for printing
     *
     * @param array $conditions for sql search
     * @return String
     */
    public function exportPdfLabels($conditions)
    {
        // result pdf file location
        $result = sys_get_temp_dir() . "/" . date("Y-m-d_His")."_pdf_out.pdf";
        if (!is_writable(sys_get_temp_dir())) {
            throw new \Exception("Unable to open file for pdf export $result ");
        }
        // html storage template location
        $outfile = sys_get_temp_dir() . "/" . date("Y-m-d_His")."_html_out.html";
        if (!is_writable(sys_get_temp_dir()))  {
            throw new \Exception("Unable to open file for template $outfile ");
        }
        // doctrine
        $em = $this->getDoctrine()->getManager();
        $entities = $em->getRepository('FarAssetManagerBundle:Item')
            ->search($conditions);
        // some render
        $input = $this->renderView(
            'FarAssetManagerBundle:Item:templateLabel_6180.html.twig', 
            array('entities' => $entities)
        );

        $fh = fopen($outfile, "w");
        if ($fh) {
            fwrite($fh, $input);
        }
        fclose($fh);
        // formato Pimaco Bic 6180 100/3000 30/p
        $x = shell_exec('/usr/local/wkhtmltox/bin/wkhtmltopdf -B 13mm -T 13mm -L 3mm -R 3mm '. $outfile . " " . $result );
        if ($x === null && !is_readable($result)) {
            throw new \Exception ("Failed executing html to pdf converter.");
        }
//        $outmessage = ob_get_clean();
        // monolog out message has info
        return $result;
    }

    /**
     * Finds and displays a Item entity.
     *
     * @Route("/search", name="item_search")
     * @Method("GET")
     * @Template("FarAssetManagerBundle:Item:search.html.twig")
     */
    public function searchAction()
    {
        $entity = new Item();
        $entity->setQt("");
        $entity->setValueUnit("");

        $form = $this->createForm(new ItemType(), $entity, array(
            'action' => $this->generateUrl('item_search_post'),
            'method' => 'POST', 'attr' => array('novalidate' => 'novalidate')
        ));
        $form->add('submit', 'submit', array('label' => 'Search'));

        return array(
            'form'      => $form->createView()
        );
    }

    /**
     * List search for entity.
     *
     * @Route("/search", name="item_search_post")
     * @Method("POST")
     * @Template("FarAssetManagerBundle:Item:index.html.twig")
     */
    public function searchResultAction(Request $request)
    {
        $conds = $request->request->get('far_assetmanagerbundle_item');

        $em = $this->getDoctrine()->getManager();
        $entities = $em->getRepository('FarAssetManagerBundle:Item')
            ->search($conds);

        return array(
            'entities' => $entities,
            'search'    => urlencode(serialize($conds))
        );
    }

    /**
     * Lists all Item entities.
     *
     * @Route("/", name="item")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('FarAssetManagerBundle:Item')
            ->findAll();

        return array(
            'entities' => $entities,
        );
    }
    /**
     * Creates a new Item entity.
     *
     * @Route("/", name="item_create")
     * @Method("POST")
     * @Template("FarAssetManagerBundle:Item:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Item();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $code = $em->getRepository("FarAssetManagerBundle:Counters")->getAndInsert("code");
            if ($code !== null) {
                $entity->setCode($code->getTcount());
            }

            $defid = $em->getRepository("FarAssetManagerBundle:Counters")->getAndInsert("defid");
            if ($defid !== null) {
                $entity->setDefid($defid->getTcount());
            }

            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('item_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
    * Creates a form to create a Item entity.
    *
    * @param Item $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createCreateForm(Item $entity)
    {
        $form = $this->createForm(new ItemType(), $entity, array(
            'action' => $this->generateUrl('item_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Item entity.
     *
     * @Route("/new", name="item_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Item();
        $form   = $this->createCreateForm($entity);
        $form->remove("dataviewed")
            ->remove("dataout")
            ->remove("depreciation")
            ->remove("defid")
            ->remove("code")
            ->remove("searchable");

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Item entity.
     *
     * @Route("/{id}", name="item_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FarAssetManagerBundle:Item')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Item entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Item entity.
     *
     * @Route("/{id}/edit", name="item_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FarAssetManagerBundle:Item')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Item entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }


   /**
    * Creates a form to edit a Item entity.
    *
    * @param Item $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Item $entity)
    {
        $form = $this->createForm(new ItemType(), $entity, array(
            'action' => $this->generateUrl('item_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Item entity.
     *
     * @Route("/{id}", name="item_update")
     * @Method("PUT")
     * @Template("FarAssetManagerBundle:Item:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FarAssetManagerBundle:Item')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Item entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('item_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Item entity.
     *
     * @Route("/{id}", name="item_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FarAssetManagerBundle:Item')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Item entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('item'));
    }

    /**
     * Creates a form to delete a Item entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('item_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }
}
