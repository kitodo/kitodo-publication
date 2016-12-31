Include the static template of the extension
============================================================

1. Go to the root template or the extension template of the clients pagetree rootpage
2. Click on "Edit the whole template record" 
3. On the tab includes click on "Qucosa Publication (dpf)" in the "Available Items" box.


Client storage folder
=====================

Add a new system folder inside the pagetree of the client.
If you want the data to be stored in separate folders, you
need to add some subfolders:

- clientstoragefolder (pid=10)
-- document (pid=11)
-- documenttype (pid=12)
-- metadatapage (pid=13)
-- metadatagroup (pid=14)
-- metadataobject (pid=15)
 

Configuration of the document storage
=====================================

The root page of the client pagetree needs at least an extension template
with the following entries inside the constants section:

plugin.tx_dpf.persistence {
  storagePid = 10
}
module.tx_dpf.persistence < plugin.tx_dpf.persistence


Documents, files and Transfer log data, ...  can also be stored in their own
storage subfolders:

- clientstoragefolder (pid=10)
-- document (pid=11)

plugin.tx_dpf.persistence {
  storagePid = 10
  documentStoragePid = 11
  # fileStoragePid = 
  # documentTransferLogStoragePid = 
  # documentTypeStoragePid = 
  # metadataPageStoragePid = 
  # metadataGroupStoragePid = 
  # metadataObjectStoragePid = 
}
module.tx_dpf.persistence < plugin.tx_dpf.persistence

  
Configuration of the form configuration storage (List-Module)
=============================================================

Configuration data is usually stored in their own folders:

- clientstoragefolder (pid=10)
-- document (pid=11)
-- documenttype (pid=12)
-- metadatapage (pid=13)
-- metadatagroup (pid=14)
-- metadataobject (pid=15)


The tsConfig section of the client storage folder needs the following
configuration settings:

TCAdefaults.tx_dpf_domain_model_documenttype.pid = 12
TCAdefaults.tx_dpf_domain_model_metadatapage.pid = 13
TCAdefaults.tx_dpf_domain_model_metadatagroup.pid = 14
TCAdefaults.tx_dpf_domain_model_metadataobject.pid = 15

TCEFORM.tx_dpf_domain_model_metadatapage.metadata_group {
    PAGE_TSCONFIG_ID < TCAdefaults.tx_dpf_domain_model_metadatagroup.pid
}
TCEFORM.tx_dpf_domain_model_document.document_type {
    PAGE_TSCONFIG_ID < TCAdefaults.tx_dpf_domain_model_documenttype.pid
}