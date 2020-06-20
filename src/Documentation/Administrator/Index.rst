.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _admin-manual:

Administrator Manual
====================

* Elasticsearch 1.3
* Fedora
* SWORD
* Kitodo.Presentation (optional)

.. _admin-installation:

Installation
------------

#. Copy the extension to /typo3conf/ext/dpf/ and use composer to install all needed dependancies.
#. Load the static template
#. Add a folder to the page tree for configuration data
#. Add a client dataset and set the configuration for your installation
#. Add typoscript configuration (see configuration part in the documentation)
#. Create document and form configuration (see configuration part in the documentation)

Client dataset

.. figure:: ../Images/Client_Dataset.png
   :width: 500px
   :alt: Client dataset

   Client dataset


.. _admin-configuration:

Configuration
-------------

* Typoscript configuration

.. code-block:: typoscript
   :linenos:

   plugin.tx_dpf {
      persistence {
         # cat=module.tx_dpf/link; type=int+; label=Default storage PID
         storagePid = 22
      }
   }
   module.tx_dpf.persistence < plugin.tx_dpf.persistence


* Client data
