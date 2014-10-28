<?php
namespace EWW\Dpf\Helper;
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class FormFactory {

    /**
     * documentTypeRepository
     * 
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository $documentTypeRepository
     */
    protected $documentTypeRepository = NULL;

    /**
     * metadataPageRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataPageRepository $metadataPageRepository
     */
    protected $metadataPageRepository = NULL;

    /**
     * metadataGroupRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataGroupRepository $metadataGroupRepository
     */
    protected $metadataGroupRepository = NULL;

    /**
     * metadataObjectRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataObjectRepository $metadataObjectRepository
     */
    protected $metadataObjectRepository = NULL;


    public function __construct($documentTypeRepository,$metadataPageRepository,$metadataGroupRepository,$metadataObjectRepository) {

        $this->documentTypeRepository = $documentTypeRepository;
        $this->metadataPageRepository = $metadataPageRepository;
        $this->metadataGroupRepository = $metadataGroupRepository;
        $this->metadataObjectRepository = $metadataObjectRepository;
    }


    /**
     * Creats a Form object, representing the form structure (pages, groups and
     * fields) for the given document type.
     *
     * @param \EWW\Dpf\Domain\Model\DocumentType $documentType 
     */
    public function createForm(\EWW\Dpf\Domain\Model\DocumentType $documentType ) {

        $metadataPages = $documentType->getMetadataPage();

        $qucosaForm = new \EWW\Dpf\Helper\Form();
        $qucosaForm->setDisplayName($documentType->getDisplayName());
        $qucosaForm->setName($documentType->getName());


        $prevPageUid = 0;
        $pageCount = 0;

        // Form pages
        foreach ($metadataPages as $metadataPageKey => $metadataPage) {

          $pageUid = $metadataPage->getUid();

          $formPage = new \EWW\Dpf\Helper\FormPage();
          $formPage->setDisplayName($metadataPage->getDisplayName());
          $formPage->setName($metadataPage->getName());
          $formPage->setUid($pageUid);

          $metadataGroups = $metadataPage->getMetadataGroup();


          $prevGroupUid = 0;
          $groupCount = 0;

          // Form groups
          foreach ($metadataGroups as $metadataGroupKey => $metadataGroup) {

            $groupUid = $metadataGroup->getUid();

            $formGroup = new \EWW\Dpf\Helper\FormGroup();
            $formGroup->setDisplayName($metadataGroup->getDisplayName());
            $formGroup->setName($metadataGroup->getName());
            $formGroup->setUid($groupUid);

            $metadataObjects = $metadataGroup->getMetadataObject();

            
            if ($prevGroupUid == $groupUid) {
                  $groupCount++;
              } else {
                  $groupCount = 0;
              }

            $prevGroupUid = $groupUid;


            $prevFieldUid = 0;
            $fieldCount = 0;

            // Form fields
            foreach ($metadataObjects as $metadataObjectKey => $metadataObject) {

              $fieldUid = $metadataObject->getUid();

              $formField = new \EWW\Dpf\Helper\FormField();
              $formField->setUid($fieldUid);
              $formField->setDisplayName($metadataObject->getDisplayName());
              $formField->setName($metadataObject->getName());
              $formField->setFieldType($metadataObject->getInputField());

              
              if ($prevFieldUid == $fieldUid) {
                  $fieldCount++;
              } else {
                  $fieldCount = 0;
              }

              $prevFieldUid = $fieldUid;


              $fieldId  = "".$metadataPage->getUid()."-";
              $fieldId .= "".$pageCount."-";

              $fieldId .= "".$metadataGroup->getUid()."-";
              $fieldId .= "".$groupCount."-";
              
              $fieldId .= "".$fieldUid."-";
              $fieldId .= "".$fieldCount."";

              $formField->setFieldId($fieldId);


              $formGroup->addChild($formField);
              
            }
          
            $formPage->addChild($formGroup);

          }

          $qucosaForm->addChild($formPage);
        }

        return $qucosaForm;
    }

    /**
     *
     * @param \EWW\Dpf\Domain\Model\DocumentType $documentType
     */
    public function createFormDataArray(\EWW\Dpf\Domain\Model\DocumentType $documentType) {

        $metadataPages = $documentType->getMetadataPage();
      
        $prevPageUid = 0;
        $pageCount = 0;

        $formData = array();
        
        // Form pages
        foreach ($metadataPages as $metadataPageKey => $metadataPage) {

          $pageUid = $metadataPage->getUid();
          $metadataGroups = $metadataPage->getMetadataGroup();

          $prevGroupUid = 0;
          $groupCount = 0;

          $formData[$pageUid] = array();
          $formData[$pageUid][$pageCount] = array();

          // Form groups
          foreach ($metadataGroups as $metadataGroupKey => $metadataGroup) {

            $groupUid = $metadataGroup->getUid();
            $metadataObjects = $metadataGroup->getMetadataObject();


            if ($prevGroupUid == $groupUid) {
                  $groupCount++;
              } else {
                  $groupCount = 0;
              }

            $prevGroupUid = $groupUid;

            $formData[$pageUid][$pageCount][$groupUid] = array();
            $formData[$pageUid][$pageCount][$groupUid][$groupCount] = array();

            $prevFieldUid = 0;
            $fieldCount = 0;

            // Form fields
            foreach ($metadataObjects as $metadataObjectKey => $metadataObject) {

              $fieldUid = $metadataObject->getUid();

              if ($prevFieldUid == $fieldUid) {
                  $fieldCount++;
              } else {
                  $fieldCount = 0;
              }

              $prevFieldUid = $fieldUid;

              $formData[$pageUid][$pageCount][$groupUid][$groupCount][$fieldUid] = array();
              $formData[$pageUid][$pageCount][$groupUid][$groupCount][$fieldUid][$fieldCount] = "";
                                  
            }
          }
        }

        return $formData;
    }


    
    /**
     *
     * @param array $data
     * @param integer $documentUid
     */
    public function createFromDataArray(array $data, integer $documentUid) {

        $documentType = $this->documentTypeRepository->findByUid($documentUid);
        
        $qucosaForm = new \EWW\Dpf\Helper\Form();
        $qucosaForm->setDisplayName($documentType->getDisplayName());
        $qucosaForm->setName($documentType->getName());

        foreach ($data as $pageUid => $pageClass) {

            //$pageUid = (integer)str_replace("p", "", $pageUid);

            foreach ($pageClass as $pageNum => $pageObject) {

                $metadataPage = $this->metadataPageRepository->findByUid($pageUid);

                $formPage = new \EWW\Dpf\Helper\FormPage();
                $formPage->setDisplayName($metadataPage->getDisplayName());
                $formPage->setName($metadataPage->getName());
                $formPage->setUid($pageUid);

                foreach ($pageObject as $groupUid => $groupClass) {

                    //$groupUid = (integer)str_replace("g", "", $groupUid);

                    foreach ($groupClass as $groupNum => $groupObject) {

                        $metadataGroup = $this->metadataGroupRepository->findByUid($groupUid);

                        $formGroup = new \EWW\Dpf\Helper\FormGroup();
                        $formGroup->setDisplayName($metadataGroup->getDisplayName());
                        $formGroup->setName($metadataGroup->getName());
                        $formGroup->setUid($groupUid);

                        foreach ($groupObject as $fieldUid => $fieldClass) {

                            //$fieldUid = (integer)str_replace("f", "", $fieldUid);
                                  
                            foreach ($fieldClass as $fieldNum => $fieldObject) {

                                $metadataObject = $this->metadataObjectRepository->findByUid($fieldUid);

                                $formField = new \EWW\Dpf\Helper\FormField();
                                $formField->setUid($fieldUid);
                                $formField->setDisplayName($metadataObject->getDisplayName());
                                $formField->setName($metadataObject->getName());
                                $formField->setFieldType($metadataObject->getInputField());
                                $formField->setValue($fieldObject);


                                $fieldId  = "".$pageUid."-";
                                $fieldId .= "".$pageNum."-";

                                $fieldId .= "".$groupUid."-";
                                $fieldId .= "".$groupNum."-";

                                $fieldId .= "".$fieldUid."-";
                                $fieldId .= "".$fieldNum."";

                                $formField->setFieldId($fieldId);
                                
                                $formGroup->addChild($formField);                                
                            }
                        }
                        $formPage->addChild($formGroup);
                    }
                }            
                $qucosaForm->addChild($formPage);
            }           
        }

        return $qucosaForm;

    }
    

}

?>