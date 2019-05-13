.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.


.. _developer:

Developer
================

.. _developer-tests:

Tests
-----

This extension uses the nimut/testing-framework to run and write unit tests. The test cases are located in the "Tests" directory.
To run the tests use the following command inside the extension root path:
.. code-block::
   vendor/phpunit/phpunit/phpunit -c vendor/nimut/testing-framework/res/Configuration/UnitTests.xml Tests/

.. important::
    (Notice: It is necessary that all dev dependencies are loaded)
