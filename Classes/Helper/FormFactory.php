<?php
namespace EWW\Dpf\Helper;
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class FormFactory {

    /**
     * Creats a Form object, representing the form structure (pages, groups and
     * fields) for the given document type.
     *
     * @param \EWW\Dpf\Domain\Model\DocumentType $documentType
     */
    public function createForm(\EWW\Dpf\Domain\Model\DocumentType $documentType) {

        $metadataPages = $documentType->getMetadataPage();

        $qucosaForm = new \EWW\Dpf\Helper\Form();
        $qucosaForm->setDisplayName($documentType->getDisplayName());
        $qucosaForm->setName($documentType->getName());


        // Form pages
        foreach ($metadataPages as $metadataPage) {

          $formPage = new \EWW\Dpf\Helper\FormPage();
          $formPage->setDisplayName($metadataPage->getDisplayName());
          $formPage->setName($metadataPage->getName());

          $metadataGroups = $metadataPage->getMetadataGroup();


          // Form groups
          foreach ($metadataGroups as $metadataGroup) {

            $formGroup = new \EWW\Dpf\Helper\FormGroup();
            $formGroup->setDisplayName($metadataGroup->getDisplayName());
            $formGroup->setName($metadataGroup->getName());

            $metadataObjects = $metadataGroup->getMetadataObject();

            // Form fields
            foreach ($metadataObjects as $metadataObject) {

              $formField = new \EWW\Dpf\Helper\FormField();
              $formField->setUid($metadataObject->getUid());
              $formField->setDisplayName($metadataObject->getDisplayName());
              $formField->setName($metadataObject->getName());
              $formField->setInputField($metadataObject->getInputField());


              $formGroup->addChild($formField);

            }


            $formPage->addChild($formGroup);

          }

          $qucosaForm->addChild($formPage);
        }

        return $qucosaForm;
    }
}

?>